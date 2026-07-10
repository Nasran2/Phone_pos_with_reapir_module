<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function show(string $path): View
    {
        $section = $this->findSection($path);
        $this->authorizeSection($section, 'view');

        return view('settings.show', [
            'section' => $section,
            'values' => $this->valuesFor($section),
        ]);
    }

    public function update(Request $request, string $path): RedirectResponse
    {
        $section = $this->findSection($path);
        $this->authorizeSection($section, 'update');

        $validated = $request->validate($this->rulesFor($section));
        $table = $section['table'];

        foreach ($this->fieldsFor($section) as $field) {
            $key = $field['key'];
            $value = $this->valueFromRequest($request, $validated, $field);

            if ($value === null && $field['type'] === 'file') {
                continue;
            }

            DB::table($table)->updateOrInsert(
                ['key' => $key],
                ['value' => $this->serializeValue($value), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return back()->with('success', $section['label'] . ' saved successfully.');
    }

    public function reset(string $path): RedirectResponse
    {
        $section = $this->findSection($path);
        $this->authorizeSection($section, 'update');

        foreach ($this->fieldsFor($section) as $field) {
            if (($field['type'] ?? 'text') === 'file') {
                continue;
            }

            DB::table($section['table'])->updateOrInsert(
                ['key' => $field['key']],
                ['value' => $this->serializeValue($field['default'] ?? null), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return back()->with('success', $section['label'] . ' reset to defaults.');
    }

    private function findSection(string $path): array
    {
        $section = collect(config('settings_sections'))->firstWhere('path', $path);
        abort_unless($section, 404);

        return $section;
    }

    private function authorizeSection(array $section, string $ability): void
    {
        $permission = 'settings.' . $section['route'] . '.' . $ability;

        abort_unless(auth()->user()->hasPermission($permission), 403, 'Unauthorized action. You do not have permission: ' . $permission);
    }

    private function valuesFor(array $section): array
    {
        $stored = DB::table($section['table'])->pluck('value', 'key');

        return $this->fieldsFor($section)
            ->mapWithKeys(function (array $field) use ($stored) {
                $rawValue = $stored->has($field['key']) ? $stored[$field['key']] : ($field['default'] ?? null);

                return [$field['key'] => $this->castValue($rawValue, $field)];
            })
            ->all();
    }

    private function fieldsFor(array $section): Collection
    {
        return collect($section['groups'])->flatMap(fn (array $group) => $group['fields']);
    }

    private function rulesFor(array $section): array
    {
        return $this->fieldsFor($section)
            ->mapWithKeys(fn (array $field) => [$field['key'] => $field['rules'] ?? ['nullable']])
            ->all();
    }

    private function valueFromRequest(Request $request, array $validated, array $field): mixed
    {
        $key = $field['key'];
        $type = $field['type'] ?? 'text';

        if ($type === 'toggle') {
            return $request->boolean($key);
        }

        if ($type === 'file') {
            if (!$request->hasFile($key)) {
                return null;
            }

            return $request->file($key)->store('settings', 'public');
        }

        return $validated[$key] ?? null;
    }

    private function castValue(mixed $value, array $field): mixed
    {
        if (($field['type'] ?? 'text') === 'toggle') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $value;
    }

    private function serializeValue(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
