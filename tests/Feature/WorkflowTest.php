<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    public function test_login_and_every_authorized_page_render_for_all_roles(): void
    {
        $this->get('/login')->assertOk();
        $this->post('/login', ['email' => 'hr.admin@tcgc-demo.local', 'password' => 'DemoPass123!'])->assertRedirect('/dashboard');
        foreach (User::all() as $user) {
            $this->actingAs($user);
            foreach (config('modules') as $module => $entry) {
                if (in_array($user->role->name, $entry['roles'], true)) {
                    $this->get(route($module))->assertOk();
                } else {
                    $this->get(route($module))->assertForbidden();
                }
            }
        }
    }

    public function test_baseline_has_the_supplied_metrics_and_live_data_is_distinct(): void
    {
        $this->actingAs(User::where('email', 'hr.admin@tcgc-demo.local')->first());
        $this->get('/dashboard')->assertOk()->assertSee('2.82')->assertSee('285')->assertSee('2.937')->assertSee('2.654')->assertSee('39.9')->assertSee('Student Life');
        $this->get('/dashboard?dataset=live')->assertOk()->assertSee('Demonstration data')->assertSee('30');
        $this->get('/drivers')->assertOk()->assertSee('0.344')->assertSee('0.138');
        $this->get('/employee-perspective')->assertOk()->assertSee('Manageable workload');
        $this->get('/policy-report')->assertOk()->assertSee('Executive Committee and Board');
    }

    public function test_department_head_cannot_escape_scope_or_see_institutional_priorities(): void
    {
        $this->actingAs(User::where('email', 'dept.head@tcgc-demo.local')->first());
        $this->get('/dashboard?dataset=baseline&department_id=2')->assertOk()->assertSee('Scores with fewer than 5 respondents are hidden.');
        $this->get('/reports?type=priorities&export=csv')->assertForbidden();
        $this->get('/policy-report?dataset=baseline')->assertOk()->assertDontSee('285');
        $this->post('/settings', [])->assertForbidden();
    }

    public function test_assessment_validation_and_duplicate_submission_preserve_anonymity(): void
    {
        $this->actingAs(User::where('email', 'employee@tcgc-demo.local')->first());
        $questions = DB::table('survey_questions')->where('period_id', 2)->pluck('id');
        $answers = $questions->mapWithKeys(fn ($id) => [$id => 3])->all();
        $before = DB::table('survey_responses')->count();
        $invalid = $answers;
        $invalid[$questions->first()] = 9;
        $this->post('/my-assessments', ['period_id' => 2, 'q' => $invalid])->assertSessionHasErrors('q');
        $this->assertSame($before, DB::table('survey_responses')->count());
        $foreignQuestion = DB::table('survey_questions')->where('period_id', 1)->value('id');
        $this->post('/my-assessments', ['period_id' => 2, 'q' => [...$answers, $foreignQuestion => 3]])->assertSessionHasErrors('q');
        $this->post('/my-assessments', ['period_id' => 2, 'q' => $answers])->assertRedirect('/my-assessments');
        $this->assertSame($before + 1, DB::table('survey_responses')->count());
        $this->assertFalse(array_key_exists('employee_id', (array) DB::table('survey_responses')->orderByDesc('id')->first()));
        $this->post('/my-assessments', ['period_id' => 2, 'q' => $answers])->assertSessionHasErrors('period_id');
        $this->assertSame($before + 1, DB::table('survey_responses')->count());
    }

    public function test_anonymous_feedback_does_not_store_employee_identity(): void
    {
        $this->actingAs(User::where('email', 'employee@tcgc-demo.local')->first());
        $this->post('/submit-feedback', ['body' => 'A useful feedback example.', 'category_id' => 1, 'is_anonymous' => 1])->assertRedirect('/submit-feedback');
        $this->assertDatabaseHas('feedback', ['body' => 'A useful feedback example.', 'is_anonymous' => 1, 'submitter_employee_id' => null]);
    }

    public function test_reports_export_all_six_types(): void
    {
        $this->actingAs(User::where('email', 'hr.admin@tcgc-demo.local')->first());
        foreach (['retention', 'factors', 'feedback', 'priorities', 'interventions', 'prepost'] as $type) {
            $this->get('/reports?type='.$type)->assertOk();
            $this->get('/reports?type='.$type.'&export=csv')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
    }

    public function test_logout_requires_post_and_legacy_bookmarks_resolve(): void
    {
        $this->get('/index.php?page=login')->assertRedirect('/login');
        $this->actingAs(User::where('email', 'hr.admin@tcgc-demo.local')->first());
        $this->get('/index.php?page=employees&add=1')->assertRedirect('/employees?add=1');
        $this->get('/logout')->assertStatus(405);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
