<?php

namespace Tests\Unit;

use App\Facades\AssetUrl;
use App\Http\Resources\QuestStepResource;
use App\Models\BaseNpc;
use App\Models\QuestStep;
use App\Models\QuestStepAutoProgress;
use App\Models\QuestStepAutoProgressMob;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Tests\TestCase;

class QuestStepResourceTest extends TestCase
{
    public function test_it_generates_the_base_npc_image_url_for_auto_progress_mobs(): void
    {
        AssetUrl::swap(new class
        {
            public function npc(?string $path): ?string
            {
                return $path === null ? null : "https://assets.example.test/img/npc/{$path}";
            }
        });

        $baseNpc = (new BaseNpc)->forceFill([
            'id' => 42,
            'name' => 'Wilk',
            'src' => 'wolf.gif',
        ]);

        $progressMob = (new QuestStepAutoProgressMob)->forceFill([
            'base_npc_id' => 42,
            'mob_species_id' => null,
            'quantity' => 3,
        ]);
        $progressMob->setRelation('baseNpc', $baseNpc);
        $progressMob->setRelation('mobSpecies', null);

        $autoProgress = (new QuestStepAutoProgress)->forceFill([
            'type' => 'mobs',
            'time_seconds' => null,
        ]);
        $autoProgress->setRelation('mobs', new Collection([$progressMob]));

        $questStep = (new QuestStep)->forceFill([
            'name' => 'Polowanie',
            'description' => null,
        ]);
        $questStep->setRelation('autoProgress', $autoProgress);
        $questStep->setRelation('guideView', null);

        $data = (new QuestStepResource($questStep))->resolve(Request::create('/'));

        $this->assertSame(
            'https://assets.example.test/img/npc/wolf.gif',
            $data['auto_progress']['mobs'][0]['base_npc']['src'],
        );
    }
}
