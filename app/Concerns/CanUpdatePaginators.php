<?php

namespace App\Concerns;

trait CanUpdatePaginators
{
    protected string $scrollToElement = 'body';

    public function updatedPaginators(int $page, string $pageName = 'page'): void
    {
        $this->dispatch('scroll-to', [
            'element' => $this->scrollToElement,
        ]);
    }
}
