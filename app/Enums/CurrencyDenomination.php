<?php

namespace App\Enums;

use App\Models\Currency;

enum CurrencyDenomination: string
{
    case POINT_FIVE = '0.05';
    case POINT_TEN = '0.10';
    case POINT_TWENTY = '0.20';
    case POINT_FIFTY = '0.50';
    case ONE = '1.00';

    public function floatValue(): float
    {
        return round((float)$this->value, 2, PHP_ROUND_HALF_UP);
    }

    public static function values(): array
    {
        return array_map(
            fn(CurrencyDenomination $d) => $d->floatValue(),
            self::cases()
        );
    }

    public static function valuesWithCurrency(): array
    {
        return array_map(
            fn(CurrencyDenomination $d) => Currency::toCurrency($d->floatValue()),
            self::cases()
        );
    }
}
