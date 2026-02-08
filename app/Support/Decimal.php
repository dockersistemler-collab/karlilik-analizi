<?php

namespace App\Support;

/**
 * Decimal arithmetic helper (string based).
 */
class Decimal
{
    public const MONEY_SCALE = 2;
    public const CALC_SCALE = 4;

    public static function add(string $left, string $right, int $scale = self::MONEY_SCALE): string
    {
        return self::round(self::bcAdd($left, $right, $scale), $scale);
    }

    public static function sub(string $left, string $right, int $scale = self::MONEY_SCALE): string
    {
        return self::round(self::bcSub($left, $right, $scale), $scale);
    }

    public static function mul(string $left, string $right, int $scale = self::MONEY_SCALE): string
    {
        return self::round(self::bcMul($left, $right, $scale), $scale);
    }

    public static function div(string $left, string $right, int $scale = self::MONEY_SCALE): string
    {
        if (self::cmp($right, '0', self::CALC_SCALE) === 0) {
            return self::round('0', $scale);
        }

        return self::round(self::bcDiv($left, $right, $scale), $scale);
    }

    public static function cmp(string $left, string $right, int $scale = self::CALC_SCALE): int
    {
        if (function_exists('bccomp')) {
            return (int) bccomp($left, $right, $scale);
        }

        $l = (float) $left;
        $r = (float) $right;
        if ($l === $r) {
            return 0;
        }

        return $l > $r ? 1 : -1;
    }

    public static function max(string $left, string $right, int $scale = self::CALC_SCALE): string
    {
        return self::cmp($left, $right, $scale) >= 0 ? $left : $right;
    }

    public static function min(string $left, string $right, int $scale = self::CALC_SCALE): string
    {
        return self::cmp($left, $right, $scale) <= 0 ? $left : $right;
    }

    public static function round(string $value, int $scale = self::MONEY_SCALE): string
    {
        if (function_exists('bcadd')) {
            return bcadd($value, '0', $scale);
        }

        $rounded = round((float) $value, $scale);
        return number_format($rounded, $scale, '.', '');
    }

    private static function bcAdd(string $left, string $right, int $scale): string
    {
        if (function_exists('bcadd')) {
            return bcadd($left, $right, $scale);
        }

        $sum = (float) $left + (float) $right;
        return number_format(round($sum, $scale), $scale, '.', '');
    }

    private static function bcSub(string $left, string $right, int $scale): string
    {
        if (function_exists('bcsub')) {
            return bcsub($left, $right, $scale);
        }

        $diff = (float) $left - (float) $right;
        return number_format(round($diff, $scale), $scale, '.', '');
    }

    private static function bcMul(string $left, string $right, int $scale): string
    {
        if (function_exists('bcmul')) {
            return bcmul($left, $right, $scale);
        }

        $product = (float) $left * (float) $right;
        return number_format(round($product, $scale), $scale, '.', '');
    }

    private static function bcDiv(string $left, string $right, int $scale): string
    {
        if (function_exists('bcdiv')) {
            return bcdiv($left, $right, $scale);
        }

        $divisor = (float) $right;
        if ($divisor == 0.0) {
            return number_format(0, $scale, '.', '');
        }

        $result = (float) $left / $divisor;
        return number_format(round($result, $scale), $scale, '.', '');
    }
}
