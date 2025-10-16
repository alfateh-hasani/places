<div class="form-group col-md-6">
    <label>{{ $field['label'] ?? 'Smart Lock' }}</label>
    <select name="{{ $field['name'] }}" id="smart_lock_id" class="form-control">
        <option value="">{{ __('cms.lock_select2') }}</option>
    </select>
</div>

@push('after_scripts')
<script>
    $(document).ready(function () {
        let buildingSelect = $('select[name="building_id"]');
        let lockSelect = $('#smart_lock_id');
        let selectedLockId = '{{ old($field["name"], $field["value"] ?? "") }}'; // القيمة المحفوظة مسبقًا

        function loadSmartLocks(buildingId, selectedLock = null) {
            if (!buildingId) {
                lockSelect.html('<option value="">{{ __("cms.lock_select2") }}</option>').trigger('change');
                return;
            }

            $.ajax({
                url: '{{ url("admin/get-smart-locks") }}',
                data: { building_id: buildingId },
                success: function (response) {
                    lockSelect.html('<option value="">{{ __("cms.lock_select2") }}</option>');
                    response.forEach(function (item) {
                        let selectedAttr = (selectedLock && selectedLock == item.id) ? 'selected' : '';
                        lockSelect.append(`<option value="${item.id}" ${selectedAttr}>${item.text}</option>`);
                    });
                    lockSelect.trigger('change');
                }
            });
        }

        // تحميل القائمة عند تغيير المبنى
        buildingSelect.on('change', function () {
            let buildingId = $(this).val();
            loadSmartLocks(buildingId);
        });

        // تحميل الأقفال عند تحميل الصفحة في حالة وجود قيمة محفوظة
        let initialBuildingId = buildingSelect.val();
        if (initialBuildingId) {
            loadSmartLocks(initialBuildingId, selectedLockId);
        }
    });
</script>
@endpush
