{{-- "cancellations needing action" filter: teal when unselected (in every non-active
     state, so it never falls back to white), white-on-black when applied. --}}
@push('after_styles')
    <style>
        li[filter-name="cancellation_action"] > .nav-link,
        li[filter-name="cancellation_action"] > .nav-link:link,
        li[filter-name="cancellation_action"] > .nav-link:visited,
        li[filter-name="cancellation_action"] > .nav-link:hover,
        li[filter-name="cancellation_action"] > .nav-link:focus {
            color: #026a73 !important;
            background-color: transparent !important;
        }

        li[filter-name="cancellation_action"].active > .nav-link {
            color: #fff !important;
            background-color: #000 !important;
            border-radius: 6px;
            padding: .25rem .65rem !important;
            font-weight: 600;
        }
    </style>
@endpush
