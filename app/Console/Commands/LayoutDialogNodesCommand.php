<?php

namespace App\Console\Commands;

use App\Models\Dialog;
use App\Models\DialogNode;
use App\Models\DynamicModel;
use App\Services\DialogLayoutService;
use Illuminate\Console\Command;

class LayoutDialogNodesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dialogs:layout-nodes
        {connection : World database connection, for example retro}
        {dialog : Dialog ID}
        {--dry-run : Show calculated positions without saving them}
        {--horizontal-gap=220 : Horizontal gap between node layers}
        {--vertical-gap=80 : Vertical gap between nodes in one layer}
        {--start-x=0 : X coordinate for the first layer}
        {--start-y=0 : Y coordinate for the first layer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Arrange dialog node positions for a selected world connection.';

    public function __construct(
        private readonly DialogLayoutService $dialogLayoutService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connection = (string) $this->argument('connection');
        $dialogId = (int) $this->argument('dialog');

        if (! array_key_exists($connection, config('database.connections'))) {
            $this->error("Database connection [{$connection}] is not configured.");

            return self::FAILURE;
        }

        if ($dialogId <= 0) {
            $this->error('Dialog ID must be a positive integer.');

            return self::FAILURE;
        }

        $horizontalGap = $this->integerOption('horizontal-gap');
        $verticalGap = $this->integerOption('vertical-gap');
        $startX = $this->integerOption('start-x');
        $startY = $this->integerOption('start-y');

        if ($horizontalGap < 0 || $verticalGap < 0) {
            $this->error('Gaps cannot be negative.');

            return self::FAILURE;
        }

        DynamicModel::setGlobalConnection($connection);

        try {
            $dialog = Dialog::on($connection)
                ->with(['nodes.options', 'edges'])
                ->find($dialogId);

            if (! $dialog) {
                $this->error("Dialog [{$dialogId}] was not found on connection [{$connection}].");

                return self::FAILURE;
            }

            $positions = $this->dialogLayoutService->calculate(
                dialog: $dialog,
                horizontalGap: $horizontalGap,
                verticalGap: $verticalGap,
                startX: $startX,
                startY: $startY,
            );

            if ($positions === []) {
                $this->warn("Dialog [{$dialogId}] has no nodes.");

                return self::SUCCESS;
            }

            $this->table(
                ['Node', 'Type', 'Depth', 'Old X', 'Old Y', 'New X', 'New Y', 'Size'],
                $this->tableRows($dialog, $positions),
            );

            if ($this->option('dry-run')) {
                $this->info('Dry run only. No positions were saved.');

                return self::SUCCESS;
            }

            $this->dialogLayoutService->save($dialog, $positions);

            $this->info('Dialog nodes were arranged successfully.');

            return self::SUCCESS;
        } finally {
            DynamicModel::clearGlobalConnection();
        }
    }

    private function integerOption(string $name): int
    {
        return (int) $this->option($name);
    }

    /**
     * @param  array<int, array{x: int, y: int, width: int, height: int, depth: int}>  $positions
     * @return list<array<int, string|int>>
     */
    private function tableRows(Dialog $dialog, array $positions): array
    {
        return $dialog->nodes
            ->sortBy(fn (DialogNode $node): array => [
                $positions[(int) $node->id]['depth'] ?? 0,
                $positions[(int) $node->id]['y'] ?? 0,
                (int) $node->id,
            ])
            ->map(function (DialogNode $node) use ($positions): array {
                $nodeId = (int) $node->id;
                $position = $positions[$nodeId];
                $oldPosition = is_array($node->position) ? $node->position : [];

                return [
                    $nodeId,
                    $node->type,
                    $position['depth'],
                    (int) ($oldPosition['x'] ?? 0),
                    (int) ($oldPosition['y'] ?? 0),
                    $position['x'],
                    $position['y'],
                    "{$position['width']}x{$position['height']}",
                ];
            })
            ->values()
            ->all();
    }
}
