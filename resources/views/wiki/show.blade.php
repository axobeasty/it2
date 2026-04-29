<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('layout.partials.mobile_meta')
    <title>{{ $page->title }} — {{ $settings?->title ?? 'База знаний' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    @include('wiki.partials.styles')
</head>
<body class="wiki-page">
<div class="container-fluid p-0 wiki-shell">
    <div class="row g-0">
        <div class="col-12 p-0">
            @include('layout.nav')
        </div>
    </div>
    <div class="wiki-layout w-100">
    <div class="row g-0">
        <div class="col-12 col-lg-3 p-3 order-2 order-lg-1 d-none d-lg-block">
            @include('wiki.partials.sidebar', ['roots' => $sidebarRoots, 'currentSlug' => $page->slug])
        </div>
        <div class="col-12 col-lg p-3 order-1 order-lg-2">
            <div class="wiki-panel p-3 p-md-4">
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb small mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('wiki.index') }}">База знаний</a></li>
                        @if($page->parent)
                            <li class="breadcrumb-item {{ $parentReadable ?? false ? '' : 'text-muted' }}">
                                @if($parentReadable ?? false)
                                    <a href="{{ route('wiki.show', $page->parent->slug) }}">{{ $page->parent->title }}</a>
                                @else
                                    <span title="Нет доступа к родительской странице">{{ $page->parent->title }}</span>
                                @endif
                            </li>
                        @endif
                        <li class="breadcrumb-item active" aria-current="page">{{ $page->title }}</li>
                    </ol>
                </nav>

                @if($canEdit && $page->roles->isNotEmpty())
                    <p class="small text-muted mb-2"><i class="bi bi-shield-lock me-1"></i>Просмотр только для ролей: {{ $page->roles->pluck('name')->join(', ') }}</p>
                @endif
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <h1 class="wiki-header-title">{{ $page->title }}</h1>
                    <div class="d-flex flex-wrap gap-2">
                        @if($canEdit)
                            <a href="{{ route('wiki.edit', $page->slug) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> Правка</a>
                            <form action="{{ route('wiki.destroy', $page->slug) }}" method="post" class="d-inline" onsubmit="return confirm('Удалить статью «{{ $page->title }}»?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                        @endif
                    </div>
                </div>

                <p class="text-muted small mb-4">
                    Обновлено: {{ $page->updated_at?->translatedFormat('d.m.Y H:i') ?? '—' }}
                    @if($page->editor)
                        · {{ $page->editor->fio }}
                    @elseif($page->creator)
                        · {{ $page->creator->fio }}
                    @endif
                </p>

                <div class="d-lg-none mb-4">
                    @include('wiki.partials.sidebar', ['roots' => $sidebarRoots, 'currentSlug' => $page->slug])
                </div>

                <article class="wiki-content">
                    {!! $html !!}
                </article>
            </div>
        </div>
    </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
