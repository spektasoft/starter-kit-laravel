<?php

namespace Tests\Unit\Concerns;

use App\Concerns\HandlesTranslatableAttributes;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Mockery;
use Tests\TestCase;

class TestModel extends Model
{
    use HandlesTranslatableAttributes;

    protected $table = 'test_models'; // Dummy table name

    protected $guarded = []; // Allow mass assignment for testing

    /**
     * @var ?callable
     */
    public $mockedGetTranslatableAttributes;

    /**
     * @var ?callable
     */
    public $mockedGetTranslations;

    /**
     * Static property to hold mocked sorted locales
     *
     * @var array<string>
     */
    public static ?array $testSortedLocales = null;

    /**
     * @return array<string>
     */
    public function getTranslatableAttributes(): array
    {
        if ($this->mockedGetTranslatableAttributes) {
            /** @var array<string> */
            $attributes = call_user_func($this->mockedGetTranslatableAttributes);

            return $attributes;
        }

        return ['name', 'description']; // Default translatable attributes for testing
    }

    /**
     * @return array<string>
     */
    public function getTranslations(string $attribute): array
    {
        if ($this->mockedGetTranslations) {
            /** @var array<string> */
            $translations = call_user_func($this->mockedGetTranslations, $attribute);

            return $translations;
        }

        return []; // Default empty translations
    }

    /**
     * Override the trait's static method for testing
     *
     * @return Collection<int, string>
     */
    public static function getSortedLocales(): Collection
    {
        if (self::$testSortedLocales !== null) {
            return collect(self::$testSortedLocales);
        }
        // If not mocked, throw an error to ensure it's always mocked for these tests.
        throw new \Exception('static::getSortedLocales() was not mocked for the test.');
    }
}

class HandlesTranslatableAttributesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Define a test model that uses the trait
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string>  $mockedSortedLocales
     */
    protected function createTestModel(
        array $attributes = [],
        ?callable $getTranslatableAttributesMock = null,
        ?callable $getTranslationsMock = null,
        ?array $mockedSortedLocales = null
    ): TestModel {
        $model = new TestModel;
        $model->fill($attributes);
        $model->mockedGetTranslatableAttributes = $getTranslatableAttributesMock;
        $model->mockedGetTranslations = $getTranslationsMock;

        // Set the static property on the anonymous class
        if ($mockedSortedLocales !== null) {
            $model::$testSortedLocales = $mockedSortedLocales;
        } else {
            // Ensure it's reset for each test if not provided
            $model::$testSortedLocales = null;
        }

        return $model;
    }

    public function test_returns_all_system_locales_in_default_order_for_new_model_instance(): void
    {
        // Arrange
        $systemLocales = ['en', 'es', 'fr'];

        // No appMock setup needed as getLocales is mocked via createTestModel
        // and getLocale is not called in this specific test case.

        $model = $this->createTestModel(mockedSortedLocales: $systemLocales);

        // Act
        $prioritizedLocales = $model->getPrioritizedLocales();

        // Assert
        $this->assertEquals($systemLocales, $prioritizedLocales->toArray());
    }

    public function test_moves_current_app_locale_to_front_if_it_has_content(): void
    {
        // Arrange
        $systemLocales = ['en', 'es', 'fr'];
        $currentLocale = 'es';

        $appMock = Mockery::mock(Application::class);
        /** @var \Mockery\Expectation */
        $expectation = $appMock->shouldReceive('getLocale');
        $expectation->andReturn($currentLocale);
        /** @var \Illuminate\Contracts\Foundation\Application $appMock */
        Facade::setFacadeApplication($appMock);

        $model = $this->createTestModel(
            getTranslationsMock: function (string $attribute) use ($currentLocale) {
                if ($attribute === 'name') {
                    return [$currentLocale => 'some content'];
                }

                return [];
            },
            mockedSortedLocales: $systemLocales // Pass system locales here
        );

        // Act
        $prioritizedLocales = $model->getPrioritizedLocales();

        // Assert
        $expectedOrder = ['es', 'en', 'fr'];
        $this->assertEquals($expectedOrder, $prioritizedLocales->toArray());
    }

    public function test_prioritizes_locales_with_content_when_current_app_locale_has_no_content(): void
    {
        // Arrange
        $systemLocales = ['en', 'es', 'fr', 'de'];
        $currentLocale = 'de'; // Current locale has no content
        $contentLocales = ['en', 'es']; // These locales have content

        $appMock = Mockery::mock(Application::class);
        /** @var \Mockery\Expectation */
        $expectation = $appMock->shouldReceive('getLocale');
        $expectation->andReturn($currentLocale);
        /** @var \Illuminate\Contracts\Foundation\Application $appMock */
        Facade::setFacadeApplication($appMock);

        $model = $this->createTestModel(
            getTranslationsMock: function (string $attribute) use ($contentLocales) {
                if ($attribute === 'name') {
                    $translations = [];
                    foreach ($contentLocales as $locale) {
                        $translations[$locale] = "content for {$locale}";
                    }

                    return $translations;
                }

                return [];
            },
            mockedSortedLocales: $systemLocales // Pass system locales here
        );

        // Act
        $prioritizedLocales = $model->getPrioritizedLocales();

        // Assert
        $expectedOrder = ['en', 'es', 'fr', 'de'];
        $this->assertEquals($expectedOrder, $prioritizedLocales->toArray());
    }

    public function test_returns_all_system_locales_when_no_translatable_content_at_all(): void
    {
        // Arrange
        $systemLocales = ['en', 'es', 'fr'];

        // No appMock setup needed as getLocales is mocked via createTestModel
        // and getLocale is not called in this specific test case.

        $model = $this->createTestModel(
            getTranslationsMock: function (string $attribute) {
                return []; // No content for any locale
            },
            mockedSortedLocales: $systemLocales // Pass system locales here
        );

        // Act
        $prioritizedLocales = $model->getPrioritizedLocales();

        // Assert
        $this->assertEquals($systemLocales, $prioritizedLocales->toArray());
    }

    public function test_get_available_locales_identifies_locales_with_content(): void
    {
        // Arrange
        $model = $this->createTestModel(
            getTranslationsMock: function (string $attribute) {
                return match ($attribute) {
                    'title' => [
                        'en' => 'English Title',
                        'es' => 'Título Español',
                        'fr' => null,
                        'de' => '',
                        'it' => ' ',
                        'pt' => '0', // Edge case: "0" should be considered content
                        'ja' => '<p></p>', // Edge case: empty Tiptap tag should not be content
                    ],
                    'body' => [
                        'en' => 'English Body',
                        'es' => null,
                        'fr' => 'French Body',
                        'de' => 'German Body',
                        'it' => 'Italian Body',
                        'pt' => null,
                        'ja' => 'Japanese Body',
                    ],
                    default => [],
                };
            },
            getTranslatableAttributesMock: fn () => ['title', 'body'],
            mockedSortedLocales: ['en', 'es', 'fr', 'de', 'it', 'pt', 'ja']
        );

        // Act
        $reflection = new \ReflectionClass($model);
        $method = $reflection->getMethod('getAvailableLocales');
        $method->setAccessible(true);
        $availableLocales = $method->invoke($model);

        // Assert
        $this->assertEqualsCanonicalizing(
            ['en', 'es', 'fr', 'de', 'it', 'pt', 'ja'], // 'ja' is included because of 'Japanese Body'
            $availableLocales
        );
    }

    public function test_get_available_locales_ignores_null_empty_and_whitespace_values(): void
    {
        // Arrange
        $model = $this->createTestModel(
            getTranslationsMock: function (string $attribute) {
                return match ($attribute) {
                    'title' => [
                        'en' => 'English Title',
                        'es' => null,
                        'fr' => '',
                        'de' => ' ',
                    ],
                    default => [],
                };
            },
            getTranslatableAttributesMock: fn () => ['title'],
            mockedSortedLocales: ['en', 'es', 'fr', 'de']
        );

        // Act
        $reflection = new \ReflectionClass($model);
        $method = $reflection->getMethod('getAvailableLocales');
        $method->setAccessible(true);
        $availableLocales = $method->invoke($model);

        // Assert
        $this->assertEqualsCanonicalizing(
            ['en'],
            $availableLocales
        );
    }

    public function test_get_available_locales_considers_zero_as_content(): void
    {
        // Arrange
        $model = $this->createTestModel(
            getTranslationsMock: function (string $attribute) {
                return match ($attribute) {
                    'title' => [
                        'en' => '0',
                        'es' => '',
                        'fr' => '',
                        'de' => '',
                    ],
                    default => [],
                };
            },
            getTranslatableAttributesMock: fn () => ['title'],
            mockedSortedLocales: ['en', 'es']
        );

        // Act
        $reflection = new \ReflectionClass($model);
        $method = $reflection->getMethod('getAvailableLocales');
        $method->setAccessible(true);
        $availableLocales = $method->invoke($model);

        // Assert
        $this->assertEqualsCanonicalizing(
            ['en'],
            $availableLocales
        );
    }

    public function test_get_available_locales_ignores_empty_tiptap_tags(): void
    {
        // Arrange
        $model = $this->createTestModel(
            getTranslationsMock: function (string $attribute) {
                return match ($attribute) {
                    'body' => [
                        'en' => '<p></p>',
                        'es' => '<div></div>',
                        'fr' => '<p>  </p>', // Whitespace inside tag
                        'de' => '<p><br></p>', // Common empty Tiptap content
                        'it' => 'Some actual content',
                    ],
                    default => [],
                };
            },
            getTranslatableAttributesMock: fn () => ['body'],
            mockedSortedLocales: ['en', 'es', 'fr', 'de', 'it']
        );

        // Act
        $reflection = new \ReflectionClass($model);
        $method = $reflection->getMethod('getAvailableLocales');
        $method->setAccessible(true);
        $availableLocales = $method->invoke($model);

        // Assert
        $this->assertEqualsCanonicalizing(
            ['it'],
            $availableLocales
        );
    }
}
