{{--
  @var array<string, array<string, string>> $groupedPages
  @var string $idPrefix уникальный префикс id (create | id роли)
  @var \App\Models\Roles|null $editableRole роль при редактировании; null при создании
--}}
@foreach($groupedPages as $sectionTitle => $sectionItems)
    <div class="role-perms-section card border shadow-sm mb-3">
        <div class="card-header py-2 px-3 d-flex align-items-center gap-2 border-bottom-0 role-perms-section-head">
            <i class="bi bi-folder2-open text-primary small"></i>
            <span class="fw-semibold small text-uppercase text-secondary" style="letter-spacing: .03em;">{{ $sectionTitle }}</span>
        </div>
        <div class="card-body py-3 px-3 pt-0">
            <div class="row g-3">
                @foreach($sectionItems as $key => $label)
                    @php
                        $inputId = 'perm_'.$idPrefix.'_'.$key;
                        $isChecked = $editableRole !== null && $editableRole->pagePermissions->contains('page_key', $key);
                    @endphp
                    <div class="col-12 col-lg-6">
                        <div class="form-check form-switch role-perm-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" name="permissions[]" value="{{ $key }}" id="{{ $inputId }}" @checked($isChecked)>
                            <label class="form-check-label w-100" for="{{ $inputId }}">{{ $label }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach
