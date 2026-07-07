<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    /**
     * Apply date range filter to a query based on 'date_filter' request parameter or custom dates.
     *
     * @param Builder $query
     * @param string $dateColumn
     * @param string|null $filter
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Builder
     */
    public function applyDateFilter(Builder $query, string $dateColumn = 'created_at', ?string $filter = null, ?string $startDate = null, ?string $endDate = null): Builder
    {
        if (!$filter && !$startDate && !$endDate) {
            $filter = 'today'; // Default to today
        }

        switch ($filter) {
            case 'today':
                return $query->whereDate($dateColumn, Carbon::today());
            case 'yesterday':
                return $query->whereDate($dateColumn, Carbon::yesterday());
            case 'this_week':
                return $query->whereBetween($dateColumn, [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            case 'last_week':
                return $query->whereBetween($dateColumn, [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
            case 'this_month':
                return $query->whereBetween($dateColumn, [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
            case 'last_month':
                return $query->whereBetween($dateColumn, [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]);
            case 'this_year':
                return $query->whereBetween($dateColumn, [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]);
            case 'custom':
                if ($startDate) {
                    $query->whereDate($dateColumn, '>=', Carbon::parse($startDate));
                }
                if ($endDate) {
                    $query->whereDate($dateColumn, '<=', Carbon::parse($endDate));
                }
                return $query;
            default:
                // Default to custom if dates are provided, else today
                if ($startDate || $endDate) {
                    if ($startDate) $query->whereDate($dateColumn, '>=', Carbon::parse($startDate));
                    if ($endDate) $query->whereDate($dateColumn, '<=', Carbon::parse($endDate));
                    return $query;
                }
                return $query->whereDate($dateColumn, Carbon::today());
        }
    }
}
