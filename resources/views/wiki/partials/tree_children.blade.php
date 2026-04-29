<ul class="wiki-tree-nested">
    @foreach($nodes as $node)
        <li class="mb-1">
            <a href="{{ route('wiki.show', $node->slug) }}" class="wiki-tree-link {{ isset($currentSlug) && $currentSlug === $node->slug ? 'active' : '' }}">
                {{ $node->title }}
            </a>
            @if($node->relationLoaded('children') && $node->children->isNotEmpty())
                @include('wiki.partials.tree_children', ['nodes' => $node->children, 'currentSlug' => $currentSlug ?? null])
            @endif
        </li>
    @endforeach
</ul>
