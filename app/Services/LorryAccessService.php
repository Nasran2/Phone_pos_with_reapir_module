<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LorryAccessService
{
    public function isUnrestricted(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Admin') || $user->hasRole('Administrator');
    }

    public function isLorryUser(User $user): bool
    {
        return $user->user_type === 'lorry';
    }

    public function assignments(User $user): Collection
    {
        return DB::table('lorry_user_assignments as a')->join('lorries as l', 'l.id', '=', 'a.lorry_id')
            ->where('a.user_id', $user->id)->where('a.is_active', true)
            ->whereDate('a.active_from', '<=', today())->where(fn ($q) => $q->whereNull('a.active_until')->orWhereDate('a.active_until', '>=', today()))
            ->whereNull('l.deleted_at')->select('a.*', 'l.lorry_no', 'l.name as lorry_name', 'l.plate_no')->orderByDesc('a.is_primary')->get();
    }

    public function allowedIds(User $user): Collection
    {
        return $this->assignments($user)->pluck('lorry_id')->map(fn ($id) => (int) $id);
    }

    public function authorize(User $user, int $lorryId): void
    {
        if (! $this->isUnrestricted($user) && ! $this->allowedIds($user)->contains($lorryId)) abort(403, 'You are not assigned to this lorry.');
    }

    public function currentAssignment(User $user, ?int $requested = null): object
    {
        $assignments = $this->assignments($user);
        abort_if($assignments->isEmpty(), 403, 'No active lorry assignment was found.');
        if ($requested) {
            $assignment = $assignments->firstWhere('lorry_id', $requested);
            abort_unless($assignment, 403, 'You are not assigned to this lorry.');
            return $assignment;
        }
        return $assignments->firstWhere('lorry_id', $user->default_lorry_id) ?? $assignments->first();
    }

    public function scope(Builder $query, User $user, string $column = 'lorry_id'): Builder
    {
        return $this->isUnrestricted($user) ? $query : $query->whereIn($column, $this->allowedIds($user));
    }
}
