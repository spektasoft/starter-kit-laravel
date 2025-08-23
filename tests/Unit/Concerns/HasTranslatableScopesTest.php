<?php

namespace Tests\Unit\Concerns;

use App\Concerns\HasLocales;
use App\Concerns\HasTranslatableScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Translatable\HasTranslations;
use Tests\TestCase;

// Dummy model for testing the trait
class TranslatableTestModel extends Model
{
    use HasLocales;
    use HasTranslatableScopes;
    use HasTranslations;

    protected $table = 'translatable_test_models'; // Define a table name

    protected $guarded = []; // Allow mass assignment for testing

    /**
     * @var array<string>
     */
    public $translatable = ['name']; // Define a translatable attribute
}

class HasTranslatableScopesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set supported locales for testing
        config()->set('app.supported_locales', ['en', 'es']);

        // Ensure the table exists before running tests
        Schema::create('translatable_test_models', function (Blueprint $table) {
            $table->id();
            $table->json('name'); // Translatable column
            $table->timestamps();
        });
    }

    public function test_finds_record_by_translatable_string_on_mysql(): void
    {
        // Test setup: Create a model with translated name
        TranslatableTestModel::create([
            'name' => [
                'en' => 'Hello World',
                'es' => 'Hola Mundo',
            ],
        ]);

        // Test: Search for 'Hello' in English
        $found = TranslatableTestModel::whereTranslatable('name', 'Hello')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Hello World', $found->getTranslation('name', 'en'));

        // Test: Search for 'Mundo' in Spanish
        $found = TranslatableTestModel::whereTranslatable('name', 'Mundo')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Hola Mundo', $found->getTranslation('name', 'es'));
    }

    public function test_handles_case_insensitive_search(): void
    {
        TranslatableTestModel::create([
            'name' => [
                'en' => 'Case Sensitive Test',
            ],
        ]);

        // Test: Search with different casing
        $found = TranslatableTestModel::whereTranslatable('name', 'case sensitive test')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Case Sensitive Test', $found->getTranslation('name', 'en'));

        $found = TranslatableTestModel::whereTranslatable('name', 'CASE SENSITIVE TEST')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Case Sensitive Test', $found->getTranslation('name', 'en'));
    }

    public function test_handles_partial_matches(): void
    {
        TranslatableTestModel::create([
            'name' => [
                'en' => 'Searching for something',
            ],
        ]);

        // Test: Partial match
        $found = TranslatableTestModel::whereTranslatable('name', 'search')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Searching for something', $found->getTranslation('name', 'en'));

        $found = TranslatableTestModel::whereTranslatable('name', 'something')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Searching for something', $found->getTranslation('name', 'en'));
    }

    public function test_returns_no_results_for_non_matching_search_term(): void
    {
        TranslatableTestModel::create([
            'name' => [
                'en' => 'Unique String',
            ],
        ]);

        // Test: Non-matching search term
        $found = TranslatableTestModel::whereTranslatable('name', 'NonExistent')->first();
        $this->assertNull($found);
    }
}
