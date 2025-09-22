<?php

namespace App\Enums;

enum Symbol: string
{
    case USD = '$';
    case EUR = '€';
    case BGN = 'лв.';

    public static function values(): array
    {
        return array_map(fn(Symbol $s) => $s->value, self::cases());
    }

    // Check if a given value is a valid Symbol
    public static function isValid(mixed $value): bool
    {
        if ($value instanceof self) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return true;
            }
        }

        return false;
    }

    // Try to create from string value
    public static function fromValue(mixed $value, self $default = self::BGN): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (!is_string($value)) {
            return $default;
        }

        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        return $default;
    }
}
