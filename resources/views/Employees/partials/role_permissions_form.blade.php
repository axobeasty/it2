{{--
  @var array<string, array<string, string>> $groupedPages
  @var string $idPrefix уникальный префикс id (create | id роли)
  @var \App\Models\Roles|null $editableRole роль при редактировании; null при создании
--}}
<div class="row g-3 role-perms-sections-row">
@foreach($groupedPages as $sectionTitle => $sectionItems)
    <div class="col-12 col-md-6 col-xl-4">
        <div class="role-perms-section card border shadow-sm h-100 mb-0">
        <div class="card-header py-2 px-3 d-flex align-items-center gap-2 border-bottom-0 role-perms-section-head">
            <i class="bi bi-folder2-open text-primary small"></i>
            <span class="fw-semibold small text-uppercase text-secondary" style="letter-spacing: .03em;">{{ $sectionTitle }}</span>
        </div>
        <div class="card-body py-2 px-3 pt-2 pb-3">
            <div class="row g-2">
                @foreach($sectionItems as $key => $label)
                    @php
                        $inputId = 'perm_'.$idPrefix.'_'.$key;
                        $isChecked = $editableRole !== null && $editableRole->pagePermissions->contains('page_key', $key);
                    @endphp
                    <div class="col-12">
                        <label class="perm-toggle w-100 d-block position-relative mb-0" for="{{ $inputId }}">
                            <input class="perm-toggle__input" type="checkbox" name="permissions[]" value="{{ $key }}" id="{{ $inputId }}" @checked($isChecked)>
                            <span class="perm-toggle__face d-flex align-items-center gap-3 w-100 rounded-3 border border-2 px-3 py-2">
                                <span class="perm-toggle__icon flex-shrink-0" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                                <span class="perm-toggle__text small flex-grow-1 text-start">{{ $label }}</span>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
        </div>
    </div>
@endforeach
</div>
