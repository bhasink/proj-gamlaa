<div class="di-lightbox" id="diLightbox" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Inspiration viewer">
    <button type="button" class="di-lightbox__close" id="diLightboxClose" aria-label="Close">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <button type="button" class="di-lightbox__nav di-lightbox__nav--prev" id="diLightboxPrev" aria-label="Previous">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <button type="button" class="di-lightbox__nav di-lightbox__nav--next" id="diLightboxNext" aria-label="Next">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </button>

    <div class="di-lightbox__stage">
        <figure class="di-lightbox__figure">
            <img id="diLightboxImage" src="" alt="">
            <figcaption class="di-lightbox__caption">
                <div class="di-lightbox__meta">
                    <h3 id="diLightboxTitle"></h3>
                    <p id="diLightboxSubtitle"></p>
                    <a id="diLightboxSource" href="#" target="_blank" rel="noopener" class="di-lightbox__source" hidden>
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        <span id="diLightboxSourceLabel">Source</span>
                    </a>
                </div>
                <div class="di-lightbox__actions">
                    <button type="button" class="di-share-btn" data-share="facebook" aria-label="Share on Facebook">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.55 9.87v-6.98H7.9V12h2.55V9.8c0-2.52 1.5-3.9 3.77-3.9 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.98A10 10 0 0 0 22 12z"/></svg>
                    </button>
                    <button type="button" class="di-share-btn" data-share="twitter" aria-label="Share on X">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M18.244 2H21l-6.53 7.47L22 22h-6.89l-4.53-6.14L5.3 22H2.54l7-8L2 2h6.96l4.1 5.57L18.24 2zm-1.2 18.18h1.58L7.06 3.73H5.37l11.67 16.45z"/></svg>
                    </button>
                    <button type="button" class="di-share-btn" data-share="whatsapp" aria-label="Share on WhatsApp">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M20.52 3.48A12 12 0 0 0 3.48 20.52L2 22l1.52-1.5a11.9 11.9 0 0 0 8.5 3.48h.01A12 12 0 0 0 20.52 3.48zM12.02 21.3a9.3 9.3 0 0 1-4.74-1.3l-.34-.2-3.05.9.9-2.97-.22-.35a9.3 9.3 0 1 1 7.45 3.92zm5.36-6.96c-.3-.15-1.74-.86-2.01-.96-.27-.1-.46-.15-.66.15-.2.3-.75.96-.92 1.16-.17.2-.34.22-.63.07-.3-.15-1.25-.46-2.38-1.47a8.9 8.9 0 0 1-1.64-2.04c-.17-.3-.02-.46.13-.6.14-.14.3-.34.45-.51.15-.17.2-.3.3-.5.1-.2.05-.38-.02-.53-.07-.15-.66-1.6-.91-2.19-.24-.57-.48-.5-.66-.5h-.56c-.2 0-.53.07-.8.38-.27.3-1.04 1.02-1.04 2.47 0 1.46 1.06 2.87 1.21 3.07.15.2 2.08 3.18 5.04 4.46.7.3 1.26.48 1.69.62.71.23 1.36.2 1.87.12.57-.09 1.74-.71 1.99-1.4.24-.7.24-1.29.17-1.41-.07-.12-.27-.2-.56-.35z"/></svg>
                    </button>
                    <button type="button" class="di-share-btn" data-share="linkedin" aria-label="Share on LinkedIn">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M4.98 3.5A2.5 2.5 0 1 1 2.5 6 2.5 2.5 0 0 1 4.98 3.5zM3 8.98h4v12H3zM9 8.98h3.83v1.64h.06A4.2 4.2 0 0 1 16.6 8.7c4.09 0 4.85 2.69 4.85 6.18v6.1h-4v-5.41c0-1.29-.02-2.96-1.8-2.96-1.8 0-2.08 1.41-2.08 2.87v5.5H9z"/></svg>
                    </button>
                    <button type="button" class="di-share-btn di-share-btn--copy" data-share="copy" aria-label="Copy link">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.07 0l3-3a5 5 0 1 0-7.07-7.07l-1 1"/><path d="M14 11a5 5 0 0 0-7.07 0l-3 3a5 5 0 1 0 7.07 7.07l1-1"/></svg>
                        <span class="di-share-btn__label">Copy link</span>
                    </button>
                    <button type="button" class="di-share-btn di-share-btn--native" data-share="native" aria-label="Share via device" hidden>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                    </button>
                </div>
            </figcaption>
        </figure>
        <div class="di-lightbox__thumbs" id="diLightboxThumbs" aria-label="Nearby inspirations"></div>
    </div>

    <div class="di-toast" id="diToast" role="status" aria-live="polite"></div>
</div>
