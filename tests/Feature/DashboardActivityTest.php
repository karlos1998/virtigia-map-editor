<?php

namespace Tests\Feature;

use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Models\Quest;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DashboardActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if ((getenv('DB_CONNECTION') ?: 'sqlite') === 'sqlite' && (getenv('DB_DATABASE') ?: null) === 'testing') {
            $testingDatabasePath = dirname(__DIR__, 2).'/database/testing.sqlite';

            if (! file_exists($testingDatabasePath)) {
                touch($testingDatabasePath);
            }

            putenv("DB_DATABASE={$testingDatabasePath}");
            $_ENV['DB_DATABASE'] = $testingDatabasePath;
            $_SERVER['DB_DATABASE'] = $testingDatabasePath;
        }

        parent::setUp();
    }

    public function test_it_displays_activity_analytics_for_selected_world_and_period(): void
    {
        $editor = $this->makeUser('dashboard-editor@example.com', 'Dashboard Editor');
        $reviewer = $this->makeUser('dashboard-reviewer@example.com', 'Dashboard Reviewer');

        $this->createActivity(
            event: 'attach-base-npc-loots',
            subjectType: BaseNpc::class,
            subjectId: 10,
            causer: $editor,
            world: 'retro',
            createdAt: now()->subHours(2),
            properties: [
                'base_item' => [
                    'id' => 20,
                    'name' => 'Miecz testowy',
                ],
            ],
        );
        $this->createActivity(
            event: 'updated',
            subjectType: BaseItem::class,
            subjectId: 20,
            causer: $editor,
            world: 'retro',
            createdAt: now()->subHour(),
            properties: [
                'attributes' => [
                    'name' => 'Miecz testowy',
                ],
            ],
        );
        $this->createActivity(
            event: 'created',
            subjectType: Quest::class,
            subjectId: 5,
            causer: $reviewer,
            world: 'retro',
            createdAt: now()->subMinutes(30),
            properties: [
                'attributes' => [
                    'name' => 'Wigilijna próba',
                ],
            ],
        );
        $this->createActivity(
            event: 'created',
            subjectType: Quest::class,
            subjectId: 50,
            causer: $reviewer,
            world: 'classic',
            createdAt: now()->subMinutes(15),
        );
        $this->createActivity(
            event: 'updated',
            subjectType: BaseItem::class,
            subjectId: 99,
            causer: $editor,
            world: 'retro',
            createdAt: now()->subDays(20),
        );

        $response = $this
            ->actingAs($editor)
            ->withSession(['world' => 'retro'])
            ->get(route('dashboard', ['days' => 7]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Dashboard')
            ->where('filters.days', 7)
            ->where('filters.world', 'retro')
            ->where('summary.total_activities', 3)
            ->where('summary.active_users', 2)
            ->where('summary.touched_objects', 3)
            ->has('dailyActivity', 7)
            ->has('hourlyActivity', 24)
            ->where('activityAreas.0.label', 'Itemy i loot')
            ->where('activityAreas.0.count', 2)
            ->where('attachmentFeed.0.item_name', 'Miecz testowy')
            ->where('questFocus.0.subject_name', 'Wigilijna próba'));
    }

    private function makeUser(string $email, string $name): User
    {
        return User::query()->create([
            'login' => $email,
            'name' => $name,
            'email' => $email,
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => ['admin'],
            'permissions' => ['world.read'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function createActivity(
        string $event,
        string $subjectType,
        int $subjectId,
        User $causer,
        string $world,
        CarbonInterface $createdAt,
        array $properties = [],
    ): void {
        Activity::query()->create([
            'log_name' => 'default',
            'description' => $event,
            'event' => $event,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'causer_type' => User::class,
            'causer_id' => $causer->id,
            'properties' => $properties,
            'world' => $world,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
