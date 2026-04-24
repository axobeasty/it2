<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{$settings->title}} — Управление инвентарем</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="https://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
</head>
<body style="background:#eaeff6;">
@include('layout.nav')
<div class="container-fluid p-0" style="height: 100vh;">
    <div class="row g-0">
        <div class="col-12 col-lg-2 p-3 pt-2 pt-lg-3 sidebar-offcanvas-column">
            @include('layout.sidebar_offcanvas')
        </div>
        <div class="col-12 col-lg p-3">
            <div class="bg-white rounded shadow-sm p-4" style="height: calc(100vh - 40px); overflow: auto;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Управление инвентарем</h4>
                    <div class="d-flex gap-2">
                        <a href="/inv/export" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Экспорт в Excel
                        </a>
                        <a href="/inv/print" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-printer"></i> Печать
                        </a>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignModal">
                            <i class="bi bi-plus-lg"></i> Закрепить инвентарь
                        </button>
                    </div>
                </div>
                <p class="text-muted mb-4">Администратор может закреплять инвентарь за сотрудником и откреплять его при списании.</p>
                <div class="mb-3">
                    <input id="inventorySearch" type="text" class="form-control" placeholder="Поиск по названию, номеру, сотруднику или кабинету...">
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Сотрудник</th>
                            <th style="width: 220px;">Кол-во предметов</th>
                            <th style="width: 220px;">Инвентарь</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($groupedByEmployee as $employeeId => $items)
                            @php
                                $employee = $items->first()->employee;
                                $spoilerId = 'inv_'.$employeeId;
                                $searchText = mb_strtolower(trim(($employee->fio ?? '').' '.$items->pluck('store.name')->implode(' ').' '.$items->pluck('number')->implode(' ').' '.$items->pluck('room')->implode(' ')), 'UTF-8');
                            @endphp
                            <tr class="inventory-item" data-search="{{ $searchText }}">
                                <td>{{ $employeeId }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $employee->fio ?? '—' }}</div>
                                </td>
                                <td>{{ $items->count() }}</td>
                                <td>
                                    <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $spoilerId }}">
                                        Показать
                                    </button>
                                    @if($items->count() > 1)
                                        <form action="/inv/unassign-all/{{ $employeeId }}" method="post" class="d-inline ms-2">
                                            @csrf
                                            <button class="btn btn-outline-danger btn-sm" type="submit">Открепить всё</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            <tr class="collapse" id="{{ $spoilerId }}">
                                <td colspan="4" class="bg-light">
                                    <div class="small fw-semibold mb-2">Закрепленный инвентарь:</div>
                                    <div class="d-grid gap-2">
                                        @foreach($items as $item)
                                            <div class="border rounded p-2 bg-white d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div>{{ $item->store->name ?? 'Без названия' }}</div>
                                                    <div class="text-muted small">
                                                        № {{ $item->number ?: '—' }} | Кабинет: {{ $item->room ?: '—' }} | Закреплен: {{ $item->date_in ?: '—' }}
                                                    </div>
                                                </div>
                                                <form action="/inv/unassign/{{ $item->id }}" method="post">
                                                    @csrf
                                                    <button class="btn btn-outline-danger btn-sm" type="submit">Открепить</button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">Закрепленного инвентаря пока нет.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    {{ $employeePages->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Закрепить инвентарь</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/inv/assign" method="post">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Сотрудник</label>
                        <select name="employees_id" class="selectpicker form-control" data-live-search="true" title="Выберите сотрудника..." required>
                            <option value="">Выберите сотрудника</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->fio }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="itemsContainer">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th style="min-width: 260px;">Название предмета</th>
                                    <th style="min-width: 200px;">Инвентарный номер</th>
                                    <th style="min-width: 180px;">Кабинет</th>
                                    <th style="width: 90px;" class="text-center">Действие</th>
                                </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                <tr class="inventory-row">
                                    <td>
                                        <input type="text" name="items[0][item_name]" class="form-control" placeholder="Например: Монитор LG 24" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][number]" class="form-control" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][room]" class="form-control" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-item-row" title="Удалить строку">×</button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-3" id="addItemRow">+ Добавить строку</button>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Закрепить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
<script>
    if ($('.selectpicker').length) {
        $('.selectpicker').selectpicker();
    }

    const inventorySearch = document.getElementById('inventorySearch');
    if (inventorySearch) {
        inventorySearch.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('.inventory-item').forEach(function (item) {
                const haystack = item.dataset.search || '';
                item.style.display = haystack.includes(query) ? '' : 'none';
            });
        });
    }

    const itemsContainer = document.getElementById('itemsContainer');
    const itemsTableBody = document.getElementById('itemsTableBody');
    const addItemRowButton = document.getElementById('addItemRow');
    let rowIndex = 1;

    function reindexRows() {
        if (!itemsTableBody) return;
        const rows = itemsTableBody.querySelectorAll('.inventory-row');
        rows.forEach(function (row, index) {
            const itemName = row.querySelector('input[data-field="item_name"]');
            const number = row.querySelector('input[data-field="number"]');
            const room = row.querySelector('input[data-field="room"]');
            if (itemName) itemName.name = `items[${index}][item_name]`;
            if (number) number.name = `items[${index}][number]`;
            if (room) room.name = `items[${index}][room]`;
        });
        rowIndex = rows.length;
    }

    if (itemsContainer && addItemRowButton && itemsTableBody) {
        const initialInputs = itemsTableBody.querySelectorAll('.inventory-row input');
        initialInputs.forEach(function (input) {
            if (input.name.includes('[item_name]')) input.dataset.field = 'item_name';
            if (input.name.includes('[number]')) input.dataset.field = 'number';
            if (input.name.includes('[room]')) input.dataset.field = 'room';
        });

        addItemRowButton.addEventListener('click', function () {
            const row = document.createElement('tr');
            row.className = 'inventory-row';
            row.innerHTML = `
                <td><input type="text" data-field="item_name" name="items[${rowIndex}][item_name]" class="form-control" required></td>
                <td><input type="text" data-field="number" name="items[${rowIndex}][number]" class="form-control" required></td>
                <td><input type="text" data-field="room" name="items[${rowIndex}][room]" class="form-control" required></td>
                <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm remove-item-row" title="Удалить строку">×</button></td>
            `;
            itemsTableBody.appendChild(row);
            rowIndex++;
        });

        itemsContainer.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-item-row')) {
                const rows = itemsTableBody.querySelectorAll('.inventory-row');
                if (rows.length <= 1) {
                    return;
                }
                event.target.closest('.inventory-row')?.remove();
                reindexRows();
            }
        });
    }
</script>
<script src="https://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
{!! Toastr::message() !!}
</body>
</html>
