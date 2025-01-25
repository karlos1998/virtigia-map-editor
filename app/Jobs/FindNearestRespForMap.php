<?php

namespace App\Jobs;

use App\Models\Door;
use App\Models\DynamicModel;
use App\Models\Map;
use App\Models\RespawnPoint;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FindNearestRespForMap implements ShouldQueue
{
    use Queueable;

    public $timeout = 1200;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Map $map)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DynamicModel::setGlobalConnection('retro');

        $chosenMapId = $this->map->id; // Startowa mapa
        $maxDepth = 50; // Maksymalna liczba kroków (ograniczenie głębokości)

        // Pobranie danych z RespawnPoint (mapy docelowe z limitem kroków)
        $respawnPoints = RespawnPoint::where('max_steps', '>=', $maxDepth)
            ->get(['map_id', 'max_steps'])
            ->keyBy('map_id'); // Kluczujemy map_id dla łatwego dostępu

        if ($respawnPoints->isEmpty()) {
            //Brak dostępnych punktów respawn spełniających wymagania.
            return;
        }

        // Pobranie danych i utworzenie grafu
        $doors = Door::select(['map_id', 'go_map_id'])->get()->groupBy('map_id')->toArray();

        // BFS
        $queue = [[$chosenMapId, 0]]; // [aktualna mapa, liczba kroków]
        $visited = [];

        while (!empty($queue)) {
            [$currentMap, $steps] = array_shift($queue);

            // Jeśli osiągnięto jedną z docelowych map, sprawdzamy warunki i zwracamy wynik
            if ($respawnPoints->has($currentMap) && $steps <= $respawnPoints[$currentMap]->max_steps) {
                $this->map->respawn_point_id = RespawnPoint::where('map_id', $currentMap)->first()->id;
                $this->map->save();
                return;
            }

            // Jeśli liczba kroków przekracza limit, przerywamy
            if ($steps > $maxDepth) {
                //zbyt dluga sciezka
                return;
            }

            // Oznacz mapę jako odwiedzoną
            $visited[$currentMap] = true;

            // Dodaj sąsiednie mapy do kolejki
            if (isset($doors[$currentMap])) {
                foreach ($doors[$currentMap] as $door) {
                    $neighbor = $door['go_map_id'];
                    if (!isset($visited[$neighbor])) {
                        $queue[] = [$neighbor, $steps + 1];
                    }
                }
            }
        }
    }
}
