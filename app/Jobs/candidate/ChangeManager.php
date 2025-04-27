<?php

namespace App\Jobs\candidate;

use App\Models\Candidate;
use App\Models\Customer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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

    }
}
