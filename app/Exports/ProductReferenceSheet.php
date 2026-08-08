<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductReferenceSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /** @var Collection<int, array<int, mixed>> */
    private Collection $rows;

    /**
     * @param  array<int, string>  $sheetHeadings
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function __construct(
        private string $sheetTitle,
        private array $sheetHeadings,
        iterable $rows,
    ) {
        $this->rows = collect($rows);
    }

    /** @return Collection<int, array<int, mixed>> */
    public function collection(): Collection
    {
        return $this->rows;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return $this->sheetHeadings;
    }

    /** @return array<int|string, mixed> */
    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $sheet->getHighestColumn();
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}1");
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType('solid')->getStartColor()->setARGB('FF0F766E');

        return [];
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }
}
