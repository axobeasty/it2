<?php

namespace App\Casts;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Как datetime, но невалидные/пустые значения из БД не роняют запрос (возвращается null).
 */
class LenientDatetime implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        if ($value instanceof Carbon) {
            return [$key => $value->format('Y-m-d H:i:s')];
        }

        if ($value instanceof DateTimeInterface) {
            return [$key => Carbon::instance($value)->format('Y-m-d H:i:s')];
        }

        try {
            return [$key => Carbon::parse((string) $value)->format('Y-m-d H:i:s')];
        } catch (\Throwable) {
            return [$key => null];
        }
    }
}
