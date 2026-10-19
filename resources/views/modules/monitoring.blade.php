<?php

if(count($periods)<2){echo '<div class="notice">At least two assessment periods are required for comparison.</div>';return;}
$afterId=(int)($query['after']??$periods[0]['id']);$beforeId=(int)($query['before']??$periods[1]['id']);
echo '<form method="get" class="actions no-print" style="margin-bottom:12px"><input type="hidden" name="page" value="monitoring"><label>Before</label><select name="before">';foreach($periods as $p)echo '<option value="'.$p['id'].'"'.selected($beforeId,$p['id']).'>'.e($p['title']).'</option>';echo '</select><label>After</label><select name="after">';foreach($periods as $p)echo '<option value="'.$p['id'].'"'.selected($afterId,$p['id']).'>'.e($p['title']).'</option>';echo '</select><button class="btn btn-secondary">Compare</button></form>';

$bn=respondent_count($db,$beforeId);$an=respondent_count($db,$afterId);$min=(int)setting($db,'minimum_group_size',5);
if(!min_group_ok($db,$bn)||!min_group_ok($db,$an)){echo '<div class="notice">One or both periods have fewer than '.$min.' respondents, so comparison results are suppressed.</div>';return;}

$factors=$db->query("SELECT * FROM hrm_factors WHERE status='active' ORDER BY display_order")->fetchAll();
echo '<div class="notice">Changes shown here are temporal comparisons only. The system does not claim that an intervention caused the change.</div><div class="table-wrap" style="margin-top:14px"><table><thead><tr><th>Factor</th><th>Before</th><th>After</th><th>Difference</th><th>Trend</th><th>Linked Approved Intervention</th></tr></thead><tbody>';
foreach($factors as $f){
    $b=factor_mean($db,$beforeId,(int)$f['id']);$a=factor_mean($db,$afterId,(int)$f['id']);$d=($a!==null&&$b!==null)?$a-$b:null;
    $ints=$db->prepare('SELECT title FROM interventions WHERE factor_id=? AND approval_status="Approved" AND status<>"Cancelled"');$ints->execute([$f['id']]);$titles=$ints->fetchAll(PDO::FETCH_COLUMN);$int=implode(', ',$titles);
    echo '<tr><td>'.e($f['name']).'</td><td>'.($b!==null?fmt_num($b):'—').'</td><td>'.($a!==null?fmt_num($a):'—').'</td><td>'.($d!==null?($d>=0?'+':'').fmt_num($d):'—').'</td><td>'.badge(trend_label($a,$b),$d>0?'good':($d<0?'bad':'neutral')).'</td><td>'.e($int?:'—').'</td></tr>';
}
echo '</tbody></table></div>';

if(role()==='HR Administrator'){
    $ints=$db->query("SELECT i.id,i.code,i.title,f.name factor_name FROM interventions i LEFT JOIN hrm_factors f ON f.id=i.factor_id WHERE i.approval_status='Approved' ORDER BY i.updated_at DESC")->fetchAll();
    echo '<div class="form-card" style="margin-top:14px"><h3>Save Intervention Evaluation</h3><p class="muted">Store a formal before/after evaluation using non-causal wording.</p><form method="post">'.csrf_field().'<input type="hidden" name="action" value="save_evaluation"><div class="form-grid"><div class="form-group full"><label>Approved Intervention</label><select name="intervention_id" required><option value="">Select intervention</option>';foreach($ints as $i)echo '<option value="'.$i['id'].'">'.e($i['code'].' · '.$i['title'].' · '.$i['factor_name']).'</option>';echo '</select></div><div class="form-group"><label>Before Period</label><select name="before_period_id">';foreach($periods as $p)echo '<option value="'.$p['id'].'"'.selected($beforeId,$p['id']).'>'.e($p['title']).'</option>';echo '</select></div><div class="form-group"><label>After Period</label><select name="after_period_id">';foreach($periods as $p)echo '<option value="'.$p['id'].'"'.selected($afterId,$p['id']).'>'.e($p['title']).'</option>';echo '</select></div><div class="form-group full"><label>Evaluator Note</label><textarea name="evaluator_note" placeholder="Interpret the observed change without claiming causation."></textarea></div></div><button class="btn btn-primary" style="margin-top:12px">Save Evaluation</button></form></div>';
}

$evals=$db->query('SELECT ie.*,i.code,i.title,f.name factor_name,b.title before_title,a.title after_title,u.name approved_by FROM intervention_evaluations ie JOIN interventions i ON i.id=ie.intervention_id JOIN hrm_factors f ON f.id=ie.factor_id JOIN assessment_periods b ON b.id=ie.before_period_id JOIN assessment_periods a ON a.id=ie.after_period_id LEFT JOIN users u ON u.id=i.approved_by ORDER BY ie.created_at DESC')->fetchAll();
echo section_title('Saved Evaluations','Formal pre/post records retained for management review.');echo '<div class="table-wrap"><table><thead><tr><th>Intervention</th><th>Factor</th><th>Periods</th><th>Before</th><th>After</th><th>Difference</th><th>Result</th><th>Evaluator Note</th></tr></thead><tbody>';
foreach($evals as $r)echo '<tr><td>'.e($r['code'].' · '.$r['title']).'</td><td>'.e($r['factor_name']).'</td><td>'.e($r['before_title']).'<br>→ '.e($r['after_title']).'</td><td>'.fmt_num($r['before_score']).'</td><td>'.fmt_num($r['after_score']).'</td><td>'.(($r['difference']??0)>=0?'+':'').fmt_num($r['difference']).'</td><td>'.e($r['result_label']).'</td><td>'.e($r['evaluator_note']).'</td></tr>';
if(!$evals)echo '<tr><td colspan="8" class="empty">No saved evaluations yet.</td></tr>';
echo '</tbody></table></div>';

