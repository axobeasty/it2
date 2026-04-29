<div class="wiki-sidebar wiki-panel h-100">
    <div class="card-body p-3 p-lg-4">
        <h6 class="wiki-sidebar-title">Содержание</h6>
        @if($roots->isEmpty())
            <p class="text-muted small mb-0">Статей пока нет.</p>
        @else
            <ul class="list-unstyled wiki-tree-root mb-0">
                @foreach($roots as $node)
                    <li class="mb-2">
                        <a href="{{ route('wiki.show', $node->slug) }}" class="wiki-tree-link {{ isset($currentSlug) && $currentSlug === $node->slug ? 'active' : '' }}">
                            {{ $node->title }}
                        </a>
                        @if($node->children->isNotEmpty())
                            @include('wiki.partials.tree_children', ['nodes' => $node->children, 'currentSlug' => $currentSlug ?? null])
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
        <hr class="my-3">
        <a href="{{ route('wiki.index') }}" class="btn btn-outline-primary btn-sm w-100">Все статьи</a>
    </div>
</div>
