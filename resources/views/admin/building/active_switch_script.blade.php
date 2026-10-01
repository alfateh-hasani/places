<script>
    (function () {
        var notify = function (type, text) {
            if (typeof Noty !== 'undefined') {
                new Noty({ type: type, text: text, timeout: 2500 }).show();
            }
        };

        var sendToggle = function (input) {
            input.disabled = true;

            fetch(input.dataset.url, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Request failed');
                    }

                    return response.json();
                })
                .then(function (data) {
                    input.checked = !!data.is_active;
                    notify('success', data.message);
                })
                .catch(function () {
                    input.checked = !input.checked; // revert the optimistic flip
                    notify('error', '{{ __('cms.action_failed') }}');
                })
                .finally(function () {
                    input.disabled = false;
                });
        };

        // Delegated on document so it keeps working after every DataTables redraw
        // (pagination / search / filter re-render the rows).
        document.addEventListener('change', function (event) {
            var input = event.target;

            if (!input.classList || !input.classList.contains('js-building-active-switch')) {
                return;
            }

            var willActivate = input.checked; // the state the user just switched to

            // Confirm before committing — same sweetalert dialog Backpack uses for delete.
            swal({
                title: "{!! trans('backpack::base.warning') !!}",
                text: willActivate
                    ? "{!! __('cms.confirm_activate_building') !!}"
                    : "{!! __('cms.confirm_deactivate_building') !!}",
                icon: "warning",
                buttons: {
                    cancel: {
                        text: "{!! trans('backpack::crud.cancel') !!}",
                        value: null,
                        visible: true,
                        className: "bg-secondary",
                        closeModal: true,
                    },
                    confirm: {
                        text: "{!! __('cms.confirm') !!}",
                        value: true,
                        visible: true,
                        className: willActivate ? "bg-success" : "bg-danger",
                    },
                },
                dangerMode: !willActivate,
            }).then(function (value) {
                if (value) {
                    sendToggle(input);
                } else {
                    input.checked = !input.checked; // dismissed → undo the visual flip
                }
            });
        });
    })();
</script>
