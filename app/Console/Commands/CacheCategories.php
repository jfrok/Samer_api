<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CacheCategories extends Command
{
    protected $signature = 'cache:categories {--clear : Clear the cache instead of building it}';
    protected $description = 'Cache category hierarchy for faster API navigation';

    public function handle()
    {
        $clear = $this->option('clear');

        if ($clear) {
            Cache::forget('categories_tree');
            $this->info('Category cache cleared!');
            return 0;
        }

        $this->info('Caching categories...');

        // Fetch root categories with recursive children (eager load to avoid N+1)
        $categories = Category::whereNull('parent_id')
            ->with('children')  // Assumes 'children' relation in Category model
            ->get()
            ->map(function ($category) {
                return $this->formatCategory($category);
            });

        // Cache for 2 hours (7200 seconds; categories change less often than products)
        Cache::put('categories_tree', $categories, 7200);

        $this->info("Cached {$categories->count()} root categories successfully!");
        return 0;
    }

    // Recursive formatter to build tree structure
    private function formatCategory($category)
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'children' => $category->children->map(function ($child) {
                return $this->formatCategory($child);
            }),
        ];
    }
}
