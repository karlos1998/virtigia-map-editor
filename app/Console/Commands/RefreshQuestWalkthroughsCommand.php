<?php

namespace App\Console\Commands;

use App\Services\QuestWalkthrough\QuestWalkthroughService;
use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Console\Command;

class RefreshQuestWalkthroughsCommand extends Command
{
    protected $signature = 'quest-walkthroughs:refresh
        {world? : World template slug; all visible worlds when omitted}
        {--quest= : Regenerate only this quest}
        {--force : Regenerate even when no source data changed}';

    protected $description = 'Generate quest walkthroughs (solutions) from quests, dialogs and NPC placement';

    public function handle(QuestWalkthroughService $service, WorldTemplateConnectionResolver $resolver): int
    {
        $worlds = $this->argument('world') ? [$this->argument('world')] : $resolver->visibleSlugs();

        foreach ($worlds as $world) {
            if (! $service->isAvailable($world)) {
                $this->warn("{$world}: brak tabeli quest_walkthroughs (uruchom migracje remote).");

                continue;
            }

            if ($this->option('quest')) {
                $walkthrough = $service->refreshQuest($world, (int) $this->option('quest'));
                $this->info("{$world}: quest {$this->option('quest')} → {$walkthrough['status']}, sekcji: ".count($walkthrough['sections']));

                continue;
            }

            $result = $service->refreshWorld($world, (bool) $this->option('force'));
            $this->info($result['skipped']
                ? "{$world}: pominięto ({$result['reason']})"
                : "{$world}: questów {$result['quests']}, zmienionych {$result['changed']}");
        }

        return self::SUCCESS;
    }
}
