<?php

$edit=null;if(isset($query['edit'])){$s=$db->prepare('SELECT * FROM employees WHERE id=?');$s->execute([(int)$query['edit']]);$edit=$s->fetch();}
$masters=[
    'departments'=>$db->query("SELECT * FROM departments WHERE status='active' ORDER BY name")->fetchAll(),
    'positions'=>$db->query("SELECT * FROM positions WHERE status='active' ORDER BY name")->fetchAll(),
    'categories'=>$db->query("SELECT * FROM employee_categories WHERE status='active' ORDER BY name")->fetchAll(),
    'types'=>$db->query("SELECT * FROM employment_types WHERE status='active' ORDER BY name")->fetchAll()
];

if(isset($query['add'])||$edit){
    echo '<div class="form-card"><h2>'.($edit?'Edit Employee':'Add Employee').'</h2><form method="post">'.csrf_field().'<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="'.e($edit['id']??'').'"><div class="form-grid">';
    $fields=[['Employee No','employee_no',$edit['employee_no']??'',true,'text'],['First Name','first_name',$edit['first_name']??'',true,'text'],['Middle Name','middle_name',$edit['middle_name']??'',false,'text'],['Last Name','last_name',$edit['last_name']??'',true,'text'],['Email','email',$edit['email']??'',false,'email'],['Date Hired','date_hired',$edit['date_hired']??'',false,'date']];
    foreach($fields as $f)echo '<div class="form-group"><label>'.e($f[0]).'</label><input '.($f[3]?'required ':'').'type="'.e($f[4]).'" name="'.e($f[1]).'" value="'.e($f[2]).'"></div>';
    foreach([['Department','department_id','departments'],['Position','position_id','positions'],['Category','category_id','categories'],['Employment Type','employment_type_id','types']] as $f){echo '<div class="form-group"><label>'.e($f[0]).'</label><select name="'.e($f[1]).'" required><option value="">Select</option>';foreach($masters[$f[2]] as $m)echo '<option value="'.$m['id'].'"'.selected($edit[$f[1]]??'',$m['id']).'>'.e($m['name']).'</option>';echo '</select></div>';}
    echo '<div class="form-group"><label>Status</label><select name="employment_status"><option value="active"'.selected($edit['employment_status']??'active','active').'>Active</option><option value="separated"'.selected($edit['employment_status']??'','separated').'>Separated</option></select></div></div><div class="actions" style="margin-top:14px"><button class="btn btn-primary">Save</button><a class="btn btn-secondary" href="?page=employees">Cancel</a></div></form></div>';return;
}

$q=trim($query['q']??'');$filterDept=(int)($query['department_id']??0);$filterStatus=$query['status']??'';$pageNo=max(1,(int)($query['p']??1));$perPage=10;$offset=($pageNo-1)*$perPage;
$where=['e.archived_at IS NULL'];$args=[];
if($q!==''){$where[]='(e.employee_no LIKE ? OR e.first_name LIKE ? OR e.middle_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ?)';for($i=0;$i<5;$i++)$args[]='%'.$q.'%';}
if($filterDept){$where[]='e.department_id=?';$args[]=$filterDept;}
if(in_array($filterStatus,['active','separated'],true)){$where[]='e.employment_status=?';$args[]=$filterStatus;}
$whereSql=implode(' AND ',$where);
$c=$db->prepare('SELECT COUNT(*) FROM employees e WHERE '.$whereSql);$c->execute($args);$total=(int)$c->fetchColumn();$pages=max(1,(int)ceil($total/$perPage));if($pageNo>$pages){$pageNo=$pages;$offset=($pageNo-1)*$perPage;}
$sql='SELECT e.*,d.name department,p.name position,c.name category,t.name employment_type FROM employees e LEFT JOIN departments d ON d.id=e.department_id LEFT JOIN positions p ON p.id=e.position_id LEFT JOIN employee_categories c ON c.id=e.category_id LEFT JOIN employment_types t ON t.id=e.employment_type_id WHERE '.$whereSql.' ORDER BY e.last_name,e.first_name LIMIT '.$perPage.' OFFSET '.$offset;
$s=$db->prepare($sql);$s->execute($args);$rows=$s->fetchAll();

