<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PaperResponsePresentationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Paper response presentation tests require an isolated in-memory SQLite database.');
        }
        $this->artisan('migrate')->assertSuccessful();
        $this->seed(DatabaseSeeder::class);
    }

    private function paperPeriod(): int
    {
        $period = DB::table('assessment_periods')->insertGetId([
            'title' => 'Imported paper assessment history', 'start_date' => today()->subDay()->toDateString(),
            'end_date' => today()->addDay()->toDateString(), 'status' => 'closed', 'scale_min' => 1, 'scale_max' => 4,
        ]);
        $response = DB::table('survey_responses')->insertGetId([
            'period_id' => $period, 'snapshot_department_id' => 2, 'snapshot_category_id' => 1, 'snapshot_employment_type_id' => 1,
        ]);
        DB::table('paper_response_imports')->insert([
            'period_id' => $period, 'response_id' => $response, 'code_hash' => hash('sha256', 'presentation-paper-code'),
            'source_file_sha256' => hash('sha256', 'presentation-test-source'), 'submitted_date' => today()->toDateString(), 'imported_at' => now(),
        ]);

        return $period;
    }

    public function test_imported_paper_periods_have_unknown_participation_in_every_scope(): void
    {
        $onlineParticipation = participation_rate(db(), 2);
        $paper = $this->paperPeriod();

        $this->assertTrue(period_has_paper_responses(db(), $paper));
        $this->assertFalse(period_has_paper_responses(db(), 2));
        $this->assertFalse(period_has_paper_responses(db(), 999));
        $this->assertNull(participation_rate(db(), $paper));
        $this->assertNull(participation_rate(db(), $paper, 2));
        $this->assertNull(participation_rate(db(), $paper, 4));
        $this->assertIsFloat($onlineParticipation);
        $this->assertGreaterThan(0, $onlineParticipation);
        $this->assertSame($onlineParticipation, participation_rate(db(), 2));
    }

    public function test_retention_reports_and_csv_explain_untracked_paper_participation(): void
    {
        $paper = $this->paperPeriod();
        $report = app(ReportService::class)->build('retention', $paper, null, null, null);
        $values = array_column($report['rows'], 1, 0);
        $this->assertSame('Not tracked for imported paper responses', $values['Participation']);
        $this->assertSame(1, $values['Assessment respondents']);
        $this->assertSame('Suppressed / unavailable', $values['Employee retention mean']);
        $onlineReport = app(ReportService::class)->build('retention', 2, null, null, null);
        $onlineValues = array_column($onlineReport['rows'], 1, 0);
        $this->assertSame(fmt_num(participation_rate(db(), 2), 1).'%', $onlineValues['Participation']);

        $this->actingAs(User::where('email', 'hr.admin@tcgc-demo.local')->firstOrFail());
        $this->get('/reports?type=retention&period_id='.$paper)->assertOk()->assertSee('Not tracked for imported paper responses');
        $csv = $this->get('/reports?type=retention&period_id='.$paper.'&export=csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('Not tracked for imported paper responses', $csv);
    }

    public function test_employee_history_does_not_claim_paper_completion_or_offer_resubmission(): void
    {
        $paper = $this->paperPeriod();
        // Simulate a legacy/manual reopening to verify both the view and service defenses independently.
        DB::table('assessment_periods')->where('id', $paper)->update(['status' => 'active']);
        $this->actingAs(User::where('email', 'employee@tcgc-demo.local')->firstOrFail());
        $html = $this->get('/my-assessments')->assertOk()->getContent();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $rows = $xpath->query('//tr[td/strong[normalize-space(.)="Imported paper assessment history"]]');
        $this->assertCount(1, $rows);
        $this->assertStringContainsString('Paper form import; completion not linked', $rows->item(0)->textContent);
        $this->assertStringNotContainsString('Not completed', $rows->item(0)->textContent);
        $this->assertSame(0, $xpath->query('.//a[contains(@href,"take=")]', $rows->item(0))->count());
        $this->get('/my-assessments?take='.$paper)->assertOk()
            ->assertSee('Paper form import; completion not linked')->assertDontSee('Submit Assessment');
    }

    public function test_online_submission_cannot_mix_with_imported_paper_responses_even_after_reopening(): void
    {
        $paper = $this->paperPeriod();
        DB::table('assessment_periods')->where('id', $paper)->update(['status' => 'active']);
        $question = DB::table('survey_questions')->insertGetId([
            'period_id' => $paper, 'factor_id' => DB::table('hrm_factors')->value('id'), 'question_text' => 'Guard test question',
        ]);
        $before = [];
        foreach (['survey_responses', 'survey_response_items', 'survey_participation', 'paper_response_imports'] as $table) {
            $before[$table] = DB::table($table)->count();
        }
        $this->actingAs(User::where('email', 'employee@tcgc-demo.local')->firstOrFail());
        $this->post('/my-assessments', ['period_id' => $paper, 'q' => [$question => 3]])->assertSessionHasErrors('period_id');
        $this->assertStringContainsString('imported paper responses', session('errors')->first('period_id'));
        foreach ($before as $table => $count) {
            $this->assertSame($count, DB::table($table)->count(), "{$table} must remain unchanged.");
        }
    }

    public function test_existing_online_installations_render_while_the_provenance_migration_is_pending(): void
    {
        Schema::drop('paper_response_imports');
        $this->assertFalse(period_has_paper_responses(db(), 2));
        $this->assertIsFloat(participation_rate(db(), 2));
        $this->actingAs(User::where('email', 'employee@tcgc-demo.local')->firstOrFail());
        $this->get('/my-assessments')->assertOk()->assertSee('Take assessment');
    }
}
