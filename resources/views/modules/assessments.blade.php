<?php

if(isset($query['new'])||isset($query['edit'])){$edit=null;if(isset($query['edit'])){$s=$db->prepare('SELECT * FROM assessment_periods WHERE id=?');$s->execute([(int)$query['edit']]);$edit=$s->fetch();}echo '<div class="form-card"><h2>'.($edit?'Edit Assessment':'New Assessment').'</h2><form method="post">'.csrf_field().'<input type="hidden" name="action" value="save_period"><input type="hidden" name="id" value="'.e($edit['id']??'').'"><div class="form-grid"><div class="form-group full"><label>Title</label><input name="title" required value="'.e($edit['title']??'').'"></div><div class="form-group full"><label>Description / Instructions</label><textarea name="description">'.e($edit['description']??'').'</textarea></div><div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="'.e($edit['start_date']??'').'"></div><div class="form-group"><label>End Date</label><input type="date" name="end_date" value="'.e($edit['end_date']??'').'"></div><div class="form-group"><label>Status</label><select name="status">';foreach(['draft','active','closed'] as $st)echo '<option'.selected($edit['status']??'draft',$st).'>'.$st.'</option>';echo '</select></div><div class="form-group"><label>Scale Minimum</label><input type="number" name="scale_min" min="1" value="'.e($edit['scale_min']??'1').'"></div><div class="form-group"><label>Scale Maximum</label><input type="number" name="scale_max" min="2" value="'.e($edit['scale_max']??'4').'"></div><div class="form-group full"><label>Scale Labels</label><input name="scale_labels" value="'.e($edit['scale_labels']??'1=Strongly Disagree|2=Disagree|3=Agree|4=Strongly Agree').'"><span class="help">Format: 1=Strongly Disagree|2=Disagree|3=Agree|4=Strongly Agree</span></div></div><button class="btn btn-primary" style="margin-top:14px">Save Assessment</button></form></div>';return;}
if(isset($query['view'])){
    $pid=(int)$query['view'];
    $s=$db->prepare('SELECT * FROM assessment_periods WHERE id=?');$s->execute([$pid]);$p=$s->fetch();
    if(!$p){echo '<div class="empty">Not found.</div>';return;}
    $q=$db->prepare('SELECT q.*,f.name factor_name FROM survey_questions q JOIN hrm_factors f ON f.id=q.factor_id WHERE q.period_id=? ORDER BY f.display_order,q.display_order');$q->execute([$pid]);$qs=$q->fetchAll();
    $s=$db->prepare('SELECT COUNT(*) FROM survey_responses WHERE period_id=?');$s->execute([$pid]);$locked=(int)$s->fetchColumn()>0;
    $factors=$db->query("SELECT * FROM hrm_factors WHERE status='active' ORDER BY display_order")->fetchAll();
    echo '<div class="actions no-print" style="margin-bottom:12px"><a class="btn btn-secondary" href="?page=assessments">Back</a><a class="btn btn-secondary" href="?page=assessments&edit='.$pid.'">Edit Assessment</a></div><div class="card"><h2>'.e($p['title']).'</h2><p>'.e($p['description']).'</p><div>'.badge($p['status'],$p['status']==='active'?'good':'neutral').' <span class="muted small">'.$p['start_date'].' – '.$p['end_date'].'</span></div></div>';
    if($locked)echo '<div class="notice" style="margin-top:14px"><strong>Questionnaire locked.</strong> Responses have been collected. Create a new assessment period to change the questions.</div>';
    echo '<div class="grid'.($locked?'':' two').'" style="margin-top:14px"><div class="table-wrap"><table><thead><tr><th>#</th><th>Factor</th><th>Question</th>'.($locked?'':'<th></th>').'</tr></thead><tbody>';
    foreach($qs as $qq){
        echo '<tr><td>'.$qq['display_order'].'</td><td>'.e($qq['factor_name']).'</td><td>'.e($qq['question_text']).'</td>';
        if(!$locked)echo '<td><form method="post" onsubmit="return confirm(\'Remove question?\')">'.csrf_field().'<input type="hidden" name="action" value="delete_question"><input type="hidden" name="id" value="'.$qq['id'].'"><input type="hidden" name="period_id" value="'.$pid.'"><button class="btn btn-danger">Remove</button></form></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
    if(!$locked){
        echo '<div class="form-card"><h3>Add Question</h3><form method="post">'.csrf_field().'<input type="hidden" name="action" value="add_question"><input type="hidden" name="period_id" value="'.$pid.'"><div class="form-group"><label>Factor</label><select name="factor_id">';
        foreach($factors as $f)echo '<option value="'.$f['id'].'">'.e($f['name']).'</option>';
        echo '</select></div><div class="form-group" style="margin-top:10px"><label>Question</label><textarea name="question_text" required></textarea></div><div class="form-group" style="margin-top:10px"><label>Display Order</label><input type="number" name="display_order" value="'.(count($qs)+1).'"></div><button class="btn btn-primary" style="margin-top:12px">Add Question</button></form></div>';
    }
    echo '</div>';return;
}
$rows = $db->query('SELECT p.*,
    (SELECT COUNT(*) FROM survey_questions q WHERE q.period_id=p.id) question_count,
    (SELECT COUNT(*) FROM survey_responses sr WHERE sr.period_id=p.id) response_count
    FROM assessment_periods p ORDER BY p.start_date DESC,p.id DESC')->fetchAll();
echo '<div class="actions no-print" style="justify-content:flex-end;margin-bottom:12px"><a class="btn btn-primary" href="?page=assessments&new=1">New Assessment</a></div><div class="table-wrap"><table><thead><tr><th>Assessment</th><th>Dates</th><th>Questions</th><th>Responses</th><th>Status</th><th></th></tr></thead><tbody>';foreach($rows as $r)echo '<tr><td><strong>'.e($r['title']).'</strong><br><span class="muted small">'.e($r['description']).'</span></td><td>'.$r['start_date'].' – '.$r['end_date'].'</td><td>'.$r['question_count'].'</td><td>'.$r['response_count'].'</td><td>'.badge($r['status'],$r['status']==='active'?'good':'neutral').'</td><td><a class="btn btn-secondary" href="?page=assessments&view='.$r['id'].'">Manage</a></td></tr>';echo '</tbody></table></div>';

