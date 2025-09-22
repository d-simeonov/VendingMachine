<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\VendingMachine;
use Illuminate\Console\Command;

class VendingMachineCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vending-machine';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Console Interface for Vending Machine';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $machine = new VendingMachine(
            [
                'sign' => 'лв.',
                'space' => '',
                'position' => Currency::CURRENCY_POSITION_AFTER,
            ],
            [
                'Milk' => 0.50,
                'Espresso' => 0.40,
                'Long Espresso' => 0.60,
            ]
        );

        $machine
            ->buyDrink('espresso')
            ->buyDrink('Espresso')
            ->viewDrinks()
            ->putCoin(2)
            ->putCoin(1)
            ->buyDrink('Espresso')
            ->getCoins()
            ->viewAmount()
            ->getCoins();
    }
}


