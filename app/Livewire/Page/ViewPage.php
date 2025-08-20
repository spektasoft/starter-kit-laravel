<?php

namespace App\Livewire\Page;

use App\Models\Page;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class ViewPage extends Component
{
    public Page $page;

    public function mount(Page $record): void
    {
        $description = Str::limit(strip_tags($record->content), 160, '…');

        SEOTools::setTitle($record->title);
        SEOTools::setDescription($description);

        $this->page = $record;
    }

    /**
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            route('home') => __('navigation-menu.menu.home'),
            0 => trans_choice('page.resource.model_label', 2),
            1 => $this->page->title,
        ];

        return $breadcrumbs;
    }

    public function render(): View
    {
        return view('livewire.page.view-page');
    }
}
