<?php

namespace Tests\Feature\Filament;

use App\Enums\Page\Status;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use SolutionForest\FilamentTranslateField\Facades\FilamentTranslateField;
use Tests\Support\FilamentPanelTestCase;

class PageTranslationsTest extends FilamentPanelTestCase
{
    use RefreshDatabase;

    public function test_provider_registers_the_configured_locales(): void
    {
        $this->assertSame(
            config('app.supported_locales'),
            FilamentTranslateField::getDefaultLocales(),
        );
    }

    public function test_create_form_renders_controls_for_every_configured_locale(): void
    {
        $this->authenticateEditor();

        $component = Livewire::test(CreatePage::class)
            ->assertSuccessful();

        foreach ($this->locales() as $locale) {
            $component
                ->assertFormFieldExists("title.{$locale}")
                ->assertFormFieldExists("content.{$locale}")
                ->assertSee(
                    FilamentTranslateField::getLocaleLabel($locale, $locale)
                        ?? $locale,
                );
        }
    }

    public function test_edit_form_includes_locales_without_existing_translations(): void
    {
        $user = $this->authenticateEditor();
        $locale = $this->locales()[0];

        $page = Page::factory()->create([
            'creator_id' => $user->id,
            'title' => [$locale => 'Existing title'],
            'content' => [$locale => '<p>Existing content</p>'],
        ]);

        $component = Livewire::test(EditPage::class, [
            'record' => $page->getRouteKey(),
        ])->assertSuccessful();

        $this->assertHydratedTranslations($component, [
            'title' => [$locale => 'Existing title'],
            'content' => [$locale => '<p>Existing content</p>'],
        ]);

        foreach ($this->locales() as $supportedLocale) {
            $component
                ->assertFormFieldExists("title.{$supportedLocale}")
                ->assertFormFieldExists("content.{$supportedLocale}")
                ->assertSee(
                    FilamentTranslateField::getLocaleLabel(
                        $supportedLocale,
                        $supportedLocale,
                    ) ?? $supportedLocale,
                );
        }
    }

    public function test_create_persists_all_locale_payloads(): void
    {
        $user = $this->authenticateEditor();
        $payload = $this->translationPayload('Created');

        Livewire::test(CreatePage::class)
            ->fillForm([
                ...$payload,
                'status' => Status::Draft->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $page = Page::query()->sole();

        $this->assertSame($user->id, $page->creator_id);
        $this->assertTranslations($page, $payload);
    }

    public function test_edit_loads_saves_and_reopens_all_locale_payloads(): void
    {
        $user = $this->authenticateEditor();
        $original = $this->translationPayload('Original');
        $updated = $this->translationPayload('Updated');

        $page = Page::factory()->create([
            'creator_id' => $user->id,
            ...$original,
        ]);

        $this->get(PageResource::getUrl('edit', [
            'record' => $page->getRouteKey(),
        ]))->assertOk();

        $component = Livewire::test(EditPage::class, [
            'record' => $page->getRouteKey(),
        ])->assertSuccessful();

        $this->assertHydratedTranslations($component, $original);

        $component
            ->fillForm($updated)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTranslations($page->refresh(), $updated);

        $reopened = Livewire::test(EditPage::class, [
            'record' => $page->getRouteKey(),
        ])->assertSuccessful();

        $this->assertHydratedTranslations($reopened, $updated);
    }

    private function authenticateEditor(): User
    {
        $user = User::factory()->create();

        $this->grantPermissions($user, [
            'view_any_page',
            'view_page',
            'create_page',
            'update_page',
        ]);

        $this->actingAs($user);

        return $user;
    }

    /**
     * @return non-empty-list<string>
     */
    private function locales(): array
    {
        $locales = config('app.supported_locales');

        $this->assertIsArray($locales);

        $validated = [];

        foreach ($locales as $locale) {
            $this->assertIsString($locale);
            $validated[] = $locale;
        }

        if ($validated === []) {
            throw new \LogicException('Supported locales must not be empty.');
        }

        return $validated;
    }

    /**
     * @return array{title: array<string, string>, content: array<string, string>}
     */
    private function translationPayload(string $prefix): array
    {
        $payload = [
            'title' => [],
            'content' => [],
        ];

        foreach ($this->locales() as $locale) {
            $payload['title'][$locale] = "{$prefix} title {$locale}";
            $payload['content'][$locale] = "<p>{$prefix} content {$locale}</p>";
        }

        return $payload;
    }

    /**
     * @template TComponent of CreatePage|EditPage
     *
     * @param  Testable<TComponent>  $component
     * @param  array{title: array<string, string>, content: array<string, string>}  $payload
     */
    private function assertHydratedTranslations(
        Testable $component,
        array $payload,
    ): void {
        foreach ($payload['title'] as $locale => $title) {
            $component->assertFormSet(["title.{$locale}" => $title]);
        }

        foreach ($payload['content'] as $locale => $html) {
            $state = $component->get("data.content.{$locale}");

            $this->assertIsArray($state);

            $text = '';

            array_walk_recursive(
                $state,
                function (mixed $value, string|int $key) use (&$text): void {
                    if ($key === 'text' && is_string($value)) {
                        $text .= $value;
                    }
                },
            );

            $this->assertSame(strip_tags($html), $text);
        }
    }

    /**
     * @param  array{title: array<string, string>, content: array<string, string>}  $payload
     */
    private function assertTranslations(Page $page, array $payload): void
    {
        foreach (['title', 'content'] as $attribute) {
            $expected = $payload[$attribute];
            $actual = $page->getTranslations($attribute);

            ksort($expected);
            ksort($actual);

            $this->assertSame($expected, $actual);
        }
    }
}
