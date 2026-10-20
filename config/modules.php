<?php

$hr = ['HR Administrator'];
$leaders = ['HR Administrator', 'Executive'];
$management = ['HR Administrator', 'Department Head', 'Executive'];
$everyone = ['Employee', ...$management];

return [
    'dashboard' => ['title' => 'Retention overview', 'group' => 'Workspace', 'icon' => 'dashboard', 'roles' => $everyone],
    'analytics' => ['title' => 'HRM analytics', 'description' => 'Explore assessment scores and compare human resource management factors.', 'group' => 'Workspace', 'icon' => 'chart', 'roles' => $management],
    'drivers' => ['title' => 'Predictive insights', 'group' => 'Workspace', 'icon' => 'spark', 'roles' => $leaders],
    'employee_perspective' => ['title' => 'Employee experience', 'group' => 'Workspace', 'icon' => 'message', 'roles' => $leaders],
    'priorities' => ['title' => 'Priorities & actions', 'group' => 'Workspace', 'icon' => 'target', 'roles' => $leaders, 'write_roles' => $hr],
    'monitoring' => ['title' => 'Monitoring & evaluation', 'description' => 'Compare results across assessment periods and review intervention evaluations.', 'group' => 'Workspace', 'icon' => 'activity', 'roles' => $leaders, 'write_roles' => $hr],
    'reports' => ['title' => 'Reports', 'group' => 'Workspace', 'icon' => 'report', 'roles' => $management],
    'employees' => ['title' => 'Employees', 'description' => 'Manage employee records, import historical data, and record employment changes.', 'group' => 'Management', 'icon' => 'users', 'roles' => $hr, 'write_roles' => $hr],
    'assessments' => ['title' => 'Assessments', 'description' => 'Create assessment periods, manage questions, and track employee responses.', 'group' => 'Management', 'icon' => 'clipboard', 'roles' => $hr, 'write_roles' => $hr],
    'feedback' => ['title' => 'Feedback review', 'description' => 'Review employee feedback and organize comments by theme.', 'group' => 'Management', 'icon' => 'message', 'roles' => $hr, 'write_roles' => $hr],
    'interventions' => ['title' => 'Interventions', 'description' => 'Review retention actions, approval decisions, and progress updates.', 'group' => 'Management', 'icon' => 'layers', 'roles' => $management, 'write_roles' => $management],
    'recommendations' => ['title' => 'Recommendations', 'description' => 'Explore recommended actions for each human resource management factor.', 'group' => 'Management', 'icon' => 'bulb', 'roles' => $management, 'write_roles' => $hr],
    'master_data' => ['title' => 'Organization setup', 'description' => 'Maintain departments, positions, employee categories, and employment types.', 'group' => 'Administration', 'icon' => 'building', 'roles' => $hr, 'write_roles' => $hr],
    'factors' => ['title' => 'HRM factors', 'description' => 'Manage the human resource management factors used in assessments and reports.', 'group' => 'Administration', 'icon' => 'sliders', 'roles' => $hr, 'write_roles' => $hr],
    'users' => ['title' => 'User management', 'description' => 'Manage user accounts, assign roles, and link accounts to employee records.', 'group' => 'Administration', 'icon' => 'shield', 'roles' => $hr, 'write_roles' => $hr],
    'audit' => ['title' => 'Audit log', 'description' => 'Review recorded system activity and changes made by users.', 'group' => 'Administration', 'icon' => 'history', 'roles' => $hr],
    'settings' => ['title' => 'Settings', 'description' => 'Update institution details, branding, privacy settings, and decision thresholds.', 'group' => 'Administration', 'icon' => 'settings', 'roles' => $hr, 'write_roles' => $hr],
    'my_assessments' => ['title' => 'My assessments', 'description' => 'View available assessments and share your workplace experience.', 'group' => 'Workspace', 'icon' => 'clipboard', 'roles' => ['Employee'], 'write_roles' => ['Employee']],
    'submit_feedback' => ['title' => 'Share feedback', 'description' => 'Share comments and suggestions about your workplace experience.', 'group' => 'Workspace', 'icon' => 'message', 'roles' => ['Employee'], 'write_roles' => ['Employee']],
    'org_results' => ['title' => 'Organizational results', 'description' => 'Explore assessment results summarized across the institution.', 'group' => 'Workspace', 'icon' => 'chart', 'roles' => ['Employee']],
    'hr_actions' => ['title' => 'HR actions', 'description' => 'See retention actions approved for sharing with employees.', 'group' => 'Workspace', 'icon' => 'layers', 'roles' => ['Employee']],
    'profile' => ['title' => 'My profile', 'description' => 'Review your account name, email address, and assigned role.', 'group' => 'Account', 'icon' => 'user', 'roles' => $everyone],
    'privacy' => ['title' => 'Privacy', 'description' => 'Learn how assessment responses and feedback are handled.', 'group' => 'Account', 'icon' => 'shield', 'roles' => ['Employee']],
];
