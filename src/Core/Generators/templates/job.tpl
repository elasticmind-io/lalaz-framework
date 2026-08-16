<?php declare(strict_types=1);

namespace {{namespace}};

use Lalaz\Queue\Job;

class {{name}}Job extends Job
{
    public function handle(array $payload): void
    {
        // Handle the job here
    }
}
