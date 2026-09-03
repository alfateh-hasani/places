{{-- custom activity log changes view (app override) --}}
{{--
    Overrides vendor/backpack/activity-log/resources/views/columns/activity_changes.blade.php.
    Fix: __($key) returns an ARRAY when an attribute name collides with a lang file
    (e.g. the bookings.site column vs resources/lang/en/site.php), which made
    ucfirst() throw a TypeError. We fall back to the raw key in that case.
--}}
@php
$values = $column['value'] ?? data_get($entry, $column['name']);
$old = isset($values['old']);
$deleted = $old && !isset($values['attributes']);
@endphp

<table class="table table-sm table-bordered table-hover">
    <tr>
        <th>{{ ucfirst(__('backpack.activity-log::activity_log.key')) }}</th>
        @if($old)
        <th>{{ ucfirst(__('backpack.activity-log::activity_log.previous_value')) }}</th>
        @endif
        <th>{{ ucfirst(__('backpack.activity-log::activity_log.new_value')) }}</th>
    </tr>
    @php
        $entries = $deleted ? ($values['old'] ?? []) : ($values['attributes'] ?? []);
    @endphp

    @foreach ($entries as $key => $new)
        @if ($deleted || !in_array($key, ['id', 'deleted_at']))
            @php
                $keyLabel = __($key);
                $keyLabel = is_array($keyLabel) ? $key : $keyLabel;
            @endphp
            <tr>
                <td class="font-weight-bold">{{ str_replace('_', ' ', ucfirst($keyLabel)) }}</td>
                @if ($old)
                    <td class="{{ isset($values['old'][$key]) && $values['old'][$key] != $new ? 'text-danger' : '' }}">
                        @if (isset($values['old'][$key]) && is_array($values['old'][$key]))
                            <ul class="pl-3" style="list-style: circle">
                                @foreach ($values['old'][$key] as $attribute => $value)
                                    <li><strong>{{ $attribute }}</strong>: {{ $value }}</li>
                                @endforeach
                            </ul>
                        @else
                            {{ $values['old'][$key] ?? '' }}
                        @endif
                    </td>
                @endif
                <td class="{{ isset($values['old'][$key]) && $values['old'][$key] != $new ? 'text-success' : '' }}">
                    @if (is_array($new))
                        <ul class="pl-3" style="list-style: circle">
                            @foreach ($new as $attribute => $value)
                                <li><strong>{{ $attribute }}</strong>: {{ $value }}</li>
                            @endforeach
                        </ul>
                    @else
                        {{ $new }}
                    @endif
                </td>
            </tr>
        @endif
    @endforeach
</table>
