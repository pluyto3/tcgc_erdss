<?php

// Published reference values checked against the owner's final manuscript.
// These are study baselines, never a substitute for current operational records.
return [
    'source' => 'Catipay, J. Full Manuscript FINAL VERSION (September 2026), Chapter 4 Tables 4-10 and Chapter 5', 'label' => 'Institutional research baseline',
    'retention' => 2.821, 'headcount' => 285, 'sample_size' => 190, 'qualitative_sample_size' => 15,
    'model_r' => .632, 'r_squared' => .399, 'adjusted_r_squared' => .336, 'model_p' => '< .001',
    'units' => ['Academic Affairs' => 157, 'Administrative Affairs' => 97, 'Research & Extension' => 13, 'Finance & Resource Generation' => 10, 'Student Life' => 8],
    'factors' => [
        'Employee Engagement' => ['mean' => 2.937, 'beta' => .257, 'p' => '< .001', 'pearson_r' => .286, 'pearson_p' => '< .001', 'correlation_significant' => true],
        'Job Satisfaction' => ['mean' => 2.921, 'beta' => .150, 'p' => '= .016', 'pearson_r' => .181, 'pearson_p' => '= .013', 'correlation_significant' => true],
        'Career Development' => ['mean' => 2.834, 'beta' => .138, 'p' => '= .026', 'pearson_r' => .137, 'pearson_p' => '= .059', 'correlation_significant' => false],
        'Work Environment' => ['mean' => 2.777, 'beta' => .191, 'p' => '= .003', 'pearson_r' => .114, 'pearson_p' => '= .117', 'correlation_significant' => false],
        'Training and Development' => ['mean' => 2.739, 'beta' => .161, 'p' => '= .011', 'pearson_r' => .134, 'pearson_p' => '= .065', 'correlation_significant' => false],
        'Performance Management' => ['mean' => 2.725, 'beta' => .344, 'p' => '< .001', 'pearson_r' => .313, 'pearson_p' => '< .001', 'correlation_significant' => true],
        'Leadership and Management' => ['mean' => 2.709, 'beta' => .266, 'p' => '< .001', 'pearson_r' => .246, 'pearson_p' => '= .001', 'correlation_significant' => true],
        'Compensation and Benefits' => ['mean' => 2.654, 'beta' => .219, 'p' => '< .001', 'pearson_r' => .119, 'pearson_p' => '= .101', 'correlation_significant' => false],
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
