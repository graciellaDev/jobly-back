<?php

namespace App\Jobs\candidate;

use App\Mail\action\InviteCandidate;
use App\Mail\action\NoCallCandidate;
use App\Mail\action\RefuseCandidate;
use App\Models\Candidate;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Can;
use Mockery\Exception;

class SendEmail implements ShouldQueue
{
    use Queueable;
    protected Candidate $candidate;
    protected string $typeEmail;

    /**
     * Create a new job instance.
     */
    public function __construct(Candidate $candidate, string $typeEmail)
    {
        $this->candidate = $candidate;
        $this->typeEmail = $typeEmail;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $candidate = $this->candidate->toArray();
        try {
            $dataEmail = [
                'subject' => '',
                'name' => $candidate['surname'] . ' ' . $candidate['firstname'] . ' ' . $candidate['patronymic']
            ];

            switch ($this->typeEmail) {
                case 'invite':
                    $dataEmail['subject'] = 'Приглашение на собеседование';
                    Mail::to($candidate['email'])->send(new InviteCandidate($dataEmail));
                    break;
                case 'refuse':
                    $dataEmail['subject'] = 'Работадатель не готов пригласить на сообеседование';
                    Mail::to($candidate['email'])->send(new RefuseCandidate($dataEmail));
                    break;
                case 'no-call':
                    $dataEmail['subject'] = 'Не смогли дозвониться';
                    Mail::to($candidate['email'])->send(new NoCallCandidate($dataEmail));
                default:
                    break;
            }

            Log::channel('emailCandidate')->info($dataEmail['subject'], ['time' =>
                Carbon::now(), 'candidate' => $candidate['surname'] . ' ' . $candidate['firstname'] . ' ' . $candidate['patronymic']]);
        } catch (Exception $error) {
            Log::channel('emailCandidate')->info('Ошибка задачи - ' . $dataEmail['subject'], ['time' =>
                Carbon::now(), 'candidate' => $candidate['surname'] . ' ' . $candidate['firstname'] . ' ' .
                $candidate['patronymic'], 'error' => $error->getMessage()]);
        }
    }
}
