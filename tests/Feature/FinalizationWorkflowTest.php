<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinalizationWorkflowTest extends TestCase
{
    private function asRole(string $role): User
    {
        $user = User::whereHas('role', fn ($query) => $query->where('name', $role))->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    public function test_hr_can_review_and_tag_general_feedback_without_exposing_anonymous_identity(): void
    {
        $employee = $this->asRole('Employee');
        DB::table('employees')->where('id', $employee->employee_id)->update(['first_name' => 'Hidden', 'last_name' => 'AnonymousIdentity']);
        $this->post('/submit-feedback', ['category_id' => 1, 'body' => 'General anonymous workplace suggestion.', 'is_anonymous' => 1])
            ->assertRedirect('/submit-feedback');
        $anonymous = DB::table('feedback')->where('body', 'General anonymous workplace suggestion.')->first();
        $this->assertNull($anonymous->period_id);
        $this->assertNull($anonymous->submitter_employee_id);
        $namedEmployee = DB::table('employees')->where('id', '<>', $employee->employee_id)->first();
        DB::table('employees')->where('id', $namedEmployee->id)->update(['first_name' => 'Visible', 'last_name' => 'NamedIdentity']);
        DB::table('feedback')->insert([
            'category_id' => 1, 'body' => 'General named workplace suggestion.', 'is_anonymous' => 0,
            'submitter_employee_id' => $namedEmployee->id, 'snapshot_department_id' => $namedEmployee->department_id,
        ]);
        DB::table('feedback')->insert(['period_id' => 2, 'category_id' => 1, 'body' => 'Only the selected assessment should show this comment.', 'is_anonymous' => 1]);

        $this->asRole('HR Administrator');
        $this->get('/feedback?period_id=general')->assertOk()
            ->assertSee('General / not tied to a period')->assertSee('General anonymous workplace suggestion.')
            ->assertSee('General named workplace suggestion.')->assertSee('Visible NamedIdentity')
            ->assertDontSee('Hidden AnonymousIdentity')->assertDontSee('Only the selected assessment should show this comment.');
        $themeId = DB::table('feedback_themes')->value('id');
        $this->post('/feedback', ['action' => 'tag', 'feedback_id' => $anonymous->id, 'theme_id' => $themeId, 'period_id' => 'general'])
            ->assertRedirect('/feedback?period_id=general');
        $this->assertDatabaseHas('feedback_theme_links', ['feedback_id' => $anonymous->id, 'theme_id' => $themeId]);
        $this->assertDatabaseHas('feedback', ['id' => $anonymous->id, 'review_status' => 'reviewed', 'submitter_employee_id' => null]);
        $themeName = DB::table('feedback_themes')->where('id', $themeId)->value('name');
        $this->get('/feedback?period_id=general')->assertOk()->assertSee('<span>'.e($themeName).'</span><strong>1</strong>', false);
        $this->get('/feedback?period_id=2')->assertOk()->assertSee('Only the selected assessment should show this comment.')
            ->assertDontSee('General anonymous workplace suggestion.')->assertDontSee('General named workplace suggestion.');
        $this->post('/feedback', ['action' => 'new_theme', 'name' => 'General workplace support', 'period_id' => 'general'])
            ->assertRedirect('/feedback?period_id=general');
        $this->get('/feedback?period_id=general')->assertOk()->assertSee('General workplace support');
    }

    public function test_general_feedback_review_remains_restricted_to_hr_and_validates_period_filters(): void
    {
        foreach (['Employee', 'Executive', 'Department Head'] as $role) {
            $this->asRole($role);
            $this->get('/feedback?period_id=general')->assertForbidden();
            $this->post('/feedback', ['action' => 'new_theme', 'name' => 'Unauthorized theme', 'period_id' => 'general'])->assertForbidden();
        }
        $this->assertDatabaseMissing('feedback_themes', ['name' => 'Unauthorized theme']);
        $this->asRole('HR Administrator');
        $this->get('/feedback?period_id=invalid')->assertSessionHasErrors('period_id');
        $this->get('/feedback?period_id=999999')->assertSessionHasErrors('period_id');
    }

    public function test_collected_responses_freeze_question_additions_and_removals(): void
    {
        $this->asRole('HR Administrator');
        $periodId = DB::table('assessment_periods')->insertGetId([
            'title' => 'Collection has started', 'start_date' => '2027-01-01', 'end_date' => '2027-03-31', 'status' => 'active',
        ]);
        $questionId = DB::table('survey_questions')->insertGetId([
            'period_id' => $periodId, 'factor_id' => DB::table('hrm_factors')->where('is_outcome', 0)->value('id'),
            'question_text' => 'This existing question has no collected answer yet.',
        ]);
        DB::table('survey_responses')->insert(['period_id' => $periodId]);
        $this->assertTrue(DB::table('survey_responses')->where('period_id', $periodId)->exists());
        $question = DB::table('survey_questions')->find($questionId);
        $questionCount = DB::table('survey_questions')->where('period_id', $periodId)->count();
        $this->post('/assessments', ['action' => 'add_question', 'period_id' => $periodId, 'factor_id' => $question->factor_id,
            'question_text' => 'This question must not be added during collection.', 'display_order' => 100])->assertSessionHasErrors('period_id');
        $this->post('/assessments', ['action' => 'delete_question', 'period_id' => $periodId, 'id' => $question->id])->assertSessionHasErrors('period_id');
        $this->assertSame($questionCount, DB::table('survey_questions')->where('period_id', $periodId)->count());
        $this->assertDatabaseMissing('survey_questions', ['question_text' => 'This question must not be added during collection.']);
        $this->assertDatabaseHas('survey_questions', ['id' => $question->id]);
        $this->get('/assessments?view='.$periodId)->assertOk()->assertSee('Questionnaire locked.')
            ->assertDontSee('value="add_question"', false)->assertDontSee('value="delete_question"', false);
    }

    public function test_draft_without_responses_can_still_add_and_remove_questions(): void
    {
        $this->asRole('HR Administrator');
        $periodId = DB::table('assessment_periods')->insertGetId([
            'title' => 'Prepared assessment without responses', 'start_date' => '2027-01-01', 'end_date' => '2027-03-31', 'status' => 'draft',
        ]);
        $factorId = DB::table('hrm_factors')->where('is_outcome', 0)->value('id');
        $this->post('/assessments', ['action' => 'add_question', 'period_id' => $periodId, 'factor_id' => $factorId,
            'question_text' => 'An approved question prepared before collection.', 'display_order' => 1])->assertRedirect('/assessments?view='.$periodId);
        $questionId = DB::table('survey_questions')->where('period_id', $periodId)->value('id');
        $this->assertNotNull($questionId);
        $this->get('/assessments?view='.$periodId)->assertOk()->assertSee('value="add_question"', false)
            ->assertSee('value="delete_question"', false)->assertDontSee('Questionnaire locked.');
        $this->post('/assessments', ['action' => 'delete_question', 'period_id' => $periodId, 'id' => $questionId])->assertRedirect('/assessments?view='.$periodId);
        $this->assertDatabaseMissing('survey_questions', ['id' => $questionId]);
    }
}
