<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InstallQuestionnaire extends Command
{
    protected $signature = 'retention:install-questionnaire {period : Existing draft assessment period ID}';

    protected $description = 'Install the approved 56 questions into an unused draft four-point assessment';

    public function handle(): int
    {
        $periodId = filter_var($this->argument('period'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($periodId === false) {
            $this->error('Supply an existing draft assessment period ID.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($periodId): void {
                $period = DB::table('assessment_periods')->where('id', $periodId)->lockForUpdate()->first();
                if (! $period || $period->status !== 'draft' || (int) $period->scale_min !== 1 || (int) $period->scale_max !== 4) {
                    throw new RuntimeException('Choose an existing draft assessment with scale minimum 1 and maximum 4.');
                }
                if (DB::table('survey_questions')->where('period_id', $periodId)->exists()
                    || DB::table('survey_responses')->where('period_id', $periodId)->exists()
                    || DB::table('survey_participation')->where('period_id', $periodId)->exists()) {
                    throw new RuntimeException('The assessment must have no existing questions, responses, or participation. Existing records were preserved.');
                }
                $bank = config('instrument.questions');
                if (! is_array($bank) || count($bank) !== 9 || count(array_filter($bank, 'is_array')) !== 9
                    || array_sum(array_map('count', $bank)) !== 56) {
                    throw new RuntimeException('The approved instrument configuration must contain nine measures and 56 questions.');
                }
                $factors = DB::table('hrm_factors')->whereIn('name', array_keys($bank))->get()->keyBy('name');
                foreach ($bank as $factorName => $questions) {
                    $factor = $factors->get($factorName);
                    if (! $factor || $factor->status !== 'active' || (int) $factor->is_outcome !== (int) ($factorName === 'Employee Retention')
                        || count($questions) !== ($factorName === 'Employee Retention' ? 8 : 6)) {
                        throw new RuntimeException('Initialize all nine approved active measures with Employee Retention as the outcome before installing questions.');
                    }
                }
                $order = 1;
                foreach ($bank as $factorName => $questions) {
                    foreach ($questions as $question) {
                        DB::table('survey_questions')->insert([
                            'period_id' => $periodId, 'factor_id' => $factors[$factorName]->id, 'question_text' => $question,
                            'display_order' => $order++, 'is_required' => 1, 'created_at' => now(),
                        ]);
                    }
                }
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Installed 56 approved questions into draft assessment {$periodId}. No responses were created.");
        $this->line('Review the questionnaire, dates, instructions, and four-point scale labels in Assessments before activating it.');

        return self::SUCCESS;
    }
}
