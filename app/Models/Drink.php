<?php

namespace App\Models;

class Drink
{
    public function __construct(private string $name, private float $price)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPriceWithCurrency(): string
    {
        return Currency::toCurrency($this->price);
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}
