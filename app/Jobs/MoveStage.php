<?php

namespace App\Jobs;

use App\Mail\register\Success;
use App\Models\Candidate;
use App\Models\Stage;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class MoveStage implements ShouldQueue
{
    use Queueable;
    private Candidate $candidate;
    private Stage $stage;

    /**
     * Create a new job instance.
     */
    public function __construct(Candidate $candidate, Stage $stage)
    {
        $this->candidate = $candidate;
        $this->stage = $stage;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->candidate->stage_id = $this->stage->id;
            $this->candidate->save();

            Log::channel('stageCandidate')->info('Перемещение кандидата на этап ' . $this->stage->name, ['time' =>
                Carbon::now()]);
        } catch (Exception $error) {
            Log::channel('stageCandidate')->info('Ошибка  - переместить кандидата на этап ' . $this->stage->name, ['time' =>
                Carbon::now(), 'name' => $this->candidate->surname, 'error' => $error->getMessage()]);
        }
    }
}
