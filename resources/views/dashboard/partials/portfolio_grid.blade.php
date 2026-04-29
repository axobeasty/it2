<div class="modal fade" id="portfolioDetailModal" tabindex="-1" aria-labelledby="portfolioDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="portfolioDetailModalLabel">Портфолио</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body pt-2">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted">Номер записи</dt>
                    <dd class="col-sm-8 fw-medium" id="pdNumber">—</dd>
                    <dt class="col-sm-4 text-muted">Название</dt>
                    <dd class="col-sm-8 fw-medium" id="pdTitle">—</dd>
                    <dt class="col-sm-4 text-muted">Тип</dt>
                    <dd class="col-sm-8" id="pdType">—</dd>
                    <dt class="col-sm-4 text-muted">Роль в портфолио</dt>
                    <dd class="col-sm-8" id="pdRole">—</dd>
                    <dt class="col-sm-4 text-muted">Автор</dt>
                    <dd class="col-sm-8" id="pdAuthor">—</dd>
                    <dt class="col-sm-4 text-muted">Дата добавления</dt>
                    <dd class="col-sm-8" id="pdDate">—</dd>
                    <dt class="col-sm-4 text-muted">Статус</dt>
                    <dd class="col-sm-8" id="pdStatus">—</dd>
                    <dt class="col-sm-4 text-muted">Файл</dt>
                    <dd class="col-sm-8">
                        <a href="#" target="_blank" rel="noopener" class="d-none" id="pdFileLink">Открыть вложение</a>
                        <span class="text-muted" id="pdFileNone">Нет файла</span>
                    </dd>
                </dl>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    @forelse($portfolios as $portfolio)
        @php
            $typeName = optional($portfolio->portfolioType)->name ?? '—';
            $roleName = optional($portfolio->portfolioRole)->name ?? '—';
            $statusCode = (int) $portfolio->status;
            $statusLabel = match ($statusCode) {
                0 => 'На проверке',
                1 => 'Утверждено',
                2 => 'Отклонено',
                default => '—',
            };
            $fileUrl = $portfolio->file_path ? route('portfolio.file', $portfolio) : '';
            $authorName = optional($portfolio->employee)->fio ?? '—';
            $dateStr = $portfolio->created_at
                ? \Carbon\Carbon::parse($portfolio->created_at)->format('d.m.Y H:i')
                : '—';
        @endphp
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card text-center position-relative h-100 border-0 shadow-sm portfolio-detail-card"
                 role="button"
                 tabindex="0"
                 style="cursor: pointer; transition: transform .15s ease, box-shadow .15s ease;"
                 data-bs-toggle="modal"
                 data-bs-target="#portfolioDetailModal"
                 data-number="{{ e($portfolio->number) }}"
                 data-title="{{ e($portfolio->title) }}"
                 data-type="{{ e($typeName) }}"
                 data-role="{{ e($roleName) }}"
                 data-author="{{ e($authorName) }}"
                 data-date="{{ e($dateStr) }}"
                 data-status="{{ e($statusLabel) }}"
                 data-file-url="{{ e($fileUrl) }}">
                <div class="card-body pt-4 pb-3">
                    <div class="mb-2 text-primary">
                        <i class="bi bi-journal-richtext" style="font-size: 2.5rem;"></i>
                    </div>
                    <h6 class="card-title mb-2 fw-semibold text-truncate px-1" title="{{ $portfolio->title }}">{{ $portfolio->title }}</h6>
                    <p class="card-text small text-muted mb-1">{{ $portfolio->number }}</p>
                    <span class="badge text-bg-primary">{{ $typeName }}</span>
                    <div class="mt-2 small">
                        @switch($statusCode)
                            @case(0)
                                <span class="badge text-bg-secondary">На проверке</span>
                                @break
                            @case(1)
                                <span class="badge text-bg-success"><i class="bi bi-patch-check me-1"></i>Утверждено</span>
                                @break
                            @case(2)
                                <span class="badge text-bg-danger">Отклонено</span>
                                @break
                        @endswitch
                    </div>
                    <p class="small text-muted mt-2 mb-0">Нажмите для подробностей</p>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <p class="text-muted text-center py-5 mb-0">Записей в портфолио пока нет.</p>
        </div>
    @endforelse
</div>
