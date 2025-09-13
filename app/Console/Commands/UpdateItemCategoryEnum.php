<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Enums\BaseItemCategory;

class UpdateItemCategoryEnum extends Command
{
    protected $signature = 'db:update-item-category-enum {connection}';
    protected $description = 'Aktualizuje typ ENUM w kolumnie category (base_items) na podstawie BaseItemCategory';

    public function handle()
    {
        $connection = $this->argument('connection');

        // Pobierz wartości Enum
        $enumValues = array_map(fn($case) => $case->value, BaseItemCategory::cases());
        $enumList = "'" . implode("','", $enumValues) . "'";

        $sql = "ALTER TABLE base_items MODIFY category ENUM($enumList)";

        $this->info("Wykonuję zapytanie SQL na połączeniu '$connection':");
        $this->line($sql);

        try {
            // Wykonaj ALTER na wskazanym połączeniu
            DB::connection($connection)->statement($sql);
            $this->info('Kolumna ENUM została zaktualizowana.');
        } catch (\Exception $e) {
            $this->error('Błąd aktualizacji: ' . $e->getMessage());
        }
    }
}
