<?php

namespace App\Http\Middleware;

use App\Models\Account;
use App\Models\HoldSale;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Support\Features;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class EnforceFeatureAvailability
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Features::repairEnabled()) {
            abort_if($request->is('repairs', 'repairs/*') || $request->filled('repair_job_id'), 404);
            $items = $request->input('items', []);
            $accountIds = is_array($items) ? array_filter(array_column($items, 'account_id')) : [];
            abort_if($accountIds && Account::whereIn('id', $accountIds)->whereIn('account_code', ['4010', '4011', '5010', '2210'])->exists(), 404);
            foreach ($request->route()?->parameters() ?? [] as $record) {
                if ($record instanceof Account) {
                    abort_if(in_array($record->account_code, ['4010', '4011', '5010', '2210'], true), 404);
                }
            }
        }

        if (! Features::wholesaleEnabled()) {
            foreach (['pos_type', 'sale_type', 'price_type', 'items.*.price_type'] as $key) {
                $values = Arr::wrap(data_get($request->all(), $key));
                foreach ($values as $value) {
                    abort_if(is_string($value) && strtolower($value) === 'wholesale', 404);
                }
            }

            $items = $request->input('items', []);
            $accountIds = is_array($items) ? array_filter(array_column($items, 'account_id')) : [];
            if ($accountIds !== []) {
                abort_if(Account::whereIn('id', $accountIds)->where(fn ($q) => $q->where('account_code', '4002')->orWhere('account_name', 'like', '%wholesale%'))->exists(), 404);
            }

            foreach ($request->route()?->parameters() ?? [] as $record) {
                if ($record instanceof Account) {
                    abort_if($record->account_code === '4002' || str_contains(strtolower((string) $record->getRawOriginal('account_name')), 'wholesale'), 404);
                }
                if ($record instanceof HoldSale || ($record instanceof Sale && ! $request->isMethodSafe())) {
                    abort_if($record->pos_type === 'wholesale', 404);
                }
                if ($record instanceof SaleReturn && ! $request->isMethodSafe()) {
                    abort_if($record->sale()->value('pos_type') === 'wholesale', 404);
                }
            }

            foreach (['edit_sale_id', 'sale_id'] as $key) {
                if ($request->filled($key)) {
                    abort_if(Sale::whereKey($request->input($key))->where('pos_type', 'wholesale')->exists(), 404);
                }
            }
            if ($request->filled('hold_id')) {
                abort_if(HoldSale::whereKey($request->input('hold_id'))->where('pos_type', 'wholesale')->exists(), 404);
            }
        }

        return $next($request);
    }
}
