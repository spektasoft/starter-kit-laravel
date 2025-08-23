<?php

namespace Tests\Unit\Filament\Tables\Columns;

use App\Filament\Tables\Columns\TranslatableTextColumn;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Mockery\Expectation;
use PHPUnit\Framework\TestCase; // Or use Tests\TestCase if in a Laravel project
use ReflectionClass;

class TranslatableTextColumnTest extends TestCase
{
    /**
     * Clean up Mockery expectations after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_is_not_searchable_by_default(): void
    {
        $column = TranslatableTextColumn::make('name');

        $this->assertFalse($column->isSearchable());
    }

    public function test_applies_custom_translatable_search_logic_when_searchable_is_enabled_without_a_custom_query(): void
    {
        // Arrange
        $column = TranslatableTextColumn::make('title')->searchable();

        // Use reflection to access the protected searchQuery property from the parent class
        $reflection = new ReflectionClass($column);
        $property = $reflection->getProperty('searchQuery');
        $property->setAccessible(true);
        $queryClosure = $property->getValue($column);

        // Assert that our custom closure was set and the column is searchable
        $this->assertTrue($column->isSearchable());
        $this->assertInstanceOf(Closure::class, $queryClosure);

        // Create a mock query builder
        $builder = Mockery::mock(Builder::class);

        // Set expectation: whereTranslatable should be called with the correct column name and search term
        /** @var Expectation */
        $expectation = $builder->shouldReceive('whereTranslatable');
        $expectation->once();
        $expectation->with('title', 'test search');
        $expectation->andReturnSelf();

        // Act: Execute the search closure that was set by the searchable() method
        $queryClosure($builder, 'test search');
    }

    public function test_uses_a_developer_provided_custom_search_query_instead_of_the_default(): void
    {
        // Arrange: Create a custom closure for searching
        $customClosure = function (Builder $query, string $search): Builder {
            return $query->where('custom_logic', 'like', "%{$search}%");
        };

        // Apply the custom closure to the column's searchable method
        $column = TranslatableTextColumn::make('description')->searchable(query: $customClosure);

        // Use reflection to get the closure back out
        $reflection = new ReflectionClass($column);
        $property = $reflection->getProperty('searchQuery');
        $property->setAccessible(true);
        $queryClosure = $property->getValue($column);

        // Assert that the stored closure is the exact one we provided
        $this->assertTrue($column->isSearchable());
        $this->assertSame($customClosure, $queryClosure);

        // Create a mock query builder
        $builder = Mockery::mock(Builder::class);

        // Set expectations:
        // 1. Our custom logic method (`where`) should be called.
        /** @var Expectation */
        $expectation = $builder->shouldReceive('where');
        $expectation->once();
        $expectation->with('custom_logic', 'like', '%test search%');
        $expectation->andReturnSelf();

        // 2. The default translatable logic (`whereTranslatable`) should NOT be called.
        $builder->shouldNotReceive('whereTranslatable');

        // Act: Execute the closure
        /** @var Builder<Model> $builder */
        $queryClosure($builder, 'test search');
    }

    public function test_can_be_explicitly_made_not_searchable(): void
    {
        // Arrange & Act
        $column = TranslatableTextColumn::make('name')->searchable(false);

        // Assert
        $this->assertFalse($column->isSearchable());
    }
}
