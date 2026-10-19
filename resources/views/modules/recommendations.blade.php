<?php

$edit=null;if(role()==='HR Administrator'&&isset($query['edit'])){$s=$db->prepare('SELECT rl.*,rf.factor_id FROM recommendation_library rl LEFT JOIN recommendation_factors rf ON rf.recommendation_id=rl.id WHERE rl.id=? LIMIT 1');$s->execute([(int)$query['edit']]);$edit=$s->fetch();}
echo '<div class="grid '.(role()==='HR Administrator'?'two':'').'">';
echo '<div class="table-wrap"><table><thead><tr><th>Recommendation</th><th>Factor</th><th>Status</th>'.(role()==='HR Administrator'?'<th></th>':'').'</tr></thead><tbody>';
foreach($rows as $r){echo '<tr><td><strong>'.e($r['title']).'</strong><br><span class="muted small">'.e($r['description']).'</span></td><td>'.e($r['factors']).'</td><td>'.badge($r['status'],$r['status']==='active'?'good':'neutral').'</td>'.(role()==='HR Administrator'?'<td><a class="btn btn-secondary" href="?page=recommendations&edit='.$r['id'].'">Edit</a></td>':'').'</tr>';}
echo '</tbody></table></div>';
if(role()==='HR Administrator'){
    $factors=$db->query("SELECT * FROM hrm_factors WHERE status='active' ORDER BY display_order")->fetchAll();
    echo '<div class="form-card"><h3>'.($edit?'Edit Recommendation':'Add Recommendation').'</h3><form method="post">'.csrf_field().'<input type="hidden" name="id" value="'.e($edit['id']??'').'"><div class="form-group"><label>Title</label><input name="title" required value="'.e($edit['title']??'').'"></div><div class="form-group" style="margin-top:10px"><label>Description</label><textarea name="description">'.e($edit['description']??'').'</textarea></div><div class="form-group" style="margin-top:10px"><label>HRM Factor</label><select name="factor_id">';foreach($factors as $f)echo '<option value="'.$f['id'].'"'.selected($edit['factor_id']??'', $f['id']).'>'.e($f['name']).'</option>';echo '</select></div><div class="form-group" style="margin-top:10px"><label>Status</label><select name="status"><option value="active"'.selected($edit['status']??'active','active').'>Active</option><option value="inactive"'.selected($edit['status']??'active','inactive').'>Inactive</option></select></div><div class="actions" style="margin-top:12px"><button class="btn btn-primary">Save Recommendation</button>'.($edit?'<a class="btn btn-secondary" href="?page=recommendations">Cancel</a>':'').'</div></form></div>';
}
echo '</div>';
