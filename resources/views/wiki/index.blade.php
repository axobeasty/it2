<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('layout.partials.mobile_meta')
    <title>{{ $settings?->title ?? 'Система' }} — База знаний</title>
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
            @include('wiki.partials.sidebar', ['roots' => $roots, 'currentSlug' => null])
        </div>
        <div class="col-12 col-lg p-3 order-1 order-lg-2">
            <div class="wiki-panel p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                    <div>
                        <h1 class="wiki-header-title">База знаний</h1>
                        <p class="wiki-header-subtitle">Внутренняя wiki с быстрым доступом к инструкциям и правилам работы.</p>
                    </div>
                    @if($canEdit)
                        <a href="{{ route('wiki.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Новая статья
                        </a>
                    @endif
                </div>

                <div class="d-lg-none mb-4">
                    @include('wiki.partials.sidebar', ['roots' => $roots, 'currentSlug' => null])
                </div>

                @if($roots->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-journal-bookmark display-4 d-block mb-3 opacity-50"></i>
                        <p class="mb-2">Пока нет ни одной статьи.</p>
                        @if($canEdit)
                            <a href="{{ route('wiki.create') }}" class="btn btn-outline-primary">Создать первую статью</a>
                        @endif
                    </div>
                @else
                    <ul class="list-unstyled mb-0">
                        @foreach($flatForNav as $p)
                            <li class="wiki-list-item d-flex justify-content-between align-items-center gap-2">
                                <a href="{{ route('wiki.show', $p->slug) }}" class="text-decoration-none fw-medium text-dark">{{ $p->title }}</a>
                                <span class="wiki-slug">{{ $p->slug }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
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
