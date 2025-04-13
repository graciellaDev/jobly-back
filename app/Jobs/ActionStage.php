<?php

namespace App\Jobs;

use App\Mail\register\Success;
use App\Models\Candidate;
use Carbon\Carbon;
use http\Env\Request;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ActionStage implements ShouldQueue
{
    use Queueable;

    protected Candidate $candidate;

    /**
     * Create a new job instance.
     */
    public function __construct(Candidate $candidate)
    {
        $this->candidate = $candidate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->candidate->stage_id = 2;
        $this->candidate->save();
        $dataEmail = [
            'subject' => 'Приглашение на собеседование',
            'name' => $this->candidate->name
        ];
        Mail::to($this->candidate->email)->send(new Success($dataEmail));
        echo $this->candidate->email;
        Log::channel('inviteCandidate')->info('Приглашение кандидата на собеседование', ['time' =>
            Carbon::now()]);
    }
}
