<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class InitializeInstitution extends Command
{
    protected $signature = 'retention:initialize {--name= : Initial HR administrator name} {--email= : Initial HR administrator email}';

    protected $description = 'Initialize an empty institutional database without demonstration records';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Run retention:initialize interactively. The administrator password must be entered using the hidden password prompt.');

            return self::FAILURE;
        }

        try {
            $this->assertEmptyDatabase();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Administrator name')));
        $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('Administrator email'))));
        $password = $this->secret('Administrator password (at least 12 characters)', false);
        $confirmation = $this->secret('Confirm administrator password', false);
        $validator = Validator::make([
            'name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:180'], 'email' => ['required', 'email', 'max:180'],
            'password' => ['required', 'string', 'min:12', 'max:1024', 'confirmed'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($name, $email, $password): void {
                $this->assertEmptyDatabase();
                $factors = config('instrument.factors');
                if (! is_array($factors) || count($factors) !== 9) {
                    throw new RuntimeException('The nine approved measures are unavailable. Clear the configuration cache before initializing.');
                }
                $roles = [];
                foreach (['Employee', 'HR Administrator', 'Department Head', 'Executive'] as $role) {
                    $roles[$role] = DB::table('roles')->insertGetId(['name' => $role]);
                }
                $factorIds = [];
                foreach ($factors as [$factor, $description, $order, $outcome]) {
                    $factorIds[$factor] = DB::table('hrm_factors')->insertGetId([
                        'name' => $factor, 'description' => $description, 'display_order' => $order,
                        'is_outcome' => $outcome, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $adminId = DB::table('users')->insertGetId([
                    'role_id' => $roles['HR Administrator'], 'name' => $name, 'email' => $email,
                    'password_hash' => Hash::make($password), 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
                ]);
                foreach ($this->settings() as $key => $value) {
                    DB::table('system_settings')->insert([
                        'key_name' => $key, 'value' => $value, 'updated_by' => $adminId, 'updated_at' => now(),
                    ]);
                }
                foreach ([
                    'Workload' => null, 'Compensation' => 'Compensation and Benefits', 'Benefits' => 'Compensation and Benefits',
                    'Leadership' => 'Leadership and Management', 'Training' => 'Training and Development',
                    'Career Growth' => 'Career Development', 'Recognition' => 'Job Satisfaction',
                    'Workplace Environment' => 'Work Environment', 'Communication' => 'Leadership and Management',
                    'Payroll' => 'Compensation and Benefits', 'Other' => null,
                ] as $category => $factor) {
                    DB::table('feedback_categories')->insert([
                        'name' => $category, 'factor_id' => $factor ? $factorIds[$factor] : null, 'status' => 'active',
                    ]);
                }
                foreach (config('research.themes') as $theme) {
                    DB::table('feedback_themes')->insert(['name' => $theme['title'], 'status' => 'active']);
                }
                foreach ([
                    'Monitor' => 'Continue monitoring the factor and trend.',
                    'Improve' => 'Prioritize corrective or strengthening actions.',
                    'Strengthen' => 'Maintain and reinforce positive practices.',
                ] as $category => $description) {
                    DB::table('action_categories')->insert(['name' => $category, 'description' => $description]);
                }
                DB::table('decision_rules')->insert([
                    'name' => 'Management action defaults - review with HR', 'threshold_low' => 2.51,
                    'threshold_high' => 3.26, 'stability_band' => 0.10, 'resulting_category' => 'Pending HR review',
                    'active' => 1, 'is_prototype' => 1, 'created_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Initialization failed and was rolled back. Check the application log; existing records were preserved.');

            return self::FAILURE;
        }

        $this->info('Institutional database initialized. One HR administrator was created.');
        $this->line('No employees, assessment periods, answers, feedback submissions, or interventions were created.');
        $this->line('Enter the authorized organization and employee records, then create a draft assessment period.');
        $this->line('Review management thresholds with HR. Research baselines and regression coefficients remain separate reference context.');

        return self::SUCCESS;
    }

    private function assertEmptyDatabase(): void
    {
        foreach (['roles', 'users', 'employees', 'hrm_factors', 'system_settings', 'survey_responses'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException('Run php artisan migrate on a new empty database before initializing. Do not use --seed.');
            }
        }
        foreach (Schema::getTableListing(null, false) as $table) {
            if ($table !== config('database.migrations.table', 'migrations') && DB::table($table)->exists()) {
                throw new RuntimeException("Initialization refused: {$table} already contains records. Use a separate empty institutional database; the demonstration database was not changed.");
            }
        }
    }

    private function settings(): array
    {
        return [
            'institution_name' => 'Tangub City Global College',
            'system_name' => 'TCGC Employee Retention Decision Support System',
            'dashboard_title' => 'HRM Analytics & Decision Support Dashboard',
            'minimum_group_size' => '5', 'session_timeout' => '60', 'allow_named_feedback' => '0',
            'prototype_mode' => '0', 'demo_mode' => '0', 'default_scale' => '4-point Likert',
            'instrument_interpretation' => '1.00–1.75 Very Low|1.76–2.50 Low|2.51–3.25 High|3.26–4.00 Very High',
            'assessment_frequency' => 'Quarterly', 'client_validation_status' => 'Pending institutional acceptance',
            'system_version' => '1.0', 'primary_color' => config('branding.primary_color'),
            'accent_color' => config('branding.accent_color'), 'logo_path' => config('branding.logo'),
            'report_footer' => 'TCGC Employee Retention Decision Support System',
        ];
    }
}
