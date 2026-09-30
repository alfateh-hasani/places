@include('crud::fields.text')

@if(isset($field['target']) && $field['target'] != null && $field['target'] != '')
  @push('after_scripts')
  @basset('https://cdn.jsdelivr.net/npm/slugify@1.6.6/slugify.min.js')
    <script>
          
        crud.field('{{ $field['target'] }}').onChange(field => {
          let lower = '{{$field['lower'] ?? true}}';
          let strict = '{{$field['strict'] ?? true}}';
          let remove = '{{$field['remove'] ?? null}}';

          lower = (lower === '1');
          strict = (strict === '1');
          if(remove === '') {
            remove = undefined;
          }

          let slug = slugify(field.value, {
            replacement: '{{$field['replacement'] ?? "-"}}',
            lower: lower,
            locale: '{{$field['locale'] ?? app()->getLocale()}}',
            remove: remove,
            strict: strict
          });
          crud.field('{{ $field['name'] }}').input.value = slug;
        });
    </script>
  @endpush
@endif
