<?php

namespace App\Console\Commands;

use App\Services\PaperResponseImportService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ImportPaperResponses extends Command
{
    protected $signature = 'retention:import-paper-responses {file : Completed UTF-8 paper-transcription CSV} {--period= : CLOSED historical assessment ID} {--commit : Import validated rows; otherwise perform a dry run only}';

    protected $description = 'Validate or atomically import verified historical paper answers without employee identity links';

    public function handle(PaperResponseImportService $importer): int
    {
        $periodId = filter_var($this->option('period'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($periodId === false) {
            $this->error('Supply --period with an existing CLOSED historical assessment ID.');

            return self::FAILURE;
        }
        try {
            $result = $importer->import((string) $this->argument('file'), $periodId, (bool) $this->option('commit'));
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }
            $this->error('Validation failed. No response sets, answers, provenance records or audit entries were written.');

            return self::FAILURE;
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Import failed and was rolled back. Check the application log; existing records were preserved.');

            return self::FAILURE;
        }
        if ($result['committed']) {
            $this->info("Imported {$result['responses']} paper response sets and {$result['answers']} answers into assessment {$periodId}.");
        } else {
            $this->info("Dry run passed: {$result['responses']} response sets ({$result['answers']} answers) validated for closed assessment {$periodId}. No database records were written.");
            $this->line('Review the transcription against the paper forms, then rerun with --commit to import.');
        }
        $this->line('Source SHA-256: '.$result['source_file_sha256']);
        $this->line('No employees, accounts or participation links were created. Paper codes are stored only as hashes.');
        $this->line('Collection dates are stored at midnight as date-only markers, not recorded historical submission times.');

        return self::SUCCESS;
    }
}
