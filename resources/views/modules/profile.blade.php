<?php
 $u=current_user();
echo '<div class="card"><h3>Account</h3><div class="stat-row"><span>Name</span><strong>'.e($u['name']).'</strong></div><div class="stat-row"><span>Email</span><strong>'.e($u['email']).'</strong></div><div class="stat-row"><span>Role</span><strong>'.e($u['role']).'</strong></div></div>';

