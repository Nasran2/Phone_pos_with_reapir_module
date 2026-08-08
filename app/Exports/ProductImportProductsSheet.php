<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductImportProductsSheet implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles, WithTitle
{
    /** @param Collection<int, Product> $products */
    public function __construct(private Collection $products) {}

    /** @return Collection<int, array{int, string, string, string|null, string|null, string|null, string|null, float, float, float, int, string, float}> */
    public function collection(): Collection
    {
        return $this->products->map(fn (Product $product) => [
            $product->id,
            $product->name,
            $product->sku,
            $product->barcode,
            $product->category?->name,
            $product->brand?->name,
            $product->unit?->name,
            (float) $product->cost_price,
            (float) $product->retail_price,
            (float) $product->wholesale_price,
            (int) $product->stock_alert_quantity,
            $product->is_active ? 'Active' : 'Inactive',
            (float) ($product->stock ? $product->stock->quantity : 0),
        ]);
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['ID', 'Name', 'SKU', 'Barcode', 'Category', 'Brand', 'Unit', 'Cost Price', 'Retail Price', 'Wholesale Price', 'Stock Alert Qty', 'Status', 'Opening Stock'];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'H' => '#,##0.00',
            'I' => '#,##0.00',
            'J' => '#,##0.00',
            'M' => '#,##0.00',
        ];
    }

    /** @return array<int|string, mixed> */
    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:M1');
        $sheet->getStyle('A1:M1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:M1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF2563EB');

        return [];
    }

    public function title(): string
    {
        return 'Products';
    }
}
