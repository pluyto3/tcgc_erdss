<?php

function active_period(PDO $db): ?array
{
    $s = $db->query("SELECT * FROM assessment_periods WHERE status='active' ORDER BY start_date DESC,id DESC LIMIT 1");

    return $s->fetch() ?: null;
}

function latest_periods(PDO $db, int $limit = 2): array
{
    $s = $db->prepare("SELECT * FROM assessment_periods WHERE status IN ('active','closed') ORDER BY start_date DESC,id DESC LIMIT ?");
    $s->bindValue(1, $limit, PDO::PARAM_INT);
    $s->execute();

    return $s->fetchAll();
}

function previous_period_id(PDO $db, int $periodId): ?int
{
    $periods = latest_periods($db, 50);
    foreach ($periods as $idx => $p) {
        if ((int) $p['id'] === $periodId && isset($periods[$idx + 1])) {
            return (int) $periods[$idx + 1]['id'];
        }
    }

    return null;
}

function factor_means(PDO $db, int $periodId, ?int $departmentId = null): array
{
    $sql = 'SELECT f.id,f.name,f.display_order,COUNT(DISTINCT sr.id) respondents,AVG(CASE WHEN sr.id IS NOT NULL THEN sri.response_value END) mean_score
          FROM hrm_factors f
          LEFT JOIN survey_questions q ON q.factor_id=f.id AND q.period_id=:pid
          LEFT JOIN survey_response_items sri ON sri.question_id=q.id
          LEFT JOIN survey_responses sr ON sr.id=sri.response_id AND sr.period_id=:pid2';
    if ($departmentId) {
        $sql .= ' AND sr.snapshot_department_id=:dept';
    }
    $sql .= " WHERE f.status='active' AND COALESCE(f.is_outcome,0)=0 GROUP BY f.id,f.name,f.display_order ORDER BY f.display_order";
    $s = $db->prepare($sql);
    $s->bindValue(':pid', $periodId, PDO::PARAM_INT);
    $s->bindValue(':pid2', $periodId, PDO::PARAM_INT);
    if ($departmentId) {
        $s->bindValue(':dept', $departmentId, PDO::PARAM_INT);
    }
    $s->execute();
    $rows = $s->fetchAll();
    foreach ($rows as &$row) {
        if (! min_group_ok($db, (int) $row['respondents'])) {
            $row['mean_score'] = null;
        }
    }

    return $rows;
}

function factor_mean(PDO $db, int $periodId, int $factorId, ?int $departmentId = null): ?float
{
    $sql = 'SELECT AVG(sri.response_value) mean_score, COUNT(DISTINCT sr.id) respondents
          FROM survey_response_items sri
          JOIN survey_questions q ON q.id=sri.question_id
          JOIN survey_responses sr ON sr.id=sri.response_id
          WHERE sr.period_id=? AND q.factor_id=?';
    $args = [$periodId, $factorId];
    if ($departmentId) {
        $sql .= ' AND sr.snapshot_department_id=?';
        $args[] = $departmentId;
    }
    $s = $db->prepare($sql);
    $s->execute($args);
    $row = $s->fetch();

    return ! min_group_ok($db, (int) $row['respondents']) || $row['mean_score'] === null ? null : (float) $row['mean_score'];
}

function overall_mean(PDO $db, int $periodId, ?int $departmentId = null): ?float
{
    if (! min_group_ok($db, respondent_count($db, $periodId, $departmentId))) {
        return null;
    }
    // Overall HRM perception mean across the eight predictor factors (excludes the Employee Retention outcome measure).
    $sql = 'SELECT AVG(sri.response_value)
          FROM survey_response_items sri
          JOIN survey_questions q ON q.id=sri.question_id
          JOIN hrm_factors f ON f.id=q.factor_id
          JOIN survey_responses sr ON sr.id=sri.response_id
          WHERE sr.period_id=? AND COALESCE(f.is_outcome,0)=0';
    $args = [$periodId];
    if ($departmentId) {
        $sql .= ' AND sr.snapshot_department_id=?';
        $args[] = $departmentId;
    }
    $s = $db->prepare($sql);
    $s->execute($args);
    $v = $s->fetchColumn();

    return $v === null ? null : (float) $v;
}

