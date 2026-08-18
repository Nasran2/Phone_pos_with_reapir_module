<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductImportInstructionsSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    /** @return array<int, array<int, string>> */
    public function array(): array
    {
        return [
            ['Product Import Instructions'],
            ['1', 'Edit or add rows only on the Products sheet.'],
            ['2', 'Name is required. SKU and Barcode will be auto-generated if left blank. SKU must be unique if provided.'],
            ['3', 'Use the Categories, Brands, and Units sheets to copy valid names. New names will be created automatically.'],
            ['4', 'Opening Stock sets the stock quantity in the default store.'],
            ['5', 'Status must be Active or Inactive.'],
            ['6', 'Do not rename the Products sheet column headings.'],
        ];
    }

    /** @return array<int|string, mixed> */
    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:B1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF1E293B');
        $sheet->getStyle('A2:A7')->getFont()->setBold(true);

        return [];
    }

    public function title(): string
    {
        return 'Instructions';
    }
}