echo '<div class="actions no-print" style="justify-content:space-between;margin-bottom:12px"><form method="get" class="actions" style="flex-wrap:wrap"><input type="hidden" name="page" value="employees"><input name="q" placeholder="Search employees" value="'.e($q).'" style="width:220px"><select name="department_id"><option value="0">All departments</option>';foreach($masters['departments'] as $d)echo '<option value="'.$d['id'].'"'.selected($filterDept,$d['id']).'>'.e($d['name']).'</option>';echo '</select><select name="status"><option value="">All statuses</option><option value="active"'.selected($filterStatus,'active').'>Active</option><option value="separated"'.selected($filterStatus,'separated').'>Separated</option></select><button class="btn btn-secondary">Filter</button></form><div class="actions"><a class="btn btn-secondary" href="?page=employees&template=csv">CSV Template</a><a class="btn btn-primary" href="?page=employees&add=1">Add Employee</a></div></div>';
echo '<div class="form-card no-print" style="margin-bottom:14px"><h3>Import Historical Employee Records</h3><form method="post" enctype="multipart/form-data" class="actions">'.csrf_field().'<input type="hidden" name="action" value="import_csv"><input type="file" name="employee_csv" accept=".csv,text/csv" required><button class="btn btn-secondary">Import CSV</button><span class="muted small">Master-data names in the CSV must match active departments, positions, categories, and employment types.</span></form></div>';
echo '<div class="table-wrap"><table><thead><tr><th>No.</th><th>Employee</th><th>Department</th><th>Category</th><th>Type</th><th>Date Hired</th><th>Status</th><th></th></tr></thead><tbody>';
foreach($rows as $r){
    echo '<tr><td>'.e($r['employee_no']).'</td><td><strong>'.e($r['last_name'].', '.$r['first_name']).'</strong><br><span class="muted small">'.e($r['position']).($r['email']?'<br>'.e($r['email']):'').'</span></td><td>'.e($r['department']).'</td><td>'.e($r['category']).'</td><td>'.e($r['employment_type']).'</td><td>'.e($r['date_hired']?:'—').'</td><td>'.badge($r['employment_status'],$r['employment_status']==='active'?'good':'neutral').'</td><td><div class="actions"><a class="btn btn-secondary" href="?page=employees&edit='.$r['id'].'">Edit</a>';if($r['employment_status']==='active')echo '<a class="btn btn-secondary" href="?page=employees&separate='.$r['id'].'">Separate</a>';echo '<form method="post" onsubmit="return confirm(\'Archive this record?\')">'.csrf_field().'<input type="hidden" name="action" value="archive"><input type="hidden" name="id" value="'.$r['id'].'"><button class="btn btn-danger">Archive</button></form></div></td></tr>';
}
if(!$rows)echo '<tr><td colspan="8" class="empty">No employees match the current filters.</td></tr>';
echo '</tbody></table></div>';
if($pages>1){echo '<div class="actions" style="justify-content:center;margin-top:12px">';for($i=1;$i<=$pages;$i++){echo '<a class="btn '.($i===$pageNo?'btn-primary':'btn-secondary').'" href="'.e(query_url(['p'=>$i])).'">'.$i.'</a>';}echo '</div>';}

if(isset($query['separate'])){
    $id=(int)$query['separate'];$x=$db->prepare('SELECT employee_no,first_name,last_name FROM employees WHERE id=?');$x->execute([$id]);$emp=$x->fetch();
    if($emp)echo '<div class="form-card" style="margin-top:14px"><h3>Record Separation — '.e($emp['employee_no'].' · '.$emp['last_name'].', '.$emp['first_name']).'</h3><form method="post">'.csrf_field().'<input type="hidden" name="action" value="separate"><input type="hidden" name="id" value="'.$id.'"><div class="form-grid"><div class="form-group"><label>Separation Date</label><input type="date" name="separation_date" required></div><div class="form-group"><label>Type</label><select name="separation_type"><option>Resignation</option><option>Retirement</option><option>End of Contract</option><option>Other</option></select></div><div class="form-group full"><label>Notes</label><textarea name="notes"></textarea></div></div><button class="btn btn-primary">Save Separation</button></form></div>';
}

