<?php

namespace App\Jobs\candidate;

use App\Mail\action\NoCallCandidate;
use App\Mail\action\RefuseCandidate;
use App\Models\Candidate;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery\Exception;

class NoCall implements ShouldQueue
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
            $dataEmail = [
                'subject' => 'Не смогли дозвониться',
                'name' => $candidate['surname'] . ' ' . $candidate['firstname'] . ' ' . $candidate['patronymic']
            ];

            Mail::to($candidate['email'])->send(new NoCallCandidate($dataEmail));
            Log::channel('noCallCandidate')->info('Не смогли дозвониться кандидату', ['time' =>
                Carbon::now(), 'candidate' => $candidate['surname'] . ' ' . $candidate['firstname'] . ' ' . $candidate['patronymic']]);
        } catch (Exception $error) {
            Log::channel('noCallCandidate')->info('Ошибка задачи - не смогли дозвониться кандидату', ['time' =>
                Carbon::now(), 'candidate' => $candidate['surname'] . ' ' . $candidate['firstname'] . ' ' .
                $candidate['patronymic'], 'error' => $error->getMessage()]);
        }
    }
}
