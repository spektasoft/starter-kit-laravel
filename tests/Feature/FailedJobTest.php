<?php

namespace Tests\Unit\Jobs;

use App\Jobs\FailedJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FailedJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_is_failed()
    {
        $job = (new FailedJob)->withFakeQueueInteractions();
        $job->handle();
        $job->assertFailed();
    }

    public function test_job_is_dispatched_to_queue()
    {
        Queue::fake();
        FailedJob::dispatch();
        Queue::assertPushed(FailedJob::class);
    }
}
