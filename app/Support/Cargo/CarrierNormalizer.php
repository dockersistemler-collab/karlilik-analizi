<?php

namespace App\Support\Cargo;

class CarrierNormalizer
{
    public static function normalizeCarrier(?string $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
$normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        if (function_exists('mb_strtolower')) {
            $encoding = in_array('tr_TR', mb_list_encodings(), true) ? 'tr_TR' : 'UTF-8';
            $normalized = mb_strtolower($normalized, $encoding);
        } else {
            $normalized = strtolower($normalized);
        }
$normalized = preg_replace('/[\\-_.]+/u', ' ', $normalized);
        $normalized = preg_replace('/\\s+/u', ' ', $normalized);
        $normalized = trim($normalized);

        return $normalized === '' ? null : $normalized;
    }
}
