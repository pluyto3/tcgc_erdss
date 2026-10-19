<?php
$rows=$db->query("SELECT i.*,f.name factor_name FROM interventions i LEFT JOIN hrm_factors f ON f.id=i.factor_id WHERE i.is_public=1 AND i.approval_status='Approved' AND i.status<>'Cancelled' ORDER BY i.updated_at DESC")->fetchAll();
echo '<div class="notice">You said → Management acted. Only interventions approved for public employee visibility appear here.</div><div class="grid two" style="margin-top:14px">';foreach($rows as $r)echo '<div class="card"><div class="eyebrow">'.e($r['factor_name']??'HR Action').'</div><h3>'.e($r['title']).'</h3><p>'.e($r['description']).'</p>'.badge($r['status'],$r['status']==='Completed'?'good':'info').'</div>';if(!$rows)echo '<div class="empty">No public HR actions yet.</div>';echo '</div>';

