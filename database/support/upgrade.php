<?php

function table_columns(PDO $db, string $table): array
{
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $rows = $db->query('PRAGMA table_info('.$table.')')->fetchAll();

        return array_column($rows, 'name');
    }
    $s = $db->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $s->execute([$table]);

    return $s->fetchAll(PDO::FETCH_COLUMN);
}

function apply_schema_upgrades(PDO $db): void
{
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $factorCols = table_columns($db, 'hrm_factors');
    if (! in_array('is_outcome', $factorCols, true)) {
        $db->exec($driver === 'sqlite'
            ? 'ALTER TABLE hrm_factors ADD COLUMN is_outcome INTEGER DEFAULT 0'
            : 'ALTER TABLE hrm_factors ADD COLUMN is_outcome TINYINT(1) DEFAULT 0');
    }
    $feedbackCols = table_columns($db, 'feedback_categories');
    if (! in_array('factor_id', $feedbackCols, true)) {
        $db->exec($driver === 'sqlite'
            ? 'ALTER TABLE feedback_categories ADD COLUMN factor_id INTEGER NULL REFERENCES hrm_factors(id)'
            : 'ALTER TABLE feedback_categories ADD COLUMN factor_id BIGINT UNSIGNED NULL, ADD CONSTRAINT fk_fbcat_factor FOREIGN KEY (factor_id) REFERENCES hrm_factors(id)');
    }
    $cols = table_columns($db, 'interventions');
    $adds = [
        'budget_amount' => $driver === 'sqlite' ? 'REAL DEFAULT 0' : 'DECIMAL(12,2) DEFAULT 0',
        'approval_status' => $driver === 'sqlite' ? "TEXT DEFAULT 'Pending'" : "VARCHAR(50) DEFAULT 'Pending'",
        'approved_by' => $driver === 'sqlite' ? 'INTEGER NULL' : 'BIGINT UNSIGNED NULL',
        'approved_at' => $driver === 'sqlite' ? 'TEXT NULL' : 'DATETIME NULL',
        'approval_notes' => $driver === 'sqlite' ? 'TEXT NULL' : 'TEXT NULL',
    ];
    foreach ($adds as $name => $type) {
        if (! in_array($name, $cols, true)) {
            $db->exec("ALTER TABLE interventions ADD COLUMN $name $type");
        }
    }
    // Align upgraded installations with the validated capstone measure names.
    $factorRenames = [
        'Career Growth' => 'Career Development',
        'Workplace Conditions' => 'Work Environment',
        'Recognition' => 'Job Satisfaction',
    ];
    foreach ($factorRenames as $old => $new) {
        $s = $db->prepare('SELECT id FROM hrm_factors WHERE name=?');
        $s->execute([$old]);
        $oldId = $s->fetchColumn();
        $s = $db->prepare('SELECT id FROM hrm_factors WHERE name=?');
        $s->execute([$new]);
        $newId = $s->fetchColumn();
        if ($oldId && ! $newId) {
            $db->prepare('UPDATE hrm_factors SET name=? WHERE id=?')->execute([$new, $oldId]);
        }
    }
    $s = $db->prepare('SELECT id FROM hrm_factors WHERE name=?');
    $s->execute(['Employee Retention']);
    $retentionId = $s->fetchColumn();
    if (! $retentionId) {
        $db->prepare('INSERT INTO hrm_factors(name,description,display_order,status,is_outcome) VALUES(?,?,?,?,1)')->execute(['Employee Retention', 'Intention to remain and commitment to continued employment at TCGC', 0, 'active']);
    } else {
        $db->prepare('UPDATE hrm_factors SET is_outcome=1,status="active",display_order=0 WHERE id=?')->execute([$retentionId]);
    }
    $db->prepare('UPDATE hrm_factors SET is_outcome=0 WHERE name<>?')->execute(['Employee Retention']);

    // If an installation still has the original prototype decision thresholds, align the default management action cutoffs
    // with the validated 4-point interpretation boundaries while keeping them editable in Settings.
    $db->exec("UPDATE decision_rules SET threshold_low=2.51, threshold_high=3.26, name='Client-approved management action thresholds', is_prototype=0, resulting_category='Approved' WHERE active=1 AND threshold_low=2.75 AND threshold_high=3.25");

    // Populate factor links for known feedback categories in upgraded databases.
    $categoryMap = [
        'Compensation' => 'Compensation and Benefits', 'Benefits' => 'Compensation and Benefits', 'Payroll' => 'Compensation and Benefits',
        'Leadership' => 'Leadership and Management', 'Communication' => 'Leadership and Management', 'Training' => 'Training and Development',
        'Career Growth' => 'Career Development', 'Recognition' => 'Job Satisfaction', 'Workplace Environment' => 'Work Environment',
    ];
    foreach ($categoryMap as $category => $factorName) {
        $s = $db->prepare('SELECT id FROM hrm_factors WHERE name=?');
        $s->execute([$factorName]);
        $factorId = $s->fetchColumn();
        if ($factorId) {
            $u = $db->prepare('UPDATE feedback_categories SET factor_id=? WHERE name=? AND factor_id IS NULL');
            $u->execute([$factorId, $category]);
        }
    }
    $db->exec("UPDATE interventions SET approval_status='Approved' WHERE approval_status='Pending' AND status IN ('Approved','In Progress','Completed')");
    // Keep existing installations aligned with the client-approved configuration without overwriting later edits.
    $defaults = [
        'system_name' => 'TCGC Employee Retention Decision Support System',
        'dashboard_title' => 'HRM Analytics & Decision Support Dashboard',
        'demo_mode' => '1',
        'assessment_frequency' => 'Semestral',
        'instrument_interpretation' => '1.00–1.75 Very Low|1.76–2.50 Low|2.51–3.25 High|3.26–4.00 Very High',
        'client_validation_status' => 'Approved',
        'system_version' => '1.0 Final',
        'primary_color' => '#173a5e',
        'accent_color' => '#2f6f98',
        'report_footer' => 'TCGC Employee Retention Decision Support System',
        'logo_path' => '',
    ];
    foreach ($defaults as $key => $value) {
        $s = $db->prepare('SELECT COUNT(*) FROM system_settings WHERE key_name=?');
        $s->execute([$key]);
        if (! (int) $s->fetchColumn()) {
            $db->prepare('INSERT INTO system_settings(key_name,value,updated_by) VALUES(?,?,NULL)')->execute([$key, $value]);
        }
    }
    $db->prepare("UPDATE system_settings SET value='0' WHERE key_name='prototype_mode' AND value='1'")->execute();
    $db->prepare("UPDATE decision_rules SET is_prototype=0, name='Client-approved management action thresholds', resulting_category='Approved' WHERE is_prototype=1")->execute();
}
