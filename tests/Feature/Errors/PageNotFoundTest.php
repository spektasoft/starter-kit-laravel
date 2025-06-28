<?php

namespace Tests\Feature\Errors;

use Tests\TestCase;

class PageNotFoundTest extends TestCase
{
    public function test_returns_a_404_error_page_for_an_unknown_route(): void
    {
        $response = $this->get('/a-route-that-does-not-exist');

        $response->assertNotFound();
        $response->assertSee(__('Not Found'));
    }
}
