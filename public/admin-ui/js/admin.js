(function () {
    "use strict";
    const $  = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const toastStack = (() => {
        let stack = $('.toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'toast-stack';
            document.body.appendChild(stack);
        }
        return stack;
    })();
    function toast(msg, kind = 'ok') {
        const el = document.createElement('div');
        el.className = `toast ${kind === 'error' ? 'toast--error' : ''}`;
        el.textContent = msg;
        toastStack.appendChild(el);
        requestAnimationFrame(() => el.classList.add('is-visible'));
        setTimeout(() => {
            el.classList.remove('is-visible');
            setTimeout(() => el.remove(), 280);
        }, 2400);
    }
    window.adminToast = toast;

    let confirmEl = null;
    function ensureConfirm() {
        if (confirmEl) return confirmEl;
        confirmEl = document.createElement('div');
        confirmEl.className = 'confirm';
        confirmEl.innerHTML = `
            <div class="confirm__card">
                <h3 class="confirm__title"></h3>
                <p  class="confirm__body"></p>
                <div class="confirm__actions">
                    <button type="button" class="btn btn--ghost"  data-confirm="cancel">Cancel</button>
                    <button type="button" class="btn btn--danger" data-confirm="ok">Confirm</button>
                </div>
            </div>`;
        document.body.appendChild(confirmEl);
        return confirmEl;
    }
    function confirmDialog({ title = 'Are you sure?', body = '', okLabel = 'Confirm', danger = true } = {}) {
        return new Promise((resolve) => {
            const el = ensureConfirm();
            el.querySelector('.confirm__title').textContent = title;
            el.querySelector('.confirm__body').textContent  = body;
            const ok = el.querySelector('[data-confirm="ok"]');
            ok.textContent = okLabel;
            ok.classList.toggle('btn--danger', danger);
            ok.classList.toggle('btn--primary', !danger);
            el.classList.add('is-open');

            const onClick = (e) => {
                const t = e.target.closest('[data-confirm]');
                if (!t && e.target !== el) return;
                const choice = t ? t.dataset.confirm : 'cancel';
                cleanup();
                resolve(choice === 'ok');
            };
            const onKey = (e) => {
                if (e.key === 'Escape') { cleanup(); resolve(false); }
                if (e.key === 'Enter')  { cleanup(); resolve(true); }
            };
            function cleanup() {
                el.classList.remove('is-open');
                el.removeEventListener('click', onClick);
                document.removeEventListener('keydown', onKey);
            }
            el.addEventListener('click', onClick);
            document.addEventListener('keydown', onKey);
        });
    }
    window.adminConfirm = confirmDialog;

    async function postJSON(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body || {}),
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    }

    $$('[data-bulk-root]').forEach((root) => {
        const allCheck   = $('[data-bulk-all]', root);
        const rowChecks  = () => $$('[data-bulk-id]', root);
        const toolbar    = $('[data-bulk-toolbar]', root);
        const countLabel = $('[data-bulk-count]', root);
        const action     = root.dataset.bulkUrl;

        function sync() {
            const ids = rowChecks().filter((c) => c.checked).map((c) => parseInt(c.value, 10));
            countLabel && (countLabel.textContent = ids.length + ' selected');
            toolbar && (toolbar.hidden = ids.length === 0);
            rowChecks().forEach((c) => c.closest('tr')?.classList.toggle('is-selected', c.checked));
            if (allCheck) {
                const total = rowChecks().length;
                allCheck.checked = total > 0 && ids.length === total;
                allCheck.indeterminate = ids.length > 0 && ids.length < total;
            }
            return ids;
        }

        allCheck?.addEventListener('change', () => {
            rowChecks().forEach((c) => { c.checked = allCheck.checked; });
            sync();
        });
        root.addEventListener('change', (e) => {
            if (e.target.matches('[data-bulk-id]')) sync();
        });

        $$('[data-bulk-action]', root).forEach((btn) => {
            btn.addEventListener('click', async () => {
                const ids = sync();
                if (!ids.length) return;
                const verb = btn.dataset.bulkAction;
                const confirmCfg = btn.dataset.confirmBody ? {
                    title: btn.dataset.confirmTitle || 'Confirm action',
                    body: btn.dataset.confirmBody.replace('{n}', ids.length),
                    okLabel: btn.dataset.confirmLabel || 'Continue',
                    danger: verb === 'delete',
                } : null;
                if (confirmCfg) {
                    const ok = await confirmDialog(confirmCfg);
                    if (!ok) return;
                }
                try {
                    const result = await postJSON(action, { action: verb, ids });
                    if (verb === 'delete') {
                        ids.forEach((id) => $(`tr[data-row-id="${id}"]`, root)?.remove());
                        toast(`${ids.length} inspiration${ids.length === 1 ? '' : 's'} deleted.`);
                    } else if (verb === 'publish' || verb === 'unpublish') {
                        const on = verb === 'publish';
                        ids.forEach((id) => {
                            const tr = $(`tr[data-row-id="${id}"]`, root);
                            const tog = tr && $('[data-toggle-publish]', tr);
                            if (tog) tog.classList.toggle('is-on', on);
                            const badge = tr && $('[data-status-badge]', tr);
                            if (badge) {
                                badge.classList.toggle('badge--ok', on);
                                badge.classList.toggle('badge--draft', !on);
                                badge.textContent = on ? 'Published' : 'Draft';
                            }
                        });
                        toast(on ? `${ids.length} inspiration${ids.length === 1 ? '' : 's'} published.` : `${ids.length} inspiration${ids.length === 1 ? '' : 's'} moved to drafts.`);
                    }
                    sync();
                } catch (err) {
                    toast('Could not update the selected inspirations.', 'error');
                }
            });
        });

        sync();
    });

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-toggle-publish]');
        if (!btn) return;
        const url = btn.dataset.toggleUrl;
        if (!url) return;
        const previous = btn.classList.contains('is-on');
        btn.classList.toggle('is-on');
        try {
            const result = await postJSON(url, {});
            const on = !!result.is_published;
            btn.classList.toggle('is-on', on);
            const tr = btn.closest('tr');
            const badge = tr && $('[data-status-badge]', tr);
            if (badge) {
                badge.classList.toggle('badge--ok', on);
                badge.classList.toggle('badge--draft', !on);
                badge.textContent = on ? 'Published' : 'Draft';
            }
            toast(on ? 'Published.' : 'Moved to drafts.');
        } catch (err) {
            btn.classList.toggle('is-on', previous);
            toast('Could not update the status.', 'error');
        }
    });

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-toggle-active]');
        if (!btn) return;
        const url = btn.dataset.toggleUrl;
        if (!url) return;
        const previous = btn.classList.contains('is-on');
        btn.classList.toggle('is-on');
        try {
            const result = await postJSON(url, {});
            btn.classList.toggle('is-on', !!result.is_active);
            toast(result.is_active ? 'Category is visible on the site.' : 'Category is hidden from the site.');
        } catch (err) {
            btn.classList.toggle('is-on', previous);
            toast('Could not update the category.', 'error');
        }
    });

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-confirm-delete]');
        if (!btn) return;
        e.preventDefault();
        const ok = await confirmDialog({
            title: btn.dataset.confirmTitle || 'Delete this item?',
            body:  btn.dataset.confirmBody  || 'This action cannot be undone.',
            okLabel: 'Delete',
            danger: true,
        });
        if (!ok) return;
        const form = btn.closest('form');
        if (form) form.submit();
    });

    $$('[data-reorder-root]').forEach((tbody) => {
        const url = tbody.dataset.reorderUrl;
        if (!url) return;
        let dragging = null;

        tbody.addEventListener('dragstart', (e) => {
            const tr = e.target.closest('tr[data-row-id]');
            if (!tr) return;
            dragging = tr;
            tr.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', tr.dataset.rowId);
        });
        tbody.addEventListener('dragend', () => {
            dragging?.classList.remove('is-dragging');
            $$('tr', tbody).forEach((tr) => tr.classList.remove('is-drag-target'));
            dragging = null;
        });
        tbody.addEventListener('dragover', (e) => {
            if (!dragging) return;
            e.preventDefault();
            const tr = e.target.closest('tr[data-row-id]');
            if (!tr || tr === dragging) return;
            const rect = tr.getBoundingClientRect();
            const before = (e.clientY - rect.top) < rect.height / 2;
            tbody.insertBefore(dragging, before ? tr : tr.nextSibling);
        });
        tbody.addEventListener('drop', async (e) => {
            e.preventDefault();
            const ids = $$('tr[data-row-id]', tbody).map((tr) => parseInt(tr.dataset.rowId, 10));
            try {
                await postJSON(url, { ids });
                toast('Order saved.');
            } catch (err) {
                toast('Could not save order.', 'error');
            }
        });
    });

    $$('[data-uploader]').forEach((root) => {
        const fileInput = $('input[type="file"]', root);
        const urlInput  = $('input[data-uploader-url]', root);
        const preview   = $('[data-uploader-preview]', root);
        const fileName  = $('[data-uploader-name]', root);
        const initial   = preview?.dataset.initial || '';
        if (initial) preview.style.backgroundImage = `url("${initial}")`;

        function applyUrl(url) {
            if (!url) return;
            preview.style.backgroundImage = `url("${url}")`;
        }

        fileInput?.addEventListener('change', () => {
            const file = fileInput.files?.[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            applyUrl(url);
            fileName && (fileName.textContent = file.name);
            if (urlInput) urlInput.value = '';
        });
        urlInput?.addEventListener('input', () => {
            const v = urlInput.value.trim();
            if (!v) return;
            applyUrl(v);
            fileName && (fileName.textContent = '');
        });

        ['dragenter', 'dragover'].forEach((evt) => root.addEventListener(evt, (e) => {
            e.preventDefault();
            root.classList.add('is-dragover');
        }));
        ['dragleave', 'drop'].forEach((evt) => root.addEventListener(evt, (e) => {
            e.preventDefault();
            root.classList.remove('is-dragover');
        }));
        root.addEventListener('drop', (e) => {
            const file = e.dataTransfer?.files?.[0];
            if (!file || !file.type.startsWith('image/')) return;
            if (!fileInput) return;
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            fileInput.dispatchEvent(new Event('change'));
        });
    });

    document.addEventListener('keydown', (e) => {
        const tag = (e.target?.tagName || '').toLowerCase();
        const typing = tag === 'input' || tag === 'textarea' || e.target?.isContentEditable;

        if (e.key === '/' && !typing && !e.metaKey && !e.ctrlKey) {
            const search = $('[data-admin-search]');
            if (search) { e.preventDefault(); search.focus(); search.select?.(); }
        }
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's') {
            const submit = $('[data-form-submit]');
            if (submit) { e.preventDefault(); submit.click(); }
        }
    });

    $$('[data-auto-submit]').forEach((el) => {
        el.addEventListener('change', () => el.form?.submit());
    });

    $$('[data-admin-search]').forEach((input) => {
        let t = 0;
        input.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => input.form?.submit(), 360);
        });
    });

    $$('form[method="POST"]').forEach((form) => {
        if (form.closest('.topbar')) return;
        let dirty = false;
        form.addEventListener('input', () => { dirty = true; });
        form.addEventListener('change', () => { dirty = true; });
        form.addEventListener('submit', () => { dirty = false; });
        window.addEventListener('beforeunload', (e) => {
            if (!dirty) return;
            e.preventDefault();
            e.returnValue = '';
        });
    });

    const sourceUrl = $('#source_url');
    const sourceLabel = $('#source_label');
    if (sourceUrl && sourceLabel) {
        let labelTouched = !!sourceLabel.value.trim();
        sourceLabel.addEventListener('input', () => { labelTouched = true; });
        sourceUrl.addEventListener('input', () => {
            if (labelTouched || sourceLabel.value.trim()) return;
            try {
                const host = new URL(sourceUrl.value.trim()).hostname.replace(/^www\./, '');
                sourceLabel.value = host.split('.').slice(0, -1).join(' ').replace(/[-_]/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase());
            } catch (_) {}
        });
    }

    const nameInput = $('#name');
    const slugInput = $('#slug');
    if (nameInput && slugInput) {
        let slugTouched = !!slugInput.value.trim();
        slugInput.addEventListener('input', () => { slugTouched = true; });
        nameInput.addEventListener('input', () => {
            if (slugTouched) return;
            slugInput.value = nameInput.value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        });
    }

    const drawer = $('#quickEditDrawer');
    const quickForm = $('#quickEditForm');
    function closeDrawer() {
        if (!drawer) return;
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('has-drawer-open');
    }
    $$('[data-quick-edit]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!drawer || !quickForm) return;
            quickForm.action = btn.dataset.action || '';
            $('#qe_title').value = btn.dataset.title || '';
            $('#qe_subtitle').value = btn.dataset.subtitle || '';
            $('#qe_category_id').value = btn.dataset.categoryId || '';
            $('#qe_source_url').value = btn.dataset.sourceUrl || '';
            $('#qe_source_label').value = btn.dataset.sourceLabel || '';
            $('#qe_sort_order').value = btn.dataset.sortOrder || '0';
            $('#qe_is_published').checked = btn.dataset.isPublished === '1';
            drawer.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            document.body.classList.add('has-drawer-open');
            $('#qe_title')?.focus({ preventScroll: true });
        });
    });
    $$('[data-drawer-close]').forEach((btn) => btn.addEventListener('click', closeDrawer));
    drawer?.addEventListener('click', (e) => { if (e.target === drawer) closeDrawer(); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && drawer?.classList.contains('is-open')) closeDrawer();
    });
})();
