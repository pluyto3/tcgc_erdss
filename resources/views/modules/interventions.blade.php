<?php


$where='1=1';$args=[];
if(role()==='Department Head'){$where='i.responsible_department_id=?';$args=[current_user()['department_id']];}
$s=$db->prepare('SELECT i.*,f.name factor_name,d.name department,r.title recommendation,pa.priority_level,u.name responsible_user,au.name approved_by_name FROM interventions i LEFT JOIN hrm_factors f ON f.id=i.factor_id LEFT JOIN departments d ON d.id=i.responsible_department_id LEFT JOIN recommendation_library r ON r.id=i.recommendation_id LEFT JOIN priority_areas pa ON pa.id=i.priority_area_id LEFT JOIN users u ON u.id=i.responsible_user_id LEFT JOIN users au ON au.id=i.approved_by WHERE '.$where.' ORDER BY CASE i.approval_status WHEN "Pending" THEN 1 WHEN "Rejected" THEN 2 ELSE 3 END,i.updated_at DESC');
$s->execute($args);$rows=$s->fetchAll();

if(role()==='HR Administrator'){
    $edit=null;
    if(isset($query['edit'])){$x=$db->prepare('SELECT * FROM interventions WHERE id=?');$x->execute([(int)$query['edit']]);$edit=$x->fetch();}
    $factors=$db->query("SELECT * FROM hrm_factors WHERE status='active' ORDER BY display_order")->fetchAll();
    $recs=$db->query("SELECT * FROM recommendation_library WHERE status='active' ORDER BY title")->fetchAll();
    $depts=$db->query("SELECT * FROM departments WHERE status='active' ORDER BY name")->fetchAll();
    $users=$db->query("SELECT u.id,u.name,r.name role_name,e.department_id FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN employees e ON e.id=u.employee_id WHERE u.status='active' AND r.name IN ('Department Head','HR Administrator') ORDER BY u.name")->fetchAll();
    $priorities=$db->query("SELECT p.id,p.factor_id,p.priority_level,p.action_category,f.name factor_name,ap.title period_title FROM priority_areas p JOIN hrm_factors f ON f.id=p.factor_id JOIN assessment_periods ap ON ap.id=p.period_id WHERE p.status<>'dismissed' ORDER BY p.created_at DESC")->fetchAll();
    echo '<div class="form-card"><h3>'.($edit?'Edit Intervention':'Create Intervention').'</h3><p class="muted">New or edited interventions require Executive approval before implementation progress can be recorded.</p><form method="post">'.csrf_field().'<input type="hidden" name="action" value="save_intervention"><input type="hidden" name="id" value="'.e($edit['id']??'').'"><div class="form-grid">';
    echo '<div class="form-group"><label>Title</label><input name="title" required value="'.e($edit['title']??'').'"></div>';
    echo '<div class="form-group"><label>HRM Factor</label><select name="factor_id">';foreach($factors as $f)echo '<option value="'.$f['id'].'"'.selected($edit['factor_id']??'', $f['id']).'>'.e($f['name']).'</option>';echo '</select></div>';
    echo '<div class="form-group full"><label>Priority Area</label><select name="priority_area_id"><option value="">None / manually initiated</option>';foreach($priorities as $p)echo '<option value="'.$p['id'].'"'.selected($edit['priority_area_id']??'', $p['id']).'>'.e($p['period_title'].' · '.$p['factor_name'].' · '.$p['priority_level'].' / '.$p['action_category']).'</option>';echo '</select></div>';
    echo '<div class="form-group"><label>Recommendation</label><select name="recommendation_id"><option value="">None</option>';foreach($recs as $r)echo '<option value="'.$r['id'].'"'.selected($edit['recommendation_id']??'', $r['id']).'>'.e($r['title']).'</option>';echo '</select></div>';
    echo '<div class="form-group"><label>Budget (PHP)</label><input type="number" step="0.01" min="0" name="budget_amount" value="'.e($edit['budget_amount']??'0').'" required></div>';
    echo '<div class="form-group"><label>Responsible Department</label><select name="responsible_department_id"><option value="">Select</option>';foreach($depts as $d)echo '<option value="'.$d['id'].'"'.selected($edit['responsible_department_id']??'', $d['id']).'>'.e($d['name']).'</option>';echo '</select></div>';
    echo '<div class="form-group"><label>Responsible User</label><select name="responsible_user_id"><option value="">Unassigned</option>';foreach($users as $u)echo '<option value="'.$u['id'].'"'.selected($edit['responsible_user_id']??'', $u['id']).'>'.e($u['name'].' · '.$u['role_name']).'</option>';echo '</select></div>';
    echo '<div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="'.e($edit['start_date']??'').'"></div><div class="form-group"><label>Target Date</label><input type="date" name="target_date" value="'.e($edit['target_date']??'').'"></div>';
    echo '<div class="form-group full"><label>Issue</label><textarea name="issue">'.e($edit['issue']??'').'</textarea></div><div class="form-group full"><label>Description</label><textarea name="description">'.e($edit['description']??'').'</textarea></div><div class="form-group full"><label>Objective</label><textarea name="objective">'.e($edit['objective']??'').'</textarea></div><div class="form-group full"><label>Expected Outcome</label><textarea name="expected_outcome">'.e($edit['expected_outcome']??'').'</textarea></div><div class="form-group full"><label>Internal Notes</label><textarea name="notes">'.e($edit['notes']??'').'</textarea></div>';
    echo '<div class="form-group"><label>Employee Visibility after Approval</label><select name="is_public"><option value="0"'.selected($edit['is_public']??0,0).'>Internal only</option><option value="1"'.selected($edit['is_public']??0,1).'>Publish as HR Action</option></select></div>';
    echo '</div><div class="actions" style="margin-top:12px"><button class="btn btn-primary">'.($edit?'Save & Resubmit':'Create & Submit for Approval').'</button>'.($edit?'<a class="btn btn-secondary" href="?page=interventions">Cancel Edit</a>':'').'</div></form></div>';
}

