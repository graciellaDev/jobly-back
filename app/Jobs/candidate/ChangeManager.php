<?php

namespace App\Jobs\candidate;

use App\Models\Candidate;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class ChangeManager implements ShouldQueue
{
    use Queueable;

    protected Candidate $candidate;
    protected Customer $manager;

    /**
     * Create a new job instance.
     */
    public function __construct(Candidate $candidate, Customer $manager)
    {
        $this->candidate = $candidate;
        $this->manager = $manager;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $candidate = $this->candidate->toArray();
        try {
            if ($this->manager->role_id == 1) {
                $this->candidate->manager()->associate(null);
            } else {
                $this->candidate->manager()->associate($this->manager);
            }
            $this->candidate->save();

            Log::channel('changeManagerCandidate')->info('Смена ответственного для кандидата', ['time' =>
                Carbon::now(), 'candidate' => $candidate['surname'] . ' ' . $candidate['firstname'] . ' ' . $candidate['patronymic']]);
        } catch (Exception $error) {
            Log::channel('changeManagerCandidate')->info('Ошибка смены ответственного для кандидата', ['time' =>
                Carbon::now(), 'candidate' => $candidate['surname'] . ' ' . $candidate['firstname'] . ' ' .
                $candidate['patronymic'], 'error' => $error->getMessage()]);
        }
    }
}
