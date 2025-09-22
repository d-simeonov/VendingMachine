<?php

namespace App\Models;

use Throwable;

class Display
{
    /** @var string[] */
    private array $messages = [];
    private bool $isConsole = false;

    public function __construct()
    {
        if (php_sapi_name() === 'cli') {
            $this->isConsole = true;
        }
    }

    public function addMessage(string $message): void
    {
        $this->messages[] = $message;
        $this->printIfConsole($message);
    }

    /** @return string[] */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function clear(): void
    {
        $this->messages = [];
    }

    public function printIfConsole(string $message): void
    {
        if ($this->isConsole) {
            print($message . PHP_EOL);
        }
    }
}
