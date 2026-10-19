<?php

$periods=$db->query("SELECT * FROM assessment_periods WHERE status IN ('active','closed') ORDER BY start_date DESC")->fetchAll();
$pid=(int)($query['period_id']??($periods[0]['id']??0));
$deptId=role()==='Department Head'?(int)current_user()['department_id']:(isset($query['department_id'])&&(int)$query['department_id']>0?(int)$query['department_id']:null);
$depts=$db->query("SELECT * FROM departments WHERE status='active' ORDER BY name")->fetchAll();

echo '<form method="get" class="actions no-print" style="margin-bottom:12px"><input type="hidden" name="page" value="analytics"><select name="period_id" style="width:280px">';
foreach($periods as $p)echo '<option value="'.$p['id'].'"'.selected($pid,$p['id']).'>'.e($p['title']).'</option>';
echo '</select>';
if(role()!=='Department Head'){
    echo '<select name="department_id" style="width:220px"><option value="0">All departments</option>';
    foreach($depts as $d)echo '<option value="'.$d['id'].'"'.selected($deptId,$d['id']).'>'.e($d['name']).'</option>';
    echo '</select>';
}
echo '<button class="btn btn-secondary">Apply Filters</button></form>';

if(!$pid){echo '<div class="empty">No assessment period is available.</div>';return;}
$count=respondent_count($db,$pid,$deptId);$min=(int)setting($db,'minimum_group_size',5);
if($count<$min){echo '<div class="notice">Not enough respondents to display this group. Minimum group size: '.$min.'.</div>';return;}

$means=factor_means($db,$pid,$deptId);$prevId=previous_period_id($db,$pid);$rule=decision_thresholds($db);
$retention=retention_mean($db,$pid,$deptId);$hrmOverall=overall_mean($db,$pid,$deptId);
echo '<div class="grid kpis">'.card('Respondents',(string)$count).card('Employee Retention',fmt_num($retention),instrument_interpretation($retention)).card('Overall HRM Mean',fmt_num($hrmOverall),instrument_interpretation($hrmOverall)).card('Privacy Minimum',(string)$min,'Minimum aggregate group size').'</div>';
echo '<div class="notice" style="margin:14px 0">Validated questionnaire interpretation: 1.00–1.75 Very Low, 1.76–2.50 Low, 2.51–3.25 High, 3.26–4.00 Very High. Management action thresholds remain configurable separately.</div>';
echo '<div class="grid two"><div class="card"><h3>Factor Comparison</h3>'.svg_bar_chart($means,'name','mean_score').'</div><div class="card"><h3>Factor Details</h3><div class="muted small" style="margin-bottom:8px">Factor · Current · Previous · Trend · Research Interpretation · Management Action</div><div class="factor-list">';
foreach($means as $m){
    if($m['mean_score']===null)continue;
    $cur=(float)$m['mean_score'];$prev=$prevId?factor_mean($db,$prevId,(int)$m['id'],$deptId):null;$trend=trend_label($cur,$prev,$rule['band']);
    $tone=$trend==='Improving'?'good':($trend==='Declining'?'bad':'neutral');
    echo '<div class="factor-item"><strong>'.e($m['name']).'</strong><span>'.fmt_num($cur).'</span><span>'.($prev!==null?fmt_num($prev):'—').'</span>'.badge($trend,$tone).'<span>'.badge(instrument_interpretation($cur),$cur<=2.50?'bad':($cur<=3.25?'warn':'good')).'</span><span>'.badge(status_for_score($cur),$cur<$rule['low']?'bad':($cur<$rule['high']?'warn':'good')).'</span></div>';
}
echo '</div></div></div>';

