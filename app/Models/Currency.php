<?php

namespace App\Models;

use App\Enums\Symbol;

class Currency
{
    const CURRENCY_POSITION_AFTER = 'after';
    const CURRENCY_POSITION_BEFORE = 'before';
    private static ?self $instance = null;

    private function __construct(private Symbol $symbol = Symbol::BGN, private string $position = '', private string $space = '')
    {
        if ($position !== self::CURRENCY_POSITION_BEFORE && $position !== self::CURRENCY_POSITION_AFTER) {
            $this->position = self::CURRENCY_POSITION_AFTER;
        }

        if ($space !== ' ' && $space !== '') {
            $this->space = '';
        }
    }

    public static function getInstance(Symbol $symbol = Symbol::BGN, string $space = '', string $position = ''): self
    {
        if (self::$instance === null) {
            self::$instance = new self($symbol, $position, $space);
            return self::$instance;
        }

        return self::$instance;
    }

    public static function getSymbol(): string
    {
        self::checkInstance();
        return self::$instance->symbol->value;
    }

    public static function toCurrency(float $value): string
    {
        self::checkInstance();

        $formattedValue = number_format($value, 2, '.', '');

        if (self::$instance->position === self::CURRENCY_POSITION_BEFORE) {
            return self::$instance->symbol->value . self::$instance->space . $formattedValue;
        }

        return $formattedValue . self::$instance->space . self::$instance->symbol->value;
    }

    public static function getName(): string
    {
        return self::$instance->symbol->name;
    }

    private static function checkInstance()
    {
        if (self::$instance === null) {
            self::getInstance();
        }
    }
}
