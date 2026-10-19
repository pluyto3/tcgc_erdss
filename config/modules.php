<?php

$hr = ['HR Administrator'];
$leaders = ['HR Administrator', 'Executive'];
$management = ['HR Administrator', 'Department Head', 'Executive'];
$everyone = ['Employee', ...$management];

return [
    'dashboard' => ['title' => 'Retention overview', 'group' => 'Workspace', 'icon' => 'dashboard', 'roles' => $everyone],
    'analytics' => ['title' => 'HRM analytics', 'group' => 'Workspace', 'icon' => 'chart', 'roles' => $management],
    'drivers' => ['title' => 'Predictive insights', 'group' => 'Workspace', 'icon' => 'spark', 'roles' => $leaders],
    'employee_perspective' => ['title' => 'Employee experience', 'group' => 'Workspace', 'icon' => 'message', 'roles' => $leaders],
    'priorities' => ['title' => 'Priorities & actions', 'group' => 'Workspace', 'icon' => 'target', 'roles' => $leaders, 'write_roles' => $hr],
    'monitoring' => ['title' => 'Monitoring & evaluation', 'group' => 'Workspace', 'icon' => 'activity', 'roles' => $leaders, 'write_roles' => $hr],
    'reports' => ['title' => 'Reports', 'group' => 'Workspace', 'icon' => 'report', 'roles' => $management],
    'employees' => ['title' => 'Employees', 'group' => 'Management', 'icon' => 'users', 'roles' => $hr, 'write_roles' => $hr],
    'assessments' => ['title' => 'Assessments', 'group' => 'Management', 'icon' => 'clipboard', 'roles' => $hr, 'write_roles' => $hr],
    'feedback' => ['title' => 'Feedback review', 'group' => 'Management', 'icon' => 'message', 'roles' => $hr, 'write_roles' => $hr],
    'interventions' => ['title' => 'Interventions', 'group' => 'Management', 'icon' => 'layers', 'roles' => $management, 'write_roles' => $management],
    'recommendations' => ['title' => 'Recommendations', 'group' => 'Management', 'icon' => 'bulb', 'roles' => $management, 'write_roles' => $hr],
    'master_data' => ['title' => 'Organization setup', 'group' => 'Administration', 'icon' => 'building', 'roles' => $hr, 'write_roles' => $hr],
    'factors' => ['title' => 'HRM factors', 'group' => 'Administration', 'icon' => 'sliders', 'roles' => $hr, 'write_roles' => $hr],
    'users' => ['title' => 'User management', 'group' => 'Administration', 'icon' => 'shield', 'roles' => $hr, 'write_roles' => $hr],
    'audit' => ['title' => 'Audit log', 'group' => 'Administration', 'icon' => 'history', 'roles' => $hr],
    'settings' => ['title' => 'Settings', 'group' => 'Administration', 'icon' => 'settings', 'roles' => $hr, 'write_roles' => $hr],
    'my_assessments' => ['title' => 'My assessments', 'group' => 'Workspace', 'icon' => 'clipboard', 'roles' => ['Employee'], 'write_roles' => ['Employee']],
    'submit_feedback' => ['title' => 'Share feedback', 'group' => 'Workspace', 'icon' => 'message', 'roles' => ['Employee'], 'write_roles' => ['Employee']],
    'org_results' => ['title' => 'Organizational results', 'group' => 'Workspace', 'icon' => 'chart', 'roles' => ['Employee']],
    'hr_actions' => ['title' => 'HR actions', 'group' => 'Workspace', 'icon' => 'layers', 'roles' => ['Employee']],
    'profile' => ['title' => 'My profile', 'group' => 'Account', 'icon' => 'user', 'roles' => $everyone],
    'privacy' => ['title' => 'Privacy', 'group' => 'Account', 'icon' => 'shield', 'roles' => ['Employee']],
];
