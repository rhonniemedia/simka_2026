<?php

namespace App\Casts;

use App\Support\NameFormatter;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Merapikan nama otomatis setiap kali atribut diisi lewat Eloquent
 * (create / update / save). Nilai yang dibaca tidak diubah.
 */
class NormalizedName implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value === null ? null : NameFormatter::normalize($value);
    }
}
