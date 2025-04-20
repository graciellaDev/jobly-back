<?php

namespace App\Jobs\candidate;

use App\Mail\action\RefuseCandidate;
use App\Models\Candidate;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery\Exception;

class Refuse implements ShouldQueue
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
        $candidate = $this->candidate->toArray();
        try {
            $this->candidate->stage_id = 3;
            $this->candidate->save();
            $dataEmail = [
                'subject' => 'Работадатель не готов пригласить на сообеседование',
                'name' => $candidate['surname'] . ' ' . $candidate['firstname'] . ' ' . $candidate['patronymic']
            ];

            Mail::to($candidate['email'])->send(new RefuseCandidate($dataEmail));
            Log::channel('refuseCandidate')->info('Отказать кандидату', ['time' =>
                Carbon::now(), 'candidate' => $candidate['surname'] . ' ' . $candidate['firstname'] . ' ' . $candidate['patronymic']]);
        } catch (Exception $error) {
            Log::channel('refuseCandidate')->info('Ошибка задачи - отказать кандидату', ['time' =>
                Carbon::now(), 'candidate' => $candidate['surname'] . ' ' . $candidate['firstname'] . ' ' .
                $candidate['patronymic'], 'error' => $error->getMessage()]);
        }
    }
}
