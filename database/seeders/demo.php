<?php

function seed_database(PDO $db, array $config): void
{
    $db->beginTransaction();
    try {
        foreach (['Employee', 'HR Administrator', 'Department Head', 'Executive'] as $r) {
            $db->prepare('INSERT INTO roles(name) VALUES(?)')->execute([$r]);
        }
        foreach ([['Human Resource Management Office', 'HRMO'], ['Academic Affairs', 'AA'], ['Administrative Services', 'AS'], ['Auxiliary Services', 'AUX']] as $d) {
            $db->prepare('INSERT INTO departments(name,code) VALUES(?,?)')->execute($d);
        }
        foreach (['Instructor', 'Administrative Officer', 'HR Officer', 'Department Head', 'Executive Officer', 'Support Staff'] as $p) {
            $db->prepare('INSERT INTO positions(name) VALUES(?)')->execute([$p]);
        }
        foreach (['Academic', 'Administrative', 'Auxiliary'] as $c) {
            $db->prepare('INSERT INTO employee_categories(name) VALUES(?)')->execute([$c]);
        }
        foreach (['Regular', 'Probationary', 'Contractual'] as $t) {
            $db->prepare('INSERT INTO employment_types(name) VALUES(?)')->execute([$t]);
        }

        $deptIds = $db->query('SELECT id FROM departments ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        $posIds = $db->query('SELECT id FROM positions ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        $catIds = $db->query('SELECT id FROM employee_categories ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        $typeIds = $db->query('SELECT id FROM employment_types ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        for ($i = 1; $i <= 30; $i++) {
            $dept = $deptIds[($i - 1) % count($deptIds)];
            $pos = $posIds[($i - 1) % count($posIds)];
            $cat = $catIds[($i - 1) % count($catIds)];
            $type = $typeIds[($i - 1) % count($typeIds)];
            $status = $i >= 29 ? 'separated' : 'active';
            $stmt = $db->prepare('INSERT INTO employees(employee_no,first_name,last_name,email,department_id,position_id,category_id,employment_type_id,date_hired,employment_status) VALUES(?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([sprintf('SYN-%03d', $i), 'Sample', sprintf('Employee %02d', $i), sprintf('sample%02d@tcgc-demo.local', $i), $dept, $pos, $cat, $type, date('Y-m-d', strtotime('-'.(1 + ($i % 12)).' years')), $status]);
        }
        foreach ([29, 30] as $eid) {
            $db->prepare('INSERT INTO employee_separations(employee_id,separation_date,separation_type,notes) VALUES(?,?,?,?)')->execute([$eid, '2026-08-'.($eid === 29 ? '15' : '30'), 'Resignation', 'Synthetic demo separation record']);
        }

        $roles = [];
        foreach ($db->query('SELECT id,name FROM roles') as $r) {
            $roles[$r['name']] = $r['id'];
        }
        $hash = password_hash($config['demo_password'], PASSWORD_DEFAULT);
        $accounts = [
            ['HR Administrator', null, 'HR Administrator', 'hr.admin@tcgc-demo.local'],
            ['Employee', 1, 'Sample Employee', 'employee@tcgc-demo.local'],
            ['Department Head', 4, 'Department Head', 'dept.head@tcgc-demo.local'],
            ['Executive', 5, 'Executive User', 'executive@tcgc-demo.local'],
        ];
        foreach ($accounts as [$r,$eid,$name,$email]) {
            $db->prepare('INSERT INTO users(role_id,employee_id,name,email,password_hash) VALUES(?,?,?,?,?)')->execute([$roles[$r], $eid, $name, $email, $hash]);
        }

        // The outcome measure is stored separately from the eight HRM predictor factors used in the capstone instrument.
        $factors = [
            ['Employee Retention', 'Intention to remain and commitment to continued employment at TCGC', 0, 1],
            ['Compensation and Benefits', 'Compensation fairness, adequacy, understanding and benefits support', 1, 0],
            ['Career Development', 'Professional advancement, responsibility and career-growth opportunities', 2, 0],
            ['Training and Development', 'Job-relevant training and professional development opportunities', 3, 0],
            ['Performance Management', 'Clear expectations, fair evaluation, feedback, improvement and recognition', 4, 0],
            ['Work Environment', 'Safety, resources, respect, communication, workload and working conditions', 5, 0],
            ['Leadership and Management', 'Supervisor support, fairness, communication, listening and recognition', 6, 0],
            ['Employee Engagement', 'Enthusiasm, discretionary effort, involvement, motivation and meaningful work', 7, 0],
            ['Job Satisfaction', 'Overall satisfaction with work, opportunities, recognition and employment conditions', 8, 0],
        ];
        foreach ($factors as $f) {
            $existing = $db->prepare('SELECT id FROM hrm_factors WHERE name=?');
            $existing->execute([$f[0]]);
            if (! $existing->fetchColumn()) {
                $db->prepare('INSERT INTO hrm_factors(name,description,display_order,is_outcome) VALUES(?,?,?,?)')->execute($f);
            }
        }

        $periods = [
            ['2026 Baseline Assessment', 'Synthetic historical assessment using the approved capstone questionnaire', '2026-01-10', '2026-01-31', 'closed'],
            ['2026 Q4 Retention Assessment', 'Current synthetic assessment using the approved capstone questionnaire', '2026-10-01', '2026-12-15', 'active'],
        ];
        foreach ($periods as $p) {
            $db->prepare('INSERT INTO assessment_periods(title,description,start_date,end_date,status) VALUES(?,?,?,?,?)')->execute($p);
        }
        $factorRows = $db->query('SELECT id,name,is_outcome,display_order FROM hrm_factors ORDER BY display_order')->fetchAll();
        $factorByName = [];
        foreach ($factorRows as $f) {
            $factorByName[$f['name']] = $f['id'];
        }

        // Official starting questionnaire transcribed from the approved capstone instrument.
        $questionnaire = [
            'Employee Retention' => [
                'I intend to continue working at TCGC for the foreseeable future.',
                'I plan to remain employed at TCGC as long as suitable opportunities are available to me.',
                'I would recommend TCGC as a good place to work.',
                'I feel committed to continuing my employment with TCGC.',
                'I rarely think about leaving TCGC.',
                'I would prefer to build my career at TCGC rather than seek employment elsewhere.',
                'I expect to still be working at TCGC in the next few years.',
                'I am willing to remain with TCGC even when I encounter challenges in my work.',
            ],
            'Compensation and Benefits' => [
                'I am satisfied with the compensation I receive for the work I perform.',
                'The compensation provided by TCGC is fair in relation to my responsibilities.',
                'TCGC provides benefits that adequately support employees’ needs.',
                'I understand the compensation and benefits provided to employees by TCGC.',
                'TCGC administers compensation and benefits fairly among employees.',
                'The compensation and benefits I receive encourage me to remain with TCGC.',
            ],
            'Career Development' => [
                'TCGC provides opportunities for employees to advance professionally.',
                'Employees are given opportunities to assume greater responsibilities and roles.',
                'Career advancement opportunities are communicated clearly to employees.',
                'TCGC provides opportunities for employees to develop competencies needed for career advancement.',
                'Promotion and advancement decisions are based on fair and appropriate criteria.',
                'I can see opportunities for meaningful career growth within TCGC.',
            ],
            'Training and Development' => [
                'TCGC provides training programs relevant to my job responsibilities.',
                'I am given adequate opportunities to participate in professional development activities.',
                'Training opportunities help me improve my knowledge and skills.',
                'TCGC supports employees who seek professional growth and development.',
                'Training opportunities are distributed fairly among employees.',
                'The training and development opportunities available to me encourage me to remain with TCGC.',
            ],
            'Performance Management' => [
                'My job responsibilities and performance expectations are clearly communicated.',
                'My performance is evaluated using fair and appropriate criteria.',
                'I receive useful feedback regarding my work performance.',
                'Performance evaluation results are used to identify areas for improvement.',
                'Good performance is appropriately recognized by the institution.',
                'The performance management system encourages me to improve my work and remain with TCGC.',
            ],
            'Work Environment' => [
                'TCGC provides a safe and conducive environment for employees to perform their work.',
                'I have adequate resources and facilities to perform my responsibilities effectively.',
                'Employees in my workplace generally treat one another with respect.',
                'Communication within my workplace is generally effective.',
                'My workload is manageable within the available working time and resources.',
                'TCGC provides a work environment that encourages me to remain with the institution.',
            ],
            'Leadership and Management' => [
                'My immediate supervisor communicates expectations clearly.',
                'My supervisor treats employees fairly and respectfully.',
                'My supervisor provides support when I encounter work-related difficulties.',
                'Management listens to employees’ concerns and suggestions.',
                'Management recognizes employees’ contributions to the institution.',
                'The leadership of TCGC contributes positively to my decision to remain with the institution.',
            ],
            'Employee Engagement' => [
                'I feel enthusiastic about the work I perform at TCGC.',
                'I am willing to exert extra effort to help TCGC achieve its objectives.',
                'I feel personally involved in the success of the institution.',
                'I am motivated to perform my responsibilities to the best of my ability.',
                'I feel that my work at TCGC is meaningful.',
                'I feel a strong sense of connection to the work and goals of TCGC.',
            ],
            'Job Satisfaction' => [
                'Overall, I am satisfied with my job at TCGC.',
                'I am satisfied with the nature of the work I perform.',
                'I am satisfied with the opportunities available to me in my current job.',
                'I am satisfied with the recognition I receive for my work.',
                'I am satisfied with the overall conditions of my employment.',
                'My experience working at TCGC generally meets my expectations.',
            ],
        ];
        foreach ([1, 2] as $pid) {
            $order = 1;
            foreach ($questionnaire as $factorName => $questions) {
                $factorId = $factorByName[$factorName];
                foreach ($questions as $q) {
                    $db->prepare('INSERT INTO survey_questions(period_id,factor_id,question_text,display_order) VALUES(?,?,?,?)')->execute([$pid, $factorId, $q, $order++]);
                }
            }
        }

        // Deterministic synthetic responses. Answers are intentionally unlinked to employee identities.
        $factorTargetBaseline = [
            'Employee Retention' => 2.82, 'Compensation and Benefits' => 2.65, 'Career Development' => 2.93, 'Training and Development' => 2.88,
            'Performance Management' => 3.01, 'Work Environment' => 2.84, 'Leadership and Management' => 2.71, 'Employee Engagement' => 2.96, 'Job Satisfaction' => 2.90,
        ];
        $factorTargetCurrent = [
            'Employee Retention' => 2.89, 'Compensation and Benefits' => 2.74, 'Career Development' => 3.00, 'Training and Development' => 2.96,
            'Performance Management' => 3.10, 'Work Environment' => 2.92, 'Leadership and Management' => 2.80, 'Employee Engagement' => 3.04, 'Job Satisfaction' => 2.98,
        ];
        seed_responses($db, 1, 22, $factorTargetBaseline);
        seed_responses($db, 2, 18, $factorTargetCurrent);
        for ($i = 2; $i <= 19; $i++) {
            try {
                $db->prepare('INSERT INTO survey_participation(period_id,employee_id,completed_at) VALUES(2,?,CURRENT_TIMESTAMP)')->execute([$i]);
            } catch (Throwable $ignore) {
            }
        }

        $feedbackCategoryMap = [
            ['Workload', null],
            ['Compensation', 'Compensation and Benefits'],
            ['Benefits', 'Compensation and Benefits'],
            ['Leadership', 'Leadership and Management'],
            ['Training', 'Training and Development'],
            ['Career Growth', 'Career Development'],
            ['Recognition', 'Job Satisfaction'],
            ['Workplace Environment', 'Work Environment'],
            ['Communication', 'Leadership and Management'],
            ['Payroll', 'Compensation and Benefits'],
            ['Other', null],
        ];
        foreach ($feedbackCategoryMap as [$name,$factorName]) {
            $factorId = $factorName ? ($factorByName[$factorName] ?? null) : null;
            $db->prepare('INSERT INTO feedback_categories(name,factor_id) VALUES(?,?)')->execute([$name, $factorId]);
        }
        foreach (['Administrative workload', 'Payroll timing', 'Career development', 'Leadership communication', 'Recognition', 'Benefits awareness'] as $x) {
            $db->prepare('INSERT INTO feedback_themes(name) VALUES(?)')->execute([$x]);
        }
        $feedbacks = [
            [2, 1, 'There is still a heavy amount of non-teaching paperwork that affects core work.', 1, null, 2],
            [2, 10, 'Clearer payroll release schedules would reduce uncertainty.', 1, null, 3],
            [2, 6, 'More structured career development opportunities would be helpful.', 1, null, 2],
            [2, 4, 'Regular consultation with supervisors would improve communication.', 1, null, 3],
            [2, 7, 'Recognition for additional responsibilities would improve morale.', 1, null, 4],
        ];
        foreach ($feedbacks as $idx => $f) {
            $db->prepare('INSERT INTO feedback(period_id,category_id,body,is_anonymous,submitter_employee_id,snapshot_department_id,review_status) VALUES(?,?,?,?,?,?,?)')->execute([$f[0], $f[1], $f[2], $f[3], $f[4], $f[5], 'reviewed']);
            $fid = (int) $db->lastInsertId();
            $db->prepare('INSERT INTO feedback_theme_links(feedback_id,theme_id) VALUES(?,?)')->execute([$fid, $idx + 1]);
        }

        $db->prepare('INSERT INTO research_baselines(factor_id,metric_key,value,source,study_label) VALUES(NULL,?,?,?,?)')->execute(['overall_retention_mean', 2.821, 'MBA Capstone baseline', 'Baseline Study']);
        $db->prepare('INSERT INTO research_baselines(factor_id,metric_key,value,source,study_label) VALUES(?,?,?,?,?)')->execute([$factorByName['Compensation and Benefits'], 'factor_mean', 2.654, 'MBA Capstone baseline', 'Baseline Study']);
        $db->prepare('INSERT INTO research_baselines(factor_id,metric_key,value,source,study_label) VALUES(?,?,?,?,?)')->execute([$factorByName['Leadership and Management'], 'factor_mean', 2.709, 'MBA Capstone baseline', 'Baseline Study']);
        $db->prepare('INSERT INTO retention_driver_models(name,source,is_baseline,valid_from,notes) VALUES(?,?,?,?,?)')->execute(['Baseline Multiple Regression Model', 'MBA Capstone Study', 1, '2026-01-01', 'Research-based coefficients; not individual resignation predictions.']);
        $modelId = (int) $db->lastInsertId();
        foreach ([
            'Performance Management' => 0.344, 'Leadership and Management' => 0.266, 'Employee Engagement' => 0.257, 'Compensation and Benefits' => 0.219,
        ] as $name => $coef) {
            $db->prepare('INSERT INTO retention_driver_coefficients(model_id,factor_id,coefficient) VALUES(?,?,?)')->execute([$modelId, $factorByName[$name], $coef]);
        }

        foreach ([['Monitor', 'Continue monitoring the factor and trend.'], ['Improve', 'Prioritize corrective or strengthening actions.'], ['Strengthen', 'Maintain and reinforce positive practices.']] as $a) {
            $db->prepare('INSERT INTO action_categories(name,description) VALUES(?,?)')->execute($a);
        }
        $db->prepare('INSERT INTO decision_rules(name,threshold_low,threshold_high,stability_band,resulting_category,is_prototype) VALUES(?,?,?,?,?,0)')->execute(['Client-approved management action thresholds', 2.51, 3.26, 0.10, 'Approved']);

        $recs = [
            ['Benefits Orientation', 'Improve employee understanding of available benefits.', 'Compensation and Benefits'],
            ['Payroll Communication Review', 'Clarify payroll timing and communication procedures.', 'Compensation and Benefits'],
            ['Leadership Development Training', 'Provide structured leadership and supervisor development.', 'Leadership and Management'],
            ['Structured Performance Feedback', 'Schedule regular constructive performance feedback.', 'Performance Management'],
            ['Employee Recognition Initiative', 'Create consistent mechanisms for recognizing contributions and strengthen job satisfaction.', 'Job Satisfaction'],
            ['Career Development Plan', 'Establish visible training and career development pathways.', 'Career Development'],
            ['Work Environment Review', 'Review workload, resources, safety and communication conditions.', 'Work Environment'],
            ['Training Access Plan', 'Provide equitable access to job-relevant training and professional development.', 'Training and Development'],
        ];
        foreach ($recs as $r) {
            $db->prepare('INSERT INTO recommendation_library(title,description) VALUES(?,?)')->execute([$r[0], $r[1]]);
            $rid = (int) $db->lastInsertId();
            $db->prepare('INSERT INTO recommendation_factors(recommendation_id,factor_id) VALUES(?,?)')->execute([$rid, $factorByName[$r[2]]]);
        }

        $db->prepare('INSERT INTO interventions(code,title,factor_id,recommendation_id,issue,description,objective,responsible_department_id,responsible_user_id,start_date,target_date,budget_amount,expected_outcome,status,approval_status,approved_by,approved_at,approval_notes,is_public) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP,?,?)')->execute([
            'INT-2026-001', 'Employee Benefits Orientation', $factorByName['Compensation and Benefits'], 1, 'Benefits awareness and compensation concerns', 'Conduct a structured orientation on benefits and payroll information.', 'Improve clarity and employee understanding.', 1, 1, '2026-10-15', '2026-11-30', 3000, 'Improved compensation/benefits perception', 'In Progress', 'Approved', 4, 'Approved for demonstration of the finalized workflow.', 1,
        ]);
        $intId = (int) $db->lastInsertId();
        $db->prepare('INSERT INTO intervention_updates(intervention_id,user_id,status,note) VALUES(?,?,?,?)')->execute([$intId, 1, 'In Progress', 'Orientation materials prepared; schedule being coordinated.']);

        $settings = [
            ['institution_name', 'Tangub City Global College'],
            ['system_name', 'TCGC Employee Retention Decision Support System'],
            ['dashboard_title', 'HRM Analytics & Decision Support Dashboard'],
            ['minimum_group_size', '5'],
            ['session_timeout', '60'],
            ['allow_named_feedback', '1'],
            ['prototype_mode', '0'],
            ['demo_mode', '1'],
            ['default_scale', '4-point Likert'],
            ['instrument_interpretation', '1.00–1.75 Very Low|1.76–2.50 Low|2.51–3.25 High|3.26–4.00 Very High'],
            ['assessment_frequency', 'Semestral'],
            ['client_validation_status', 'Approved'],
            ['system_version', '1.0 Final'],
            ['primary_color', '#173a5e'],
            ['accent_color', '#2f6f98'],
            ['report_footer', 'TCGC Employee Retention Decision Support System'],
            ['logo_path', ''],
        ];
        foreach ($settings as $s) {
            $existing = $db->prepare('SELECT id FROM system_settings WHERE key_name=?');
            $existing->execute([$s[0]]);
            if ($existing->fetchColumn()) {
                $db->prepare('UPDATE system_settings SET value=?,updated_by=1 WHERE key_name=?')->execute([$s[1], $s[0]]);
            } else {
                $db->prepare('INSERT INTO system_settings(key_name,value,updated_by) VALUES(?,?,1)')->execute([$s[0], $s[1]]);
            }
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function seed_responses(PDO $db, int $periodId, int $count, array $targets): void
{
    $questions = $db->prepare('SELECT q.id,q.factor_id,f.name factor_name FROM survey_questions q JOIN hrm_factors f ON f.id=q.factor_id WHERE q.period_id=? ORDER BY f.display_order,q.display_order');
    $questions->execute([$periodId]);
    $qs = $questions->fetchAll();
    for ($r = 1; $r <= $count; $r++) {
        $dept = (($r - 1) % 4) + 1;
        $cat = (($r - 1) % 3) + 1;
        $type = (($r - 1) % 3) + 1;
        $db->prepare('INSERT INTO survey_responses(period_id,snapshot_department_id,snapshot_category_id,snapshot_employment_type_id) VALUES(?,?,?,?)')->execute([$periodId, $dept, $cat, $type]);
        $responseId = (int) $db->lastInsertId();
        foreach ($qs as $q) {
            $target = (float) ($targets[$q['factor_name']] ?? 3.0);
            $base = (int) floor($target);
            $frac = $target - $base;
            $cycle = ($r + (int) $q['id'] * 3) % 100;
            $val = $cycle < (int) round($frac * 100) ? min(4, $base + 1) : max(1, $base);
            if (($r + (int) $q['id']) % 31 === 0) {
                $val = max(1, $val - 1);
            }
            if (($r + (int) $q['id']) % 37 === 0) {
                $val = min(4, $val + 1);
            }
            $db->prepare('INSERT INTO survey_response_items(response_id,question_id,response_value) VALUES(?,?,?)')->execute([$responseId, $q['id'], $val]);
        }
    }
}
