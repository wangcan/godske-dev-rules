<?php

declare(strict_types=1);

namespace RasmusGodske\DevRules;

use Illuminate\Support\ServiceProvider;
use RasmusGodske\DevRules\Commands\UpdateDevRulesCommand;

class DevRulesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdateDevRulesCommand::class,
            ]);
        }
    }

    public static function getRulesPath(): string
    {
        return dirname(__DIR__) . '/rules';
    }
}
