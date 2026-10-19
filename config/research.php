<?php

// Reference values transcribed from the feature document supplied by the project owner.
// These are study baselines, never a substitute for current operational records.
return [
    'source' => 'FEATURES IN DASHBOARD.docx', 'label' => 'Institutional research baseline',
    'retention' => 2.82, 'headcount' => 285, 'r_squared' => .399, 'model_p' => '< .001',
    'units' => ['Academic Affairs' => 157, 'Administrative Affairs' => 97, 'Research & Extension' => 13, 'Finance & Resource Generation' => 10, 'Student Life' => 8],
    'factors' => [
        'Employee Engagement' => ['mean' => 2.937, 'beta' => .257, 'p' => '< .001'],
        'Job Satisfaction' => ['mean' => 2.921, 'beta' => .150, 'p' => '= .016'],
        'Career Development' => ['mean' => 2.834, 'beta' => .138, 'p' => '= .026'],
        'Work Environment' => ['mean' => 2.777, 'beta' => .191, 'p' => '= .003'],
        'Training and Development' => ['mean' => 2.739, 'beta' => .161, 'p' => '= .011'],
        'Performance Management' => ['mean' => 2.725, 'beta' => .344, 'p' => '< .001'],
        'Leadership and Management' => ['mean' => 2.709, 'beta' => .266, 'p' => '< .001'],
        'Compensation and Benefits' => ['mean' => 2.654, 'beta' => .219, 'p' => '< .001'],
    ],
    'themes' => [
        ['title' => 'Fair compensation & employment security', 'detail' => 'Clear regularization criteria, reliable payroll, and transparent benefits.', 'icon' => 'wallet', 'tag' => 'Stability'],
        ['title' => 'Professional growth & career opportunities', 'detail' => 'Visible advancement paths and equitable access to seminars and training.', 'icon' => 'chart', 'tag' => 'Growth'],
        ['title' => 'Recognition, appreciation & employee voice', 'detail' => 'Consistent recognition and meaningful opportunities to be heard.', 'icon' => 'star', 'tag' => 'Belonging'],
        ['title' => 'Supportive leadership & relationships', 'detail' => 'Approachable supervisors, fair decisions, and supportive colleagues.', 'icon' => 'users', 'tag' => 'Leadership'],
        ['title' => 'Healthy & comfortable work environment', 'detail' => 'Respectful relationships, adequate resources, and a comfortable workplace.', 'icon' => 'building', 'tag' => 'Wellbeing'],
        ['title' => 'Manageable workload & administrative burden', 'detail' => 'Reduce non-teaching paperwork so faculty can focus on instruction.', 'icon' => 'clipboard', 'tag' => 'Workload'],
        ['title' => 'Effective communication & administrative support', 'detail' => 'Timely information, clear processes, and responsive administrative services.', 'icon' => 'message', 'tag' => 'Communication'],
    ],
    'tiers' => [
        ['title' => 'Strengthen the core drivers', 'subtitle' => 'Tier 1 · Core drivers', 'description' => 'Improve performance feedback, recognition programs, and leadership communication.', 'owner' => 'HR Office & unit heads', 'tone' => 'purple'],
        ['title' => 'Make room for meaningful work', 'subtitle' => 'Tier 2 · Workload & processes', 'description' => 'Rebalance administrative paperwork and simplify non-teaching tasks for faculty.', 'owner' => 'Academic & administrative units', 'tone' => 'blue'],
        ['title' => 'Protect stability and growth', 'subtitle' => 'Tier 3 · Stability & growth', 'description' => 'Clarify compensation policies, regularization criteria, and professional development paths.', 'owner' => 'HR Office & executive leadership', 'tone' => 'teal'],
    ],
];
