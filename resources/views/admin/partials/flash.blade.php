@if(session('flash.success'))
    <div class="flash flash--success">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-12"/></svg>
        <span>{{ session('flash.success') }}</span>
    </div>
@endif
@if(session('flash.error'))
    <div class="flash flash--error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16h.01"/></svg>
        <span>{{ session('flash.error') }}</span>
    </div>
@endif
@if($errors->any())
    <div class="flash flash--error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16h.01"/></svg>
        <span>{{ $errors->first() }}</span>
    </div>
@endif
