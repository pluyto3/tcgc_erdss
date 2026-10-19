<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PriorityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManagementTest extends TestCase
{
    private function asRole(string $role): void
    {
        $this->actingAs(User::whereHas('role', fn ($q) => $q->where('name', $role))->first());
    }

    public function test_employee_create_import_archive_and_malformed_csv(): void
    {
        $this->asRole('HR Administrator');
        $this->post('/employees', ['action' => 'save', 'employee_no' => 'TEST-001', 'first_name' => 'Test', 'last_name' => 'Person',
            'department_id' => 1, 'position_id' => 1, 'category_id' => 1, 'employment_type_id' => 1, 'employment_status' => 'active'])->assertRedirect('/employees');
        $this->assertDatabaseHas('employees', ['employee_no' => 'TEST-001']);
        $csv = "\xEF\xBB\xBFemployee_no,first_name,last_name,department,position,category,employment_type\n";
        $csv .= "IMPORT-001,Sample,Imported,Academic Affairs,Instructor,Academic,Regular\n";
        $csv .= "BAD-ROW,Too,Many,Columns,For,This,Header,Extra\n";
        $this->post('/employees', ['action' => 'import_csv', 'employee_csv' => UploadedFile::fake()->createWithContent('employees.csv', $csv)])
            ->assertRedirect('/employees');
        $this->assertDatabaseHas('employees', ['employee_no' => 'IMPORT-001']);
        $id = DB::table('employees')->where('employee_no', 'TEST-001')->value('id');
        $this->post('/employees', ['action' => 'archive', 'id' => $id])->assertRedirect('/employees');
        $this->assertNotNull(DB::table('employees')->find($id)->archived_at);
    }

    public function test_intervention_creation_approval_and_scoped_progress(): void
    {
        $this->asRole('HR Administrator');
        $factorId = DB::table('hrm_factors')->where('is_outcome', 0)->value('id');
        $this->post('/interventions', ['action' => 'save_intervention', 'title' => 'Quarterly feedback sessions', 'factor_id' => $factorId,
            'budget_amount' => 5000, 'is_public' => 1, 'responsible_department_id' => 4])->assertRedirect('/interventions');
        $item = DB::table('interventions')->where('title', 'Quarterly feedback sessions')->first();
        $this->assertSame('Pending', $item->approval_status);
        $this->post('/interventions', ['action' => 'approve', 'intervention_id' => $item->id, 'decision' => 'Approved'])->assertForbidden();
        $this->asRole('Executive');
        $this->post('/interventions', ['action' => 'approve', 'intervention_id' => $item->id, 'decision' => 'Approved'])->assertRedirect('/interventions');
        $this->asRole('Department Head');
        $this->post('/interventions', ['action' => 'update_progress', 'intervention_id' => $item->id, 'status' => 'Completed', 'note' => 'Sessions completed.'])->assertRedirect('/interventions');
        $this->assertDatabaseHas('intervention_updates', ['intervention_id' => $item->id, 'status' => 'Completed']);
        $this->post('/interventions', ['action' => 'update_progress', 'intervention_id' => 1, 'status' => 'Completed', 'note' => 'Unauthorized change.'])->assertForbidden();
        $this->assertDatabaseMissing('intervention_updates', ['note' => 'Unauthorized change.']);
    }

    public function test_priority_reads_do_not_write_and_confirmation_is_persisted(): void
    {
        $this->asRole('HR Administrator');
        $before = DB::table('priority_areas')->count();
        $this->get('/priorities')->assertOk();
        $this->get('/reports?type=priorities')->assertOk();
        $this->assertSame($before, DB::table('priority_areas')->count());
        $factor = app(PriorityService::class)->suggestions(2)[0];
        $this->post('/priorities', ['period_id' => 2, 'factor_id' => $factor['factor_id'], 'status' => 'confirmed'])->assertRedirect('/priorities?period_id=2');
        $this->get('/priorities')->assertOk();
        $this->assertDatabaseHas('priority_areas', ['period_id' => 2, 'factor_id' => $factor['factor_id'], 'status' => 'confirmed']);
    }

    public function test_admin_cannot_remove_the_last_active_administrator_or_set_invalid_settings(): void
    {
        $this->asRole('HR Administrator');
        $user = auth()->user();
        $this->post('/users', ['action' => 'save', 'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'role_id' => $user->role_id, 'status' => 'inactive'])->assertSessionHasErrors('role_id');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'active']);
        $this->post('/settings', ['action' => 'settings', 'minimum_group_size' => 1])->assertSessionHasErrors('minimum_group_size');
    }

    public function test_assessment_and_recommendation_administration(): void
    {
        $this->asRole('HR Administrator');
        $this->post('/assessments', ['action' => 'save_period', 'title' => 'Quarterly review', 'start_date' => '2027-01-01', 'end_date' => '2027-03-31',
            'status' => 'draft', 'scale_min' => 1, 'scale_max' => 4, 'scale_labels' => '1=Strongly Disagree|2=Disagree|3=Agree|4=Strongly Agree'])->assertRedirect();
        $periodId = DB::table('assessment_periods')->where('title', 'Quarterly review')->value('id');
        $factorId = DB::table('hrm_factors')->where('is_outcome', 0)->value('id');
        $this->post('/assessments', ['action' => 'add_question', 'period_id' => $periodId, 'factor_id' => $factorId,
            'question_text' => 'My work is supported.', 'display_order' => 1])->assertRedirect();
        $this->assertDatabaseHas('survey_questions', ['period_id' => $periodId, 'question_text' => 'My work is supported.']);
        $this->post('/recommendations', ['title' => 'Quarterly check-ins', 'factor_id' => $factorId, 'status' => 'active'])->assertRedirect('/recommendations');
        $this->assertDatabaseHas('recommendation_library', ['title' => 'Quarterly check-ins']);
        $this->post('/master-data', ['table' => 'departments', 'name' => 'Student Life', 'code' => 'SL', 'status' => 'active'])->assertRedirect('/master-data?tab=departments');
    }

    public function test_per_factor_privacy_threshold_and_csv_formula_escaping(): void
    {
        $this->asRole('HR Administrator');
        $factorId = DB::table('hrm_factors')->where('is_outcome', 0)->value('id');
        $questionIds = DB::table('survey_questions')->where('factor_id', $factorId)->where('period_id', 2)->pluck('id');
        $responseIds = DB::table('survey_responses')->where('period_id', 2)->orderBy('id')->skip(1)->take(100)->pluck('id');
        DB::table('survey_response_items')->whereIn('question_id', $questionIds)->whereIn('response_id', $responseIds)->delete();
        $this->assertNull(factor_mean(db(), 2, $factorId));
        $this->assertNull(collect(factor_means(db(), 2))->firstWhere('id', $factorId)['mean_score']);
        DB::table('interventions')->where('id', 1)->update(['title' => '=DANGEROUS_FORMULA()']);
        $response = $this->get('/reports?type=interventions&export=csv')->assertOk();
        $this->assertStringContainsString("'=DANGEROUS_FORMULA()", $response->streamedContent());
    }

    public function test_login_throttle_survives_new_browser_sessions_and_disabled_accounts_are_rejected(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'hr.admin@tcgc-demo.local', 'password' => 'incorrect'])->assertSessionHasErrors('email');
            session()->flush();
        }
        $this->post('/login', ['email' => 'hr.admin@tcgc-demo.local', 'password' => 'DemoPass123!'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->asRole('Employee');
        DB::table('users')->where('id', auth()->id())->update(['status' => 'inactive']);
        auth()->user()->refresh();
        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_csrf_rejects_missing_token_and_accepts_the_valid_session_token(): void
    {
        $this->app->instance('env', 'local');
        $this->get('/login')->assertOk();
        $this->post('/login', ['email' => 'hr.admin@tcgc-demo.local', 'password' => 'DemoPass123!'])->assertStatus(419);
        $this->withSession(['_token' => 'known-test-token'])->post('/login', ['_token' => 'known-test-token', 'email' => 'hr.admin@tcgc-demo.local', 'password' => 'DemoPass123!'])->assertRedirect('/dashboard');
    }
}