function retention_mean(PDO $db, int $periodId, ?int $departmentId = null): ?float
{
    $sql = 'SELECT AVG(sri.response_value) mean_score, COUNT(DISTINCT sr.id) respondents
          FROM survey_response_items sri
          JOIN survey_questions q ON q.id=sri.question_id
          JOIN hrm_factors f ON f.id=q.factor_id
          JOIN survey_responses sr ON sr.id=sri.response_id
          WHERE sr.period_id=? AND COALESCE(f.is_outcome,0)=1';
    $args = [$periodId];
    if ($departmentId) {
        $sql .= ' AND sr.snapshot_department_id=?';
        $args[] = $departmentId;
    }
    $s = $db->prepare($sql);
    $s->execute($args);
    $row = $s->fetch();

    return ! min_group_ok($db, (int) $row['respondents']) || $row['mean_score'] === null ? null : (float) $row['mean_score'];
}

function instrument_interpretation(?float $score): string
{
    if ($score === null) {
        return 'No data';
    }
    if ($score <= 1.75) {
        return 'Very Low';
    }
    if ($score <= 2.50) {
        return 'Low';
    }
    if ($score <= 3.25) {
        return 'High';
    }

    return 'Very High';
}

function instrument_agreement_label(?float $score): string
{
    if ($score === null) {
        return 'No data';
    }
    if ($score <= 1.75) {
        return 'Strongly Disagree';
    }
    if ($score <= 2.50) {
        return 'Disagree';
    }
    if ($score <= 3.25) {
        return 'Agree';
    }

    return 'Strongly Agree';
}

function respondent_count(PDO $db, int $periodId, ?int $departmentId = null): int
{
    $sql = 'SELECT COUNT(*) FROM survey_responses WHERE period_id=?';
    $args = [$periodId];
    if ($departmentId) {
        $sql .= ' AND snapshot_department_id=?';
        $args[] = $departmentId;
    }
    $s = $db->prepare($sql);
    $s->execute($args);

    return (int) $s->fetchColumn();
}

function period_has_paper_responses(PDO $db, int $periodId): bool
{
    // Preserve existing installations while the provenance migration is pending.
    $tableQuery = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
        ? "SELECT 1 FROM sqlite_master WHERE type='table' AND name='paper_response_imports'"
        : "SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='paper_response_imports'";
    if ($db->query($tableQuery)->fetchColumn() === false) {
        return false;
    }
    $statement = $db->prepare('SELECT 1 FROM paper_response_imports WHERE period_id=? LIMIT 1');
    $statement->execute([$periodId]);

    return $statement->fetchColumn() !== false;
}

function participation_rate(PDO $db, int $periodId, ?int $departmentId = null): ?float
{
    if (period_has_paper_responses($db, $periodId)) {
        // Paper responses carry no employee completion linkage or historical eligibility roster.
        return null;
    }
    if ($departmentId) {
        $s = $db->prepare('SELECT COUNT(*) FROM survey_participation sp JOIN employees e ON e.id=sp.employee_id WHERE sp.period_id=? AND e.department_id=?');
        $s->execute([$periodId, $departmentId]);
        $done = (int) $s->fetchColumn();
        $s = $db->prepare("SELECT COUNT(*) FROM employees WHERE employment_status='active' AND archived_at IS NULL AND department_id=?");
        $s->execute([$departmentId]);
        $eligible = (int) $s->fetchColumn();
    } else {
        $s = $db->prepare('SELECT COUNT(*) FROM survey_participation WHERE period_id=?');
        $s->execute([$periodId]);
        $done = (int) $s->fetchColumn();
        $eligible = (int) $db->query("SELECT COUNT(*) FROM employees WHERE employment_status='active' AND archived_at IS NULL")->fetchColumn();
    }

    return $eligible ? ($done / $eligible) * 100 : 0;
}

function employee_counts(PDO $db, ?int $departmentId = null): array
{
    $where = 'archived_at IS NULL';
    $args = [];
    if ($departmentId) {
        $where .= ' AND department_id=?';
        $args[] = $departmentId;
    }
    $s = $db->prepare("SELECT COUNT(*) FROM employees WHERE $where");
    $s->execute($args);
    $total = (int) $s->fetchColumn();
    $s = $db->prepare("SELECT COUNT(*) FROM employees WHERE $where AND employment_status='active'");
    $s->execute($args);
    $active = (int) $s->fetchColumn();
    $s = $db->prepare("SELECT COUNT(*) FROM employees WHERE $where AND employment_status='separated'");
    $s->execute($args);
    $sep = (int) $s->fetchColumn();

    return compact('total', 'active', 'sep');
}

