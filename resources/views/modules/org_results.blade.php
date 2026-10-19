<?php

$p=active_period($db)?:latest_periods($db,1)[0]??null;
if(!$p){echo '<div class="empty">No assessment data.</div>';return;}
$count=respondent_count($db,(int)$p['id']);$min=(int)setting($db,'minimum_group_size',5);
if($count<$min){echo '<div class="notice">Not enough respondents to display this group.</div>';return;}
$means=factor_means($db,(int)$p['id']);$retention=retention_mean($db,(int)$p['id']);
echo '<div class="notice">Institution-wide aggregate results only. No individual responses are displayed.</div><div class="grid kpis" style="margin-top:14px">'.card('Employee Retention',fmt_num($retention),instrument_interpretation($retention)).card('HRM Overall Mean',fmt_num(overall_mean($db,(int)$p['id'])),instrument_interpretation(overall_mean($db,(int)$p['id']))).'</div><div class="card" style="margin-top:14px"><h3>'.e($p['title']).'</h3>'.svg_bar_chart($means,'name','mean_score').'</div><div class="table-wrap" style="margin-top:14px"><table><thead><tr><th>HRM Factor</th><th>Mean</th><th>Interpretation</th></tr></thead><tbody>';
foreach($means as $m){if($m['mean_score']===null)continue;$v=(float)$m['mean_score'];echo '<tr><td>'.e($m['name']).'</td><td>'.fmt_num($v).'</td><td>'.badge(instrument_interpretation($v),$v<=2.50?'bad':($v<=3.25?'warn':'good')).'</td></tr>';}
echo '</tbody></table></div>';

