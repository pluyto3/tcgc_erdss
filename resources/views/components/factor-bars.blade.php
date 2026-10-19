@props(['factors', 'lowest' => null])
<div class="factor-bars">
@forelse($factors as $factor)
    @php($score = $factor['mean_score'])
    <div class="factor-bar-row"><div class="factor-bar-label"><span>{{ $factor['name'] }}</span>@if($lowest && $lowest['name'] === $factor['name'])<span class="attention-tag">Monitor closely</span>@endif</div><div class="factor-bar-track" role="img" aria-label="{{ $factor['name'] }}: {{ $score === null ? 'Suppressed or unavailable' : number_format($score, 3).' out of 4' }}"><div class="factor-bar-fill {{ $score !== null && $score < 2.75 ? 'amber' : '' }}" style="width: {{ $score === null ? 0 : max(0, min(100, $score / 4 * 100)) }}%"></div></div><strong>{{ $score === null ? '—' : number_format($score, 3) }}</strong></div>
@empty<div class="empty">No factor scores are available for this assessment.</div>@endforelse
</div><div class="factor-axis"><span>0</span><span>1</span><span>2</span><span>3</span><span>4</span></div>
