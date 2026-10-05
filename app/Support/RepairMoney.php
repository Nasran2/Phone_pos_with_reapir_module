<?php

namespace App\Support;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

class RepairMoney
{
    // All persistence and arithmetic use minor units; no binary floating point totals.
    public static function cents(mixed $value): int
    {
        $str = (string) $value;
        if (! preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/D', $str, $m) || strlen($m[2]) > 11) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount with at most two decimal places.']);
        }

        return ($m[1] === '-' ? -1 : 1) * ((int) $m[2] * 100 + (int) str_pad($m[3] ?? '', 2, '0'));
    }

    public static function decimal(int $cents): string
    {
        return ($cents < 0 ? '-' : '').intdiv(abs($cents), 100).'.'.str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function multiplyDivide(int $a, int $b, int $denominator, bool $round = false): int
    {
        return BigInteger::of($a)->multipliedBy($b)->plus($round ? intdiv($denominator, 2) : 0)->dividedBy($denominator, RoundingMode::DOWN)->toInt();
    }

    public static function percent(int $cents, int $basisPoints): int
    {
        return self::multiplyDivide($cents, $basisPoints, 10000, true);
    }
}
