<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    /**
     * Apply date range filter to a query based on 'date_filter' request parameter or custom dates.
     */
    public function applyDateFilter(Builder $query, string $dateColumn = 'created_at', ?string $filter = null, ?string $startDate = null, ?string $endDate = null): Builder
    {
        validator(['date_filter' => $filter, 'start_date' => $startDate, 'end_date' => $endDate], [
            'date_filter' => 'nullable|in:today,yesterday,this_week,last_week,this_month,last_month,this_year,custom',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d'.($startDate ? '|after_or_equal:start_date' : ''),
        ])->validate();
        if (! $filter && ! $startDate && ! $endDate) {
            $filter = 'today'; // Default to today
        }

        switch ($filter) {
            case 'today':
                return $query->whereDate($dateColumn, Carbon::today());
            case 'yesterday':
                return $query->whereDate($dateColumn, Carbon::yesterday());
            case 'this_week':
                return $query->whereDate($dateColumn, '>=', Carbon::now()->startOfWeek())->whereDate($dateColumn, '<=', Carbon::now()->endOfWeek());
            case 'last_week':
                return $query->whereDate($dateColumn, '>=', Carbon::now()->subWeek()->startOfWeek())->whereDate($dateColumn, '<=', Carbon::now()->subWeek()->endOfWeek());
            case 'this_month':
                return $query->whereDate($dateColumn, '>=', Carbon::now()->startOfMonth())->whereDate($dateColumn, '<=', Carbon::now()->endOfMonth());
            case 'last_month':
                return $query->whereDate($dateColumn, '>=', Carbon::now()->subMonthNoOverflow()->startOfMonth())->whereDate($dateColumn, '<=', Carbon::now()->subMonthNoOverflow()->endOfMonth());
            case 'this_year':
                return $query->whereDate($dateColumn, '>=', Carbon::now()->startOfYear())->whereDate($dateColumn, '<=', Carbon::now()->endOfYear());
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
                    if ($startDate) {
                        $query->whereDate($dateColumn, '>=', Carbon::parse($startDate));
                    }
                    if ($endDate) {
                        $query->whereDate($dateColumn, '<=', Carbon::parse($endDate));
                    }

                    return $query;
                }

                return $query->whereDate($dateColumn, Carbon::today());
        }
    }
}
