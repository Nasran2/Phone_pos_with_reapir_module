<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;

class ExcelExportService implements FromView
{
    protected $title;
    protected $headers;
    protected $rows;
    protected $totals;
    protected $filters;

    public function __construct(string $title, array $headers, $rows, array $totals = [], array $filters = [])
    {
        $this->title = $title;
        $this->headers = $headers;
        $this->rows = $rows;
        $this->totals = $totals;
        $this->filters = $filters;
    }

    public function view(): View
    {
        return view('reports.exports.excel', [
            'title' => $this->title,
            'headers' => $this->headers,
            'rows' => $this->rows,
            'totals' => $this->totals,
            'filters' => $this->filters,
            'generated_by' => auth()->user()->name,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public static function export(string $title, array $headers, $rows, array $totals = [], array $filters = [])
    {
        return Excel::download(
            new self($title, $headers, $rows, $totals, $filters), 
            str_replace(' ', '_', strtolower($title)) . '_' . now()->format('YmdHis') . '.xlsx'
        );
    }
}