function retention_rate(PDO $db, ?int $departmentId = null): float
{
    $c = employee_counts($db, $departmentId);

    return $c['total'] ? ($c['active'] / $c['total']) * 100 : 0;
}

function turnover_rate(PDO $db, ?int $departmentId = null): float
{
    $c = employee_counts($db, $departmentId);

    return $c['total'] ? ($c['sep'] / $c['total']) * 100 : 0;
}

function decision_thresholds(PDO $db): array
{
    $r = $db->query('SELECT * FROM decision_rules WHERE active=1 ORDER BY id DESC LIMIT 1')->fetch() ?: [];

    return [
        'low' => (float) ($r['threshold_low'] ?? 2.51),
        'high' => (float) ($r['threshold_high'] ?? 3.26),
        'band' => (float) ($r['stability_band'] ?? 0.10),
        'name' => $r['name'] ?? 'Approved interpretation rules',
    ];
}

function trend_label(?float $current, ?float $previous, ?float $band = null): string
{
    if ($current === null || $previous === null) {
        return 'No comparison';
    }
    if ($band === null) {
        $db = db();
        $band = $db instanceof PDO ? decision_thresholds($db)['band'] : 0.10;
    }
    $d = $current - $previous;
    if (abs($d) <= $band) {
        return 'Stable';
    }

    return $d > 0 ? 'Improving' : 'Declining';
}

function score_interpretation(PDO $db, ?float $score): string
{
    if ($score === null) {
        return 'No data';
    }
    $t = decision_thresholds($db);
    if ($score < $t['low']) {
        return 'Needs Improvement';
    }
    if ($score < $t['high']) {
        return 'Monitor';
    }

    return 'Strengthen';
}

function status_for_score(?float $score): string
{
    $db = db();

    return $db instanceof PDO ? score_interpretation($db, $score) : ($score === null ? 'No data' : ($score < 2.51 ? 'Needs Improvement' : ($score < 3.26 ? 'Monitor' : 'Strengthen')));
}

function min_group_ok(PDO $db, int $respondents): bool
{
    return $respondents >= max(2, (int) setting($db, 'minimum_group_size', 5));
}

function latest_two_period_ids(PDO $db): array
{
    return array_column(latest_periods($db, 2), 'id');
}

function feedback_theme_counts(PDO $db, int $periodId): array
{
    $s = $db->prepare('SELECT ft.name,COUNT(*) cnt FROM feedback_theme_links l JOIN feedback_themes ft ON ft.id=l.theme_id JOIN feedback f ON f.id=l.feedback_id WHERE f.period_id=? GROUP BY ft.id,ft.name ORDER BY cnt DESC,ft.name');
    $s->execute([$periodId]);

    return $s->fetchAll();
}

function feedback_factor_count(PDO $db, int $periodId, int $factorId): int
{
    $s = $db->prepare('SELECT COUNT(*) FROM feedback f JOIN feedback_categories c ON c.id=f.category_id WHERE f.period_id=? AND c.factor_id=?');
    $s->execute([$periodId, $factorId]);

    return (int) $s->fetchColumn();
}

function department_overall_means(PDO $db, int $periodId): array
{
    $depts = $db->query("SELECT id,name FROM departments WHERE status='active' ORDER BY name")->fetchAll();
    $out = [];
    foreach ($depts as $d) {
        $n = respondent_count($db, $periodId, (int) $d['id']);
        $out[] = [
            'id' => (int) $d['id'],
            'name' => $d['name'],
            'respondents' => $n,
            'suppressed' => ! min_group_ok($db, $n),
            'mean_score' => min_group_ok($db, $n) ? retention_mean($db, $periodId, (int) $d['id']) : null,
        ];
    }

    return $out;
}

function yearly_separation_counts(PDO $db, int $years = 5, ?int $departmentId = null): array
{
    $current = (int) date('Y');
    $out = [];
    for ($y = $current - $years + 1; $y <= $current; $y++) {
        $sql = 'SELECT COUNT(*) FROM employee_separations es JOIN employees e ON e.id=es.employee_id WHERE es.separation_date>=? AND es.separation_date<?';
        $args = [sprintf('%04d-01-01', $y), sprintf('%04d-01-01', $y + 1)];
        if ($departmentId) {
            $sql .= ' AND e.department_id=?';
            $args[] = $departmentId;
        }
        $s = $db->prepare($sql);
        $s->execute($args);
        $out[] = ['year' => (string) $y, 'count' => (int) $s->fetchColumn()];
    }

    return $out;
}

