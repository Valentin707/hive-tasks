<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class SystemSetting extends Model
{
    public const REGISTRATION_ENABLED = 'registration_enabled';

    public static function valueFor(string $key, ?string $default = null): ?string
    {
        $value = static::query()->where('key', $key)->value('value');

        return is_string($value) ? $value : $default;
    }

    public static function booleanFor(string $key, bool $default = false): bool
    {
        $value = static::valueFor($key);

        if ($value === null) {
            return $default;
        }

        $parsedValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return is_bool($parsedValue) ? $parsedValue : $default;
    }
}
