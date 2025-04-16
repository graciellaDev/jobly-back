<?php

namespace App\Jobs;

use App\Mail\action\InviteCandidate;
use App\Models\Candidate;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery\Exception;

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
        try {
            $candidate = $this->candidate->toArray();
            $this->candidate->stage_id = 2;
            $this->candidate->save();
            $dataEmail = [
                'subject' => 'Приглашение на собеседование',
                'name' => $candidate['surname'] . ' ' . $candidate['firstname'] . ' ' . $candidate['patronymic']
            ];
            Mail::to($candidate['email'])->send(new InviteCandidate($dataEmail));
            Log::channel('inviteCandidate')->info('Приглашение кандидата на собеседование', ['time' =>
                Carbon::now(), 'candidate' => $candidate['surname'] . ' ' . $candidate['firstname'] . ' ' . $candidate['patronymic']]);
        } catch (Exception $error) {
            Log::channel('inviteCandidate')->info('Ошибка задачи - пригласить кандидата', ['time' =>
                Carbon::now(), 'candidate' => $candidate['surname'] . ' ' . $candidate['firstname'] . ' ' .
                $candidate['patronymic'], 'error' => $error->getMessage()]);
        }
    }
}
