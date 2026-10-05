<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class Features
{
    public static function wholesaleEnabled(): bool
    {
        return filter_var(config('features.wholesale', true), FILTER_VALIDATE_BOOLEAN);
    }

    public static function repairEnabled(): bool
    {
        return filter_var(config('features.repair', true), FILTER_VALIDATE_BOOLEAN);
    }

    public static function moduleLabel(): string
    {
        return implode(' & ', array_filter(['Retail', self::wholesaleEnabled() ? 'Wholesale' : null, self::repairEnabled() ? 'Repair' : null]));
    }

    public static function permissionEnabled(string $permission): bool
    {
        if (! self::repairEnabled() && str_starts_with($permission, 'repair.')) {
            return false;
        }

        return self::wholesaleEnabled() || (! str_contains(strtolower($permission), 'wholesale')
            && ! str_starts_with($permission, 'my_lorry.orders.')
            && ! str_starts_with($permission, 'my_lorry.sales.')
            && ! str_starts_with($permission, 'lorry_sales.'));
    }

    /**
     * Filter at request time so cached configuration and runtime overrides behave identically.
     *
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    public static function settingsSection(array $section): array
    {
        if (self::wholesaleEnabled()) {
            return $section;
        }

        $section['description'] = self::retailText($section['description'] ?? '');
        foreach ($section['groups'] as &$group) {
            $group['description'] = self::retailText($group['description'] ?? '');
            $group['fields'] = array_values(array_filter($group['fields'], fn ($field) => ! str_contains($field['key'], 'wholesale')));
            foreach ($group['fields'] as &$field) {
                if (isset($field['options'])) {
                    $field['options'] = array_filter($field['options'], fn ($key) => ! str_contains((string) $key, 'wholesale'), ARRAY_FILTER_USE_KEY);
                    $field['rules'] = ['nullable', Rule::in(array_keys($field['options']))];
                }
            }
            unset($field);
        }
        unset($group);

        return $section;
    }

    public static function retailText(string $text): string
    {
        if (self::wholesaleEnabled()) {
            return $text;
        }

        return preg_replace('/wholesale,?\s*/i', '',
            preg_replace('/retail\s*(?:&|and)\s*wholesale|wholesale\s*(?:&|and)\s*retail/i', self::repairEnabled() ? 'Retail & Repair' : 'Retail', $text));
    }
}
