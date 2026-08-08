<?php

namespace App\Exports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductImportWorkbookExport implements WithMultipleSheets
{
    /** @return array<int, object> */
    public function sheets(): array
    {
        $products = Product::query()
            ->with(['category', 'brand', 'unit', 'stock'])
            ->orderBy('name')
            ->get();

        return [
            new ProductImportInstructionsSheet,
            new ProductImportProductsSheet($products),
            new ProductReferenceSheet(
                'Categories',
                ['ID', 'Category Name', 'Description'],
                Category::query()->orderBy('name')->get()->map(fn (Category $category) => [
                    $category->id,
                    $category->name,
                    $category->description,
                ]),
            ),
            new ProductReferenceSheet(
                'Brands',
                ['ID', 'Brand Name', 'Description'],
                Brand::query()->orderBy('name')->get()->map(fn (Brand $brand) => [
                    $brand->id,
                    $brand->name,
                    $brand->description,
                ]),
            ),
            new ProductReferenceSheet(
                'Units',
                ['ID', 'Unit Name', 'Short Name'],
                Unit::query()->orderBy('name')->get()->map(fn (Unit $unit) => [
                    $unit->id,
                    $unit->name,
                    $unit->short_name,
                ]),
            ),
        ];
    }
}
