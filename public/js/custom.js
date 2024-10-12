$(document).ready(function() {
    $('select[name="related_type"]').on('change', function() {
        var relatedTypeValue = $(this).val();
        $('select[name="related"]').empty().trigger('change');
        $('select[name="related"]').select2({
            ajax: {
                url: '/admin/get-related-entities',
                data: function(params) {
                    return {
                        q: params.term,
                        related_type: relatedTypeValue
                    };
                },
                processResults: function(data) {
                    return {
                        results: $.map(data, function(item) {
                            return {
                                id: item.id,
                                text: item.name_ar
                            };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 0,
            placeholder: "اختر كيان متعلق",
        });
    });
});
