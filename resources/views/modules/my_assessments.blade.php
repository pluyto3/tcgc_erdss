<?php

if(isset($query['take'])){
    $pid=(int)$query['take'];$s=$db->prepare('SELECT * FROM assessment_periods WHERE id=? AND status="active"');$s->execute([$pid]);$period=$s->fetch();
    if(!$period){echo '<div class="alert error">Assessment is not available.</div>';return;}
    $s=$db->prepare('SELECT COUNT(*) FROM survey_participation WHERE period_id=? AND employee_id=?');$s->execute([$pid,$eid]);if($s->fetchColumn()){echo '<div class="notice">You already completed this assessment.</div>';return;}
    $s=$db->prepare('SELECT q.*,f.name factor_name FROM survey_questions q JOIN hrm_factors f ON f.id=q.factor_id WHERE q.period_id=? ORDER BY f.display_order,q.display_order');$s->execute([$pid]);$rows=$s->fetchAll();
    echo '<div class="notice">Your participation status is tracked, but your answer set is stored without your employee ID.</div><form method="post" style="margin-top:14px">'.csrf_field().'<input type="hidden" name="action" value="submit_assessment"><input type="hidden" name="period_id" value="'.$pid.'"><div class="card"><h2>'.e($period['title']).'</h2><p>'.e($period['description']).'</p></div>';
    $scale=parse_scale_labels($period['scale_labels']??'',(int)$period['scale_min'],(int)$period['scale_max']);
    $last='';foreach($rows as $q){if($q['factor_name']!==$last){echo '<h3 class="survey-factor">'.e($q['factor_name']).'</h3>';$last=$q['factor_name'];}echo '<div class="question"><div><strong>'.e($q['question_text']).'</strong></div><div class="likert">';foreach($scale as $v=>$lab)echo '<label><input type="radio" name="q['.(int)$q['id'].']" value="'.$v.'" required> '.$v.' · '.e($lab).'</label>';echo '</div></div>';}
    echo '<button class="btn btn-primary" onclick="return confirm(\'Submit this assessment? You cannot change it after submission.\')">Submit Assessment</button></form>';return;
}
$periods=$db->query("SELECT * FROM assessment_periods WHERE status IN ('active','closed') ORDER BY start_date DESC")->fetchAll();
echo '<div class="table-wrap"><table><thead><tr><th>Assessment</th><th>Period</th><th>Status</th><th>Your Status</th><th></th></tr></thead><tbody>';
foreach($periods as $p){$s=$db->prepare('SELECT COUNT(*) FROM survey_participation WHERE period_id=? AND employee_id=?');$s->execute([$p['id'],$eid]);$done=(bool)$s->fetchColumn();echo '<tr><td><strong>'.e($p['title']).'</strong><br><span class="muted small">'.e($p['description']).'</span></td><td>'.e($p['start_date']).' – '.e($p['end_date']).'</td><td>'.badge(ucfirst($p['status']),$p['status']==='active'?'good':'neutral').'</td><td>'.($done?badge('Completed','good'):badge('Not completed','warn')).'</td><td>'.(!$done&&$p['status']==='active'?'<a class="btn btn-primary" href="?page=my_assessments&take='.(int)$p['id'].'">Take assessment</a>':'').'</td></tr>';}
echo '</tbody></table></div>';

