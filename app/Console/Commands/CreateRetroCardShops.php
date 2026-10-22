<?php

namespace App\Console\Commands;

use App\Services\RetroCardShopService;
use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Console\Command;
use InvalidArgumentException;

class CreateRetroCardShops extends Command
{
    protected $signature = 'card-game:create-retro-shops';

    protected $description = 'Create or complete local Retro card shops, with up to 80 cards per shop';

    public function handle(WorldTemplateConnectionResolver $worlds, RetroCardShopService $shops): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Tworzenie sklepów testowych jest dostępne wyłącznie lokalnie.');

            return self::FAILURE;
        }
        $world = $worlds->resolve('retro_local');
        if ($world === null || $world->connection_name !== 'retro_local') {
            $this->error('Najpierw skonfiguruj i zarejestruj Retro Local.');

            return self::FAILURE;
        }
        $worlds->registerTemplateConnection($world);
        try {
            $created = $shops->create('retro_local');
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        foreach ($created as $shop) {
            $this->line("Sklep #{$shop->id}: {$shop->name} — {$shop->items_count} kart. Konsola gry: shop {$shop->id}");
        }
        $this->info('Sklepy gotowe. Ponowne uruchomienie uzupełni tylko brakujące karty.');

        return self::SUCCESS;
    }
}
