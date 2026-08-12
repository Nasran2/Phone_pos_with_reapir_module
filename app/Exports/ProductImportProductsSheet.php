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
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductImportProductsSheet implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles, WithTitle, WithEvents
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

    /** @return array<string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Categories Dropdown (Column E)
                $validation = $sheet->getCell('E2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a category from the drop-down list.');
                $validation->setFormula1('\'Categories\'!$B$2:$B$1000'); 
                
                // Brands Dropdown (Column F)
                $brandValidation = clone $validation;
                $brandValidation->setPrompt('Please pick a brand from the drop-down list.');
                $brandValidation->setFormula1('\'Brands\'!$B$2:$B$1000');
                
                // Units Dropdown (Column G)
                $unitValidation = clone $validation;
                $unitValidation->setPrompt('Please pick a unit from the drop-down list.');
                $unitValidation->setFormula1('\'Units\'!$B$2:$B$1000');

                // Apply validations to rows 2 to 500
                for ($i = 2; $i <= 500; $i++) {
                    $sheet->getCell("E{$i}")->setDataValidation(clone $validation);
                    $sheet->getCell("F{$i}")->setDataValidation(clone $brandValidation);
                    $sheet->getCell("G{$i}")->setDataValidation(clone $unitValidation);
                }
            },
        ];
    }
}
