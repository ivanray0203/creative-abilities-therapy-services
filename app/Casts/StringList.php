<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * A JSON column holding a flat list of strings, normalised on the way in and
 * out so consumers can always rely on getting an array.
 *
 * A plain `array` cast round-trips whatever it is given: seed data that wrote
 * a newline-joined string came back as a string, and every `.map()` over it
 * blew up. This accepts either shape and always yields a list.
 *
 * @implements CastsAttributes<array<int, string>, array<int, string>|string|null>
 */
class StringList implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<int, string>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return $this->toList($decoded ?? $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode(array_values($this->toList($value)), JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<int, string>
     */
    private function toList(mixed $value): array
    {
        if (is_string($value)) {
            // Legacy rows stored the list as one newline-separated string.
            $value = preg_split('/\r\n|\r|\n/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn (mixed $entry): string => is_scalar($entry) ? trim((string) $entry) : '', $value),
            fn (string $entry): bool => $entry !== '',
        ));
    }
}
