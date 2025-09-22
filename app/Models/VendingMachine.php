<?php

namespace App\Models;

use App\Enums\CurrencyDenomination;
use App\Enums\Symbol;
use Illuminate\Database\Eloquent\Model;

class VendingMachine extends Model
{
    private array $coinsInMachine = [];
    private Display $display;
    private array $drinks = [];

    public function __construct(private array $currency = [], array $drinks = [])
    {
        parent::__construct();
        $this->display = new Display();
        $this->initializeCurrency();
        $this->initializeDrinks($drinks);
        $this->initializeCoins();
    }

    //region Private Initializers
    private function initializeCurrency(): void
    {
        $symbol = Symbol::BGN; // default
        $space = '';
        $position = '';

        if (!empty($this->currency)) {
            if (isset($this->currency['sign'])) {
                $symbol = Symbol::fromValue($this->currency['sign'], Symbol::BGN);
            }

            if (isset($this->currency['space']) && is_string($this->currency['space'])) {
                $space = $this->currency['space'];
            }

            if (isset($this->currency['position']) && is_string($this->currency['position'])) {
                $position = $this->currency['position'];
            }
        }

        Currency::getInstance($symbol, $space, $position);
    }

    private function initializeDrinks(array $drinks): void
    {
        foreach ($drinks as $name => $price) {
            $roundedPrice = round($price, 2, PHP_ROUND_HALF_UP);
            $this->drinks[$name] = new Drink($name, $roundedPrice);
        }
    }

    private function initializeCoins(): void
    {
        foreach (CurrencyDenomination::cases() as $denomination) {
            $this->coinsInMachine[$denomination->value] = 0;
        }
    }
    //endregion Private Initializers

    //region Required Methods
    public function viewDrinks(): VendingMachine
    {
        $drinksList = [];
        foreach ($this->drinks as $drink) {
            $drinksList[] = "{$drink->getName()}: {$drink->getPriceWithCurrency()}";
        }

        $message = "Напитки: " . PHP_EOL . implode(PHP_EOL, $drinksList);
        $this->display->addMessage($message);

        return $this;
    }

    public function putCoin(float $coin): VendingMachine
    {
        $coin = round($coin, 2, PHP_ROUND_HALF_UP);

        if ($this->isValidCoin($coin)) {
            $this->addCoin($coin);
            $coinFormatted = Currency::toCurrency($coin);
            $currentAmount = Currency::toCurrency($this->getCurrentAmount());
            $this->display->addMessage("Успешно поставихте $coinFormatted, текущата Ви сума е $currentAmount");
        } else {
            $this->showAcceptedCoins();
        }

        return $this;
    }

    public function buyDrink(string $drinkName): VendingMachine
    {
        $drink = $this->findDrink($drinkName);

        if ($drink === null) {
            $this->display->addMessage("Исканият продукт не е намерен");
            return $this;
        }

        if (!$this->hasEnoughMoney($drink->getPrice())) {
            $this->display->addMessage("Недостатъчна наличност.");
            return $this;
        }

        $this->processPurchase($drink);
        return $this;
    }

    public function getCoins(): VendingMachine
    {
        $currentAmount = $this->getCurrentAmount();
        if ($currentAmount <= 0) {
            $this->display->addMessage("Няма монети за връщане");
            return $this;
        }

        $changeAmount = Currency::toCurrency($currentAmount);
        $coinsDescription = $this->formatCoinsDescription();
        $this->display->addMessage("Получихте ресто $changeAmount в монети от: $coinsDescription");
        $this->clearCoins();

        return $this;
    }

    public function viewAmount(): VendingMachine
    {
        $amount = Currency::toCurrency($this->getCurrentAmount());
        $this->display->addMessage("Текущата Ви сума е $amount");
        return $this;
    }
    //endregion Required Methods


    //region Helper methods
    private function isValidCoin(float $coin): bool
    {
        $validDenominations = [
            CurrencyDenomination::POINT_FIVE->floatValue(),
            CurrencyDenomination::POINT_TEN->floatValue(),
            CurrencyDenomination::POINT_TWENTY->floatValue(),
            CurrencyDenomination::POINT_FIFTY->floatValue(),
            CurrencyDenomination::ONE->floatValue(),
        ];

        return in_array($coin, $validDenominations, true);
    }

    private function addCoin(float $coin): void
    {
        $coinKey = number_format($coin, 2, '.', '');
        $this->coinsInMachine[$coinKey]++;
    }

    private function showAcceptedCoins(): void
    {
        $acceptedCoins = implode(", ", CurrencyDenomination::valuesWithCurrency());
        $this->display->addMessage("Автомата приема монети от: $acceptedCoins");
    }

    private function findDrink(string $drinkName): ?Drink
    {
        return $this->drinks[$drinkName] ?? null;
    }

    private function hasEnoughMoney(float $price): bool
    {
        $roundedPrice = round($price, 2, PHP_ROUND_HALF_UP);
        return $this->getCurrentAmount() >= $roundedPrice;
    }

    private function processPurchase(Drink $drink): void
    {
        $price = round($drink->getPrice(), 2, PHP_ROUND_HALF_UP);
        $this->deductAmount($price);

        $remainingAmount = Currency::toCurrency($this->getCurrentAmount());
        $this->display->addMessage("Успешно закупихте '{$drink->getName()}' от {$drink->getPriceWithCurrency()}, текущата Ви сума е $remainingAmount");
    }

    private function deductAmount(float $amount): void
    {
        $currentTotal = $this->getCurrentAmount();

        if ($currentTotal < $amount) {
            return; // not enough money
        }

        $remainingAmount = round($currentTotal - $amount, 2, PHP_ROUND_HALF_UP);

        $this->clearCoins();
        $this->coinsInMachine = $this->calculateChange($remainingAmount);
    }

    private function calculateChange(float $amount): array
    {
        $result = [];
        foreach (CurrencyDenomination::cases() as $denomination) {
            $result[$denomination->value] = 0;
        }

        // Machine has no coins
        if ($amount <= 0) {
            return $result;
        }

        // Machine has coins to give change
        $cents = (int)($amount * 100);
        $denominations = array_reverse(CurrencyDenomination::cases());

        foreach ($denominations as $denomination) {
            $coinCents = (int)($denomination->value * 100);
            $count = intdiv($cents, $coinCents);

            if ($count > 0) {
                $result[$denomination->value] = $count;
                $cents -= $coinCents * $count;
            }
        }

        return array_reverse($result);
    }

    private function getCurrentAmount(): float
    {
        $total = 0.0;
        foreach ($this->coinsInMachine as $denomination => $quantity) {
            $total += $denomination * $quantity;
        }
        return round($total, 2, PHP_ROUND_HALF_UP);
    }

    private function formatCoinsDescription(): string
    {
        $coinsList = [];
        foreach ($this->coinsInMachine as $denomination => $quantity) {
            if ($quantity > 0) {
                $coinsList[] = "{$quantity}x" . Currency::toCurrency($denomination);
            }
        }
        return implode(", ", $coinsList);
    }

    private function clearCoins(): void
    {
        $this->coinsInMachine = [];
        foreach (CurrencyDenomination::cases() as $denomination) {
            $this->coinsInMachine[$denomination->value] = 0;
        }
    }
    //endregion Helper methods
}
