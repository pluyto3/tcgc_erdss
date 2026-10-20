<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class InstitutionSetupTest extends TestCase
{
    private const PASSWORD = 'PrivateInstitutionPass!2026';

    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Institution setup tests must use an isolated in-memory SQLite database.');
        }
        $this->artisan('migrate')->assertSuccessful();
    }

    private function initialize(): void
    {
        $this->artisan('retention:initialize', ['--name' => 'Institution HR', '--email' => 'HR@institution.example'])
            ->expectsQuestion('Administrator password (at least 12 characters)', self::PASSWORD)
            ->expectsQuestion('Confirm administrator password', self::PASSWORD)
            ->assertSuccessful();
    }

    private function draft(array $overrides = []): int
    {
        return DB::table('assessment_periods')->insertGetId([
            'title' => 'Institution assessment', 'status' => 'draft', 'scale_min' => 1, 'scale_max' => 4,
            'scale_labels' => '1=Strongly Disagree|2=Disagree|3=Agree|4=Strongly Agree', ...$overrides,
        ]);
    }

    public function test_institution_initialization_creates_private_access_without_inventing_operational_data(): void
    {
        $this->initialize();

        $this->assertSame(4, DB::table('roles')->count());
        $this->assertSame(9, DB::table('hrm_factors')->count());
        $this->assertSame(1, DB::table('hrm_factors')->where('is_outcome', 1)->count());
        $this->assertDatabaseHas('hrm_factors', ['name' => 'Employee Retention', 'is_outcome' => 1]);
        $this->assertSame(1, DB::table('users')->count());
        $admin = User::firstOrFail();
        $this->assertSame('HR Administrator', $admin->role->name);
        $this->assertSame('hr@institution.example', $admin->email);
        $this->assertNull($admin->employee_id);
        $this->assertNotSame(self::PASSWORD, $admin->password_hash);
        $this->assertTrue(Hash::check(self::PASSWORD, $admin->password_hash));
        foreach (['departments', 'positions', 'employee_categories', 'employment_types', 'employees', 'employee_separations',
            'assessment_periods', 'survey_questions', 'survey_responses', 'survey_response_items', 'survey_participation',
            'feedback', 'interventions', 'intervention_updates', 'intervention_evaluations', 'research_baselines',
            'retention_driver_models', 'retention_driver_coefficients'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} must contain no invented institutional records.");
        }
        $this->assertDatabaseHas('system_settings', ['key_name' => 'demo_mode', 'value' => '0']);
        $this->assertDatabaseHas('system_settings', ['key_name' => 'allow_named_feedback', 'value' => '0']);
        $this->assertDatabaseHas('system_settings', ['key_name' => 'primary_color', 'value' => config('branding.primary_color')]);
        $this->assertSame(11, DB::table('feedback_categories')->count());
        $this->assertSame(7, DB::table('feedback_themes')->count());
    }

    public function test_initializer_accepts_prompted_identity_and_never_requires_password_arguments(): void
    {
        $this->artisan('retention:initialize')
            ->expectsQuestion('Administrator name', 'Institution HR')
            ->expectsQuestion('Administrator email', 'hr@institution.example')
            ->expectsQuestion('Administrator password (at least 12 characters)', self::PASSWORD)
            ->expectsQuestion('Confirm administrator password', self::PASSWORD)
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'hr@institution.example']);
    }

    public function test_initializer_refuses_an_existing_domain_record_without_changing_it(): void
    {
        DB::table('departments')->insert(['name' => 'Authorized existing unit', 'code' => 'UNIT']);
        $this->artisan('retention:initialize')
            ->expectsOutputToContain('Initialization refused: departments already contains records.')
            ->assertFailed();

        $this->assertSame(1, DB::table('departments')->count());
        $this->assertSame(0, DB::table('users')->count());
        $this->assertSame(0, DB::table('roles')->count());
    }

    public function test_initializer_cannot_run_twice_or_replace_an_administrator(): void
    {
        $this->initialize();
        $hash = DB::table('users')->value('password_hash');
        $this->artisan('retention:initialize')->assertFailed();

        $this->assertSame(1, DB::table('users')->count());
        $this->assertSame($hash, DB::table('users')->value('password_hash'));
    }

    public function test_noninteractive_initializer_and_mismatched_passwords_leave_the_database_empty(): void
    {
        $this->artisan('retention:initialize', ['--no-interaction' => true])
            ->expectsOutputToContain('Run retention:initialize interactively.')
            ->assertFailed();
        $this->artisan('retention:initialize', ['--name' => 'Institution HR', '--email' => 'hr@institution.example'])
            ->expectsQuestion('Administrator password (at least 12 characters)', self::PASSWORD)
            ->expectsQuestion('Confirm administrator password', 'AnotherPassword!2026')
            ->assertFailed();

        $this->assertSame(0, DB::table('roles')->count());
        $this->assertSame(0, DB::table('users')->count());
    }

    public function test_partial_initialization_failure_rolls_back_every_created_record(): void
    {
        $factors = config('instrument.factors');
        config(['instrument.factors' => [...array_slice($factors, 0, 8), $factors[0]]]);
        $this->artisan('retention:initialize', ['--name' => 'Institution HR', '--email' => 'hr@institution.example'])
            ->expectsQuestion('Administrator password (at least 12 characters)', self::PASSWORD)
            ->expectsQuestion('Confirm administrator password', self::PASSWORD)
            ->expectsOutputToContain('Initialization failed and was rolled back.')
            ->assertFailed();

        foreach (['roles', 'users', 'hrm_factors', 'system_settings'] as $table) {
            $this->assertSame(0, DB::table($table)->count());
        }
    }

    public function test_approved_instrument_installs_once_with_correct_outcome_and_factor_links(): void
    {
        $this->initialize();
        $period = $this->draft();
        $this->artisan('retention:install-questionnaire', ['period' => $period, '--no-interaction' => true])->assertSuccessful();

        $questions = DB::table('survey_questions as q')->join('hrm_factors as f', 'q.factor_id', '=', 'f.id')
            ->where('q.period_id', $period)->orderBy('q.display_order')->get(['q.*', 'f.name', 'f.is_outcome']);
        $this->assertCount(56, $questions);
        $this->assertSame(range(1, 56), $questions->pluck('display_order')->all());
        $this->assertSame(8, $questions->where('is_outcome', 1)->count());
        foreach (config('instrument.questions') as $factor => $wording) {
            $this->assertSame($wording, $questions->where('name', $factor)->pluck('question_text')->values()->all());
        }
        $this->assertSame('draft', DB::table('assessment_periods')->find($period)->status);
        $this->assertSame(0, DB::table('survey_responses')->count());
        $this->artisan('retention:install-questionnaire', ['period' => $period])->assertFailed();
        $this->assertSame(56, DB::table('survey_questions')->count());
    }

    public function test_instrument_installation_preserves_active_used_wrong_scale_and_partial_periods(): void
    {
        $this->initialize();
        foreach ([['status' => 'active'], ['status' => 'closed'], ['scale_max' => 5]] as $invalid) {
            $period = $this->draft($invalid);
            $this->artisan('retention:install-questionnaire', ['period' => $period])->assertFailed();
        }
        $used = $this->draft();
        DB::table('survey_responses')->insert(['period_id' => $used]);
        $this->artisan('retention:install-questionnaire', ['period' => $used])->assertFailed();
        $partial = $this->draft();
        DB::table('survey_questions')->insert([
            'period_id' => $partial, 'factor_id' => DB::table('hrm_factors')->value('id'), 'question_text' => 'Existing client question',
        ]);
        $this->artisan('retention:install-questionnaire', ['period' => $partial])->assertFailed();
        $this->assertSame(1, DB::table('survey_questions')->count());
        $this->assertSame(1, DB::table('survey_responses')->count());
    }

    public function test_instrument_installation_requires_all_active_measures_and_valid_period_id(): void
    {
        $this->initialize();
        $period = $this->draft();
        DB::table('hrm_factors')->where('name', 'Job Satisfaction')->update(['status' => 'inactive']);
        $this->artisan('retention:install-questionnaire', ['period' => $period])->assertFailed();
        $this->artisan('retention:install-questionnaire', ['period' => 'invalid'])->assertFailed();
        $this->artisan('retention:install-questionnaire', ['period' => 999])->assertFailed();

        $this->assertSame(0, DB::table('survey_questions')->count());
    }

    public function test_new_institution_dashboard_defaults_to_its_operational_records(): void
    {
        $this->initialize();
        $this->actingAs(User::firstOrFail())->get('/dashboard')
            ->assertOk()->assertSee('Operational data')->assertDontSee('Demo workspace')
            ->assertViewHas('dataset', 'live')->assertViewHas('baseline', false)->assertViewHas('headcount', 0);
    }
}
