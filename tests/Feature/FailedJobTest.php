<?php

namespace Tests\Feature;

use App\Jobs\FailedJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FailedJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_is_failed(): void
    {
        $job = (new FailedJob)->withFakeQueueInteractions();
        $job->handle();
        $job->assertFailed();
    }

    public function test_job_is_dispatched_to_queue(): void
    {
        Queue::fake();
        FailedJob::dispatch();
        Queue::assertPushed(FailedJob::class);
    }
}
