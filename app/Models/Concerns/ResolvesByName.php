<?php

namespace App\Models\Concerns;

trait ResolvesByName
{
    /**
     * Resolve combobox selections ({id, name} pairs) to ids.
     *
     * Existing records arrive with an id; new ones typed inline arrive with id=null
     * and are matched case-insensitively by name, or created if no match exists.
     *
     * @param  array<int, array{id?: int|string|null, name: string}>|null  $items
     * @return array<int, int>
     */
    public static function resolveIds(?array $items): array
    {
        return collect($items ?? [])
            ->map(fn (array $item) => isset($item['id'])
                ? (int) $item['id']
                : static::findOrCreateByName($item['name'])->getKey()
            )
            ->unique()
            ->values()
            ->all();
    }

    public static function findOrCreateByName(string $name): static
    {
        return static::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first()
            ?? static::create(['name' => $name]);
    }
}
