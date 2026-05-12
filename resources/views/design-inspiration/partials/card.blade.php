<div class="di-item"
     data-id="{{ $item->id }}"
     data-image="{{ $item->image_url }}"
     data-image-width="{{ $item->image_width ?: '' }}"
     data-image-height="{{ $item->image_height ?: '' }}"
     data-title="{{ $item->title }}"
     data-subtitle="{{ $item->subtitle }}"
     data-source-url="{{ $item->source_url }}"
     data-source-label="{{ $item->source_label }}"
     data-share-url="{{ $item->share_url }}">
    <img src="{{ $item->image_md_url }}"
         srcset="{{ $item->image_sm_url }} 600w, {{ $item->image_md_url }} 1200w, {{ $item->image_url }} 2400w"
         sizes="(max-width: 640px) 92vw, (max-width: 1100px) 45vw, 33vw"
         alt="{{ $item->title }}"
         width="{{ $item->image_width ?: '' }}"
         height="{{ $item->image_height ?: '' }}"
         loading="lazy"
         decoding="async">
    <div class="di-overlay"></div>
    <div class="di-share"><img src="{{ asset('images/share.png') }}" alt=""></div>
    <div class="di-content">
        <div class="di-title">{{ $item->title }}</div>
        <div class="di-desc">{{ $item->subtitle }}</div>
    </div>
</div>
