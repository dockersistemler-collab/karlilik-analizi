<?php

namespace App\Support;

class TRNumberParser
{
    public static function parse(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(["\u{00A0}", ' '], '', $value);

        $hasComma = str_contains($value, ',');
        $hasDot = str_contains($value, '.');

        if ($hasComma && $hasDot) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif ($hasComma) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif ($hasDot) {
            if (preg_match('/\.\d{3}($|[^\d])/', $value)) {
                $value = str_replace('.', '', $value);
            }
        }

        $value = preg_replace('/[^0-9\.\-]/', '', $value);
        if ($value === '' || $value === '-' || $value === '.') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
