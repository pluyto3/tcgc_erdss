<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'survey_response_items' => ['idx_items_question' => ['question_id']],
        'feedback' => ['idx_feedback_period_department' => ['period_id', 'snapshot_department_id']],
        'interventions' => ['idx_interventions_department_status' => ['responsible_department_id', 'status']],
        'survey_participation' => ['idx_participation_employee' => ['employee_id', 'period_id']],
        'employees' => ['idx_employees_scope_status' => ['department_id', 'employment_status', 'archived_at']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (! Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }
};