function refresh_priorities(PDO $db, int $periodId): void
{
    $prevId = previous_period_id($db, $periodId);
    $rules = decision_thresholds($db);
    $low = $rules['low'];
    $high = $rules['high'];
    $band = $rules['band'];
    foreach (factor_means($db, $periodId) as $f) {
        if ($f['mean_score'] === null) {
            continue;
        }
        $cur = (float) $f['mean_score'];
        $prev = $prevId ? factor_mean($db, $prevId, (int) $f['id']) : null;
        $trend = trend_label($cur, $prev, $band);
        if ($cur < $low) {
            $priority = $trend === 'Declining' ? 'High' : 'Medium';
            $cat = 'Improve';
        } elseif ($cur < $high) {
            $priority = 'Normal';
            $cat = 'Monitor';
        } else {
            $priority = 'Positive';
            $cat = 'Strengthen';
        }
        $themeCount = feedback_factor_count($db, $periodId, (int) $f['id']);
        $rec = $db->prepare('SELECT rl.title FROM recommendation_library rl JOIN recommendation_factors rf ON rf.recommendation_id=rl.id WHERE rf.factor_id=? AND rl.status="active" ORDER BY rl.id LIMIT 1');
        $rec->execute([$f['id']]);
        $suggest = $rec->fetchColumn() ?: 'Review factor-specific actions';
        $existing = $db->prepare('SELECT id,status FROM priority_areas WHERE period_id=? AND factor_id=? AND department_id IS NULL');
        $existing->execute([$periodId, $f['id']]);
        $row = $existing->fetch();
        if ($row) {
            $db->prepare('UPDATE priority_areas SET current_score=?,previous_score=?,trend=?,priority_level=?,action_category=?,theme_count=?,suggested_recommendation=? WHERE id=?')
                ->execute([$cur, $prev, $trend, $priority, $cat, $themeCount, $suggest, $row['id']]);
        } else {
            $db->prepare('INSERT INTO priority_areas(period_id,factor_id,department_id,current_score,previous_score,trend,priority_level,action_category,theme_count,suggested_recommendation,status) VALUES(?,?,NULL,?,?,?,?,?,?,?,?)')
                ->execute([$periodId, $f['id'], $cur, $prev, $trend, $priority, $cat, $themeCount, $suggest, 'pending']);
        }
    }
}

function total_intervention_budget(PDO $db, string $approvalStatus = 'Approved'): float
{
    $s = $db->prepare("SELECT COALESCE(SUM(budget_amount),0) FROM interventions WHERE approval_status=? AND status<>'Cancelled'");
    $s->execute([$approvalStatus]);

    return (float) $s->fetchColumn();
}

function svg_bar_chart(array $items, string $labelKey, string $valueKey, int $w = 720, int $h = 260, float $max = 4.0): string
{
    if (! $items) {
        return '<div class="empty">No data available.</div>';
    }
    $valid = array_values(array_filter($items, fn ($x) => isset($x[$valueKey]) && $x[$valueKey] !== null));
    if (! $valid) {
        return '<div class="empty">No displayable data available.</div>';
    }
    $pad = 40;
    $barArea = $w - 280;
    $rowH = max(28, (int) (($h - $pad) / max(1, count($valid))));
    $height = max($h, 40 + count($valid) * $rowH);
    $svg = '<svg class="chart" viewBox="0 0 '.$w.' '.$height.'" role="img">';
    foreach ($valid as $i => $it) {
        $y = 20 + $i * $rowH;
        $label = (string) $it[$labelKey];
        $val = (float) $it[$valueKey];
        $bw = max(0, min($barArea, $barArea * ($val / $max)));
        $svg .= '<text x="10" y="'.($y + 16).'" font-size="12">'.e(function_exists('mb_strimwidth') ? mb_strimwidth($label, 0, 28, '…') : (strlen($label) > 28 ? substr($label, 0, 27).'…' : $label)).'</text><rect x="220" y="'.$y.'" width="'.$bw.'" height="18" rx="5" class="bar"></rect><text x="'.(225 + $bw).'" y="'.($y + 14).'" font-size="12">'.fmt_num($val).'</text>';
    }

    return $svg.'</svg>';
}
