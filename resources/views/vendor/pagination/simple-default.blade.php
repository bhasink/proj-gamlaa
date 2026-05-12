@if ($paginator->hasPages())
    <nav>
        <div class="pagination">
            @if ($paginator->onFirstPage())
                <span class="is-disabled" aria-disabled="true">← Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">← Previous</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next →</a>
            @else
                <span class="is-disabled" aria-disabled="true">Next →</span>
            @endif
        </div>
    </nav>
@endif
