{{-- Simple Backpack CRUD filter --}}
<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    filter-key="{{ $filter->key }}"
	class="nav-item {{ Request::get($filter->name)?'active':'' }}">
    <a class="nav-link" href=""
		parameter="{{ $filter->name }}"
    	>{{ $filter->label }}</a>
  </li>


{{-- ########################################### --}}
{{-- Extra CSS and JS for this particular filter --}}

{{-- FILTERS EXTRA CSS --}}
{{-- push things in the after_styles section --}}

{{-- @push('crud_list_styles')
	no css
@endpush --}}


{{-- FILTERS EXTRA JS --}}
{{-- push things in the after_scripts section --}}

@push('crud_list_scripts')
    <script>
		jQuery(document).ready(function($) {
			$("li[filter-key={{ $filter->key }}] a").click(function(e) {
				e.preventDefault();

				var parameter = $(this).attr('parameter');
				let value = true;

				// check if the filter is already active
				if (URI(window.location.href).hasQuery(parameter, true)) {
					// remove the filter from the url
					value = null;
				} 

				var new_url = updateDatatablesOnFilterChange(parameter, value, true, {{ $filter->options['debounce'] ?? 0 }});

				// mark this filter as active in the navbar-filters
				if (URI(new_url).hasQuery('{{ $filter->name }}', true)) {
					$("li[filter-key={{ $filter->key }}]").removeClass('active').addClass('active');
                    $('#remove_filters_button').removeClass('invisible');
				}
				else
				{
					$("li[filter-key={{ $filter->key }}]").trigger("filter:clear");
				}
			});

			// clear filter event (used here and by the Remove all filters button)
			$("li[filter-key={{ $filter->key }}]").on('filter:clear', function(e) {

				$("li[filter-key={{ $filter->key }}]").removeClass('active');
			});
		});
	</script>
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
