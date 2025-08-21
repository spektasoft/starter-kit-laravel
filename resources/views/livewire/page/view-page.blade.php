<div>
    <x-header :breadcrumbs="$this->getBreadcrumbs()" :actions="$this->getActions()">
        {{ $page->title }}
    </x-header>

    <x-container>
        <section class="flex flex-col items-center min-h-screen pt-6 sm:pt-0">
            <div
                class="w-full p-6 mt-6 overflow-hidden prose bg-white shadow-md sm:max-w-2xl dark:bg-gray-900 sm:rounded-lg dark:prose-invert">
                {!! str($page->content)->sanitizeHtml() !!}
            </div>
        </section>
    </x-container>
</div>
