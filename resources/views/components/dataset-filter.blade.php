@props(['unitScope', 'baseline', 'demo', 'periods', 'period', 'departments', 'departmentId', 'scope'])
<form method="get" class="dataset-filter" action="{{ url()->current() }}">
    @unless($unitScope)<div class="filter-field"><label for="dataset">Data source</label><select name="dataset" id="dataset"><option value="baseline" @selected($baseline)>Research baseline</option><option value="live" @selected(!$baseline)>{{ $demo ? 'Demo assessment data' : 'Operational assessment data' }}</option></select></div>@endunless
    @unless($baseline)
        <div class="filter-field"><label for="period_id">Assessment period</label><select name="period_id" id="period_id">@forelse($periods as $item)<option value="{{ $item->id }}" @selected($period?->id === $item->id)>{{ $item->title }}</option>@empty<option value="">No assessment periods</option>@endforelse</select></div>
        @unless($unitScope)<div class="filter-field"><label for="department_id">Operational unit</label><select name="department_id" id="department_id"><option value="0">All operational units</option>@foreach($departments as $item)<option value="{{ $item->id }}" @selected($departmentId === $item->id)>{{ $item->name }}</option>@endforeach</select></div>@endunless
    @endunless
    <button class="btn btn-secondary" type="submit"><x-icon name="sliders" />Apply filters</button>
    <div class="filter-summary"><span class="status-dot"></span>{{ $baseline ? 'Institution-wide reference' : $scope }}</div>
</form>