$approvedBudget=0;$pendingBudget=0;
foreach($rows as $r){if($r['approval_status']==='Approved'&&$r['status']!=='Cancelled')$approvedBudget+=(float)$r['budget_amount'];if($r['approval_status']==='Pending')$pendingBudget+=(float)$r['budget_amount'];}
if(in_array(role(),['HR Administrator','Executive'],true)) echo '<div class="grid kpis" style="margin-top:14px">'.card('Approved Budget','₱'.number_format($approvedBudget,2)).card('Pending Approval Budget','₱'.number_format($pendingBudget,2)).card('Pending Approvals',(string)count(array_filter($rows,fn($x)=>$x['approval_status']==='Pending'))).'</div>';

echo section_title('Intervention Register','Approved interventions may be implemented and monitored.');
echo '<div class="table-wrap"><table><thead><tr><th>Code / Intervention</th><th>Factor / Priority</th><th>Responsible Unit</th><th>Budget</th><th>Approval</th><th>Implementation</th><th>Action</th></tr></thead><tbody>';
foreach($rows as $r){
    echo '<tr><td><strong>'.e($r['code']).' · '.e($r['title']).'</strong><br><span class="muted small">'.e($r['description']).'</span>'.($r['is_public']?'<br>'.badge('Public after approval','info'):'').'</td>';
    echo '<td>'.e($r['factor_name']?:'—').($r['priority_level']?'<br>'.badge($r['priority_level'],'warn'):'').'</td>';
    echo '<td>'.e($r['department']?:'—').'<br><span class="muted small">'.e($r['responsible_user']?:'Unassigned').'</span></td>';
    echo '<td>₱'.number_format((float)$r['budget_amount'],2).'</td>';
    $appTone=$r['approval_status']==='Approved'?'good':($r['approval_status']==='Rejected'?'bad':'warn');
    echo '<td>'.badge($r['approval_status'],$appTone).($r['approved_by_name']?'<br><span class="muted small">'.e($r['approved_by_name']).' · '.e($r['approved_at']).'</span>':'').($r['approval_notes']?'<br><span class="muted small">'.e($r['approval_notes']).'</span>':'').'</td>';
    echo '<td>'.badge($r['status'],$r['status']==='Completed'?'good':($r['status']==='Cancelled'?'neutral':'info')).'<br><span class="muted small">'.e($r['start_date']?:'—').' to '.e($r['target_date']?:'—').'</span></td><td>';
    if(role()==='Executive' && $r['approval_status']==='Pending'){
        echo '<form method="post">'.csrf_field().'<input type="hidden" name="action" value="approve"><input type="hidden" name="intervention_id" value="'.$r['id'].'"><textarea name="approval_notes" placeholder="Approval notes" style="min-height:54px"></textarea><div class="actions" style="margin-top:6px"><button class="btn btn-primary" name="decision" value="Approved">Approve</button><button class="btn btn-danger" name="decision" value="Rejected">Reject</button></div></form>';
    }elseif(role()==='HR Administrator'){
        echo '<a class="btn btn-secondary" href="?page=interventions&edit='.$r['id'].'">Edit</a>';
        if($r['approval_status']==='Approved') echo '<form method="post" style="margin-top:8px">'.csrf_field().'<input type="hidden" name="action" value="update_progress"><input type="hidden" name="intervention_id" value="'.$r['id'].'"><select name="status">';
        if($r['approval_status']==='Approved'){
            foreach(['Approved','In Progress','Completed','On Hold','Cancelled'] as $st)echo '<option'.selected($r['status'],$st).'>'.$st.'</option>';
            echo '</select><textarea name="note" placeholder="Progress note" required style="min-height:54px;margin-top:6px"></textarea><button class="btn btn-secondary" style="margin-top:6px">Add Update</button></form>';
        }
    }elseif(role()==='Department Head' && $r['approval_status']==='Approved'){
        echo '<form method="post">'.csrf_field().'<input type="hidden" name="action" value="update_progress"><input type="hidden" name="intervention_id" value="'.$r['id'].'"><select name="status">';foreach(['Approved','In Progress','Completed','On Hold','Cancelled'] as $st)echo '<option'.selected($r['status'],$st).'>'.$st.'</option>';echo '</select><textarea name="note" placeholder="Progress note" required style="min-height:54px;margin-top:6px"></textarea><button class="btn btn-secondary" style="margin-top:6px">Add Update</button></form>';
    }else echo '<span class="muted">View only</span>';
    echo '</td></tr>';
}
if(!$rows)echo '<tr><td colspan="7" class="empty">No interventions.</td></tr>';
echo '</tbody></table></div>';

