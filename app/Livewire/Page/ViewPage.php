<?php

namespace App\Livewire\Page;

use App\Enums\Page\Status;
use App\Filament\Resources\PageResource;
use App\Models\Page;
use Artesaos\SEOTools\Facades\SEOTools;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

class ViewPage extends Component
{
    public Page $page;

    public function mount(Page $record): void
    {
        if ($record->status !== Status::Publish && ! Gate::allows('update', $record)) {
            abort(404);
        }

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

    /**
     * @return array<Action>
     */
    public function getActions(): array
    {
        $actions = [];
        $actions[] = Action::make('edit')
            ->authorize(PageResource::canEdit($this->page))
            ->label(__('page.action.edit'))
            ->url(route('filament.admin.resources.pages.edit', $this->page));

        return $actions;
    }

    public function render(): View
    {
        return view('livewire.page.view-page');
    }
}
