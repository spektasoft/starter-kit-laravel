<?php

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

trait AssertsLivewireEmbeds
{
    /**
     * Assert that each Livewire component is embedded in the response.
     *
     * @param  TestResponse<Response>  $response
     */
    protected function assertSeesLivewire(TestResponse $response, string ...$components): void
    {
        foreach ($components as $component) {
            $response->assertSeeLivewire($component); // @phpstan-ignore method.notFound
        }
    }

    /**
     * Assert that none of the Livewire components are embedded in the response.
     *
     * @param  TestResponse<Response>  $response
     */
    protected function assertDoesNotSeeLivewire(TestResponse $response, string ...$components): void
    {
        foreach ($components as $component) {
            $response->assertDontSeeLivewire($component); // @phpstan-ignore method.notFound
        }
    }
}
