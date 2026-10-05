<?php

namespace App\Exports;

use App\Models\Product;
use App\Support\Features;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductImportProductsSheet implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithStyles, WithTitle
{
    /** @param Collection<int, Product> $products */
    public function __construct(private Collection $products) {}

    /** @return Collection<int, covariant non-empty-list<float|int|string|null>> */
    public function collection(): Collection
    {
        return $this->products->map(fn (Product $product): array => $this->row($product));
    }

    /** @return non-empty-list<float|int|string|null> */
    private function row(Product $product): array
    {
        return [
            $product->name,
            $product->sku,
            $product->barcode,
            $product->category?->name,
            $product->brand?->name,
            $product->unit?->name,
            (float) $product->cost_price,
            (float) $product->retail_price,
            ...(Features::wholesaleEnabled() ? [(float) $product->wholesale_price] : []),
            (int) $product->stock_alert_quantity,
            (float) ($product->stock ? $product->stock->quantity : 0),
        ];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Name', 'SKU', 'Barcode', 'Category', 'Brand', 'Unit', 'Cost Price', 'Retail Price', ...(Features::wholesaleEnabled() ? ['Wholesale Price'] : []), 'Stock Alert Qty', 'Opening Stock'];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'G' => '#,##0.00',
            'H' => '#,##0.00',
            'I' => '#,##0.00',
            (Features::wholesaleEnabled() ? 'K' : 'J') => '#,##0.00',
        ];
    }

    /** @return array<int|string, mixed> */
    public function styles(Worksheet $sheet): array
    {
        $lastColumn = Features::wholesaleEnabled() ? 'K' : 'J';
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}1");
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType('solid')->getStartColor()->setARGB('FF2563EB');

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
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Categories Dropdown (Column D)
                $validation = $sheet->getCell('D2')->getDataValidation();
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

                // Brands Dropdown (Column E)
                $brandValidation = clone $validation;
                $brandValidation->setPrompt('Please pick a brand from the drop-down list.');
                $brandValidation->setFormula1('\'Brands\'!$B$2:$B$1000');

                // Units Dropdown (Column F)
                $unitValidation = clone $validation;
                $unitValidation->setPrompt('Please pick a unit from the drop-down list.');
                $unitValidation->setFormula1('\'Units\'!$B$2:$B$1000');

                // Apply validations to rows 2 to 500
                for ($i = 2; $i <= 500; $i++) {
                    $sheet->getCell("D{$i}")->setDataValidation(clone $validation);
                    $sheet->getCell("E{$i}")->setDataValidation(clone $brandValidation);
                    $sheet->getCell("F{$i}")->setDataValidation(clone $unitValidation);
                }
            },
        ];
    }
}
