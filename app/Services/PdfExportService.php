<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfExportService
{
    /**
     * Generate a PDF report with standard layout.
     *
     * @param string $title
     * @param array $headers
     * @param \Illuminate\Support\Collection|array $rows
     * @param array $totals
     * @param array $filters
     * @return \Illuminate\Http\Response
     */
    public function export(string $title, array $headers, $rows, array $totals = [], array $filters = [])
    {
        $pdf = Pdf::loadView('reports.exports.pdf', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'totals' => $totals,
            'filters' => $filters,
            'generated_by' => auth()->user()->name,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ]);

        return $pdf->stream(str_replace(' ', '_', strtolower($title)) . '_' . now()->format('YmdHis') . '.pdf');
    }
}
