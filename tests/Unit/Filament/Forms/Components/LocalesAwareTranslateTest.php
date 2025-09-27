<?php

namespace Tests\Unit\Filament\Forms\Components;

use App\Forms\Components\LocalesAwareTranslate;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Expectation;
use ReflectionProperty;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;
use stdClass;
use Tests\TestCase;

class LocalesAwareTranslateTest extends TestCase
{
    // Integrates Mockery with PHPUnit's test lifecycle for automatic verification and cleanup.
    use MockeryPHPUnitIntegration;

    /**
     * Test that the `locales` closure returns sorted locales when the record is `null`.
     */
    public function test_locales_closure_returns_sorted_locales_when_record_is_null(): void
    {
        // Arrange: Set up the application's locale and supported locales configuration.
        app()->setLocale('en');
        config()->set('app.supported_locales', ['fr', 'de', 'en']);

        // The expected result is a Collection with the current locale ('en') moved to the front.
        $expectedLocales = collect(['en', 'fr', 'de']);

        $component = LocalesAwareTranslate::make([]);
        $localesClosure = $this->getLocalesClosure($component);

        // Act: Invoke the closure with a null record, simulating a "create" form context.
        /** @var Collection<int, string> $result */
        $result = $localesClosure(null);

        // Assert: The closure should fall back to returning the default sorted locales.
        // We use assertEquals because we are comparing the contents of the collections, not the object instances.
        $this->assertEquals($expectedLocales->toArray(), $result->values()->toArray());
    }

    /**
     * Test that the closure returns sorted locales when the record does not have the method.
     */
    public function test_locales_closure_returns_sorted_locales_when_record_lacks_method(): void
    {
        // Arrange: Set up the application's locale and supported locales configuration.
        app()->setLocale('es');
        config()->set('app.supported_locales', ['en', 'fr', 'es']);

        // The expected result is a Collection with the current locale ('es') moved to the front.
        $expectedLocales = collect(['es', 'en', 'fr']);

        $component = LocalesAwareTranslate::make([]);
        $record = new stdClass;
        $localesClosure = $this->getLocalesClosure($component);

        // Act: Invoke the closure with the plain object.
        /** @var Collection<int, string> $result */
        $result = $localesClosure($record);

        // Assert: The closure should fall back to returning the default sorted locales.
        $this->assertEquals($expectedLocales->toArray(), $result->values()->toArray());
    }

    /**
     * Test that the closure returns the expected array from the record when the method exists.
     */
    public function test_locales_closure_returns_prioritized_locales_when_method_exists(): void
    {
        // Arrange: Create a mock record that has the getPrioritizedLocales method.
        // This test does not depend on the config or app locale, as the mock bypasses `getSortedLocales`.
        $component = LocalesAwareTranslate::make([]);
        $prioritizedLocales = ['fr', 'de'];

        $record = Mockery::mock(new class
        {
            /**
             * @return array<string>
             */
            public function getPrioritizedLocales(): array
            {
                // This body is irrelevant because Mockery will override it.
                return [];
            }
        });

        // Mockery will now override the real method on the anonymous class.
        /** @var Expectation */
        $expectation = $record->shouldReceive('getPrioritizedLocales');
        $expectation->once();
        $expectation->andReturn($prioritizedLocales);

        $localesClosure = $this->getLocalesClosure($component);

        // Act: Invoke the closure with the mock record.
        $result = $localesClosure($record);

        // Assert: The closure should return the specific array provided by the record's method.
        $this->assertSame($prioritizedLocales, $result);
    }

    /**
     * Helper method to extract the protected 'locales' closure from the component.
     * The component's setUp() method, which defines this closure, is invoked
     * when the component is instantiated via the static make() method.
     *
     * @throws \ReflectionException
     */
    private function getLocalesClosure(LocalesAwareTranslate $component): \Closure
    {
        // The 'locales' property is defined in the parent Translate class.
        $reflectionProperty = new ReflectionProperty(Translate::class, 'locales');
        $reflectionProperty->setAccessible(true);

        /** @var \Closure */
        $closure = $reflectionProperty->getValue($component);

        return $closure;
    }
}
