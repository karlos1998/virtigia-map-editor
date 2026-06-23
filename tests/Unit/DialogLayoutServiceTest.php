<?php

namespace Tests\Unit;

use App\Models\Dialog;
use App\Models\DialogEdge;
use App\Models\DialogNode;
use App\Models\DialogNodeOption;
use App\Services\DialogLayoutService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\TestCase;

class DialogLayoutServiceTest extends TestCase
{
    public function test_it_layouts_dialog_layers_without_overlapping_tall_nodes(): void
    {
        $service = new DialogLayoutService;
        $dialog = $this->dialogWithTeleportBranch();

        $positions = $service->calculate($dialog, horizontalGap: 220, verticalGap: 80);

        self::assertGreaterThan($positions[1]['x'], $positions[2]['x']);
        self::assertGreaterThan($positions[2]['x'], $positions[3]['x']);
        self::assertGreaterThan($positions[3]['x'], $positions[5]['x']);

        self::assertSame(0, $positions[3]['y']);
        self::assertGreaterThanOrEqual(
            $positions[3]['y'] + $positions[3]['height'] + 80,
            $positions[4]['y'],
        );

        self::assertLessThan($positions[6]['y'], $positions[5]['y']);
        self::assertLessThan($positions[7]['y'], $positions[6]['y']);
    }

    private function dialogWithTeleportBranch(): Dialog
    {
        $start = $this->node(1, 'start');
        $intro = $this->node(2, 'special', 'Witaj. Chcesz sie teleportowac?', [
            $this->option(10, 0, 'Chcialem sie teleportowac.'),
            $this->option(11, 1, 'Niczego.'),
        ]);
        $hub = $this->node(3, 'special', str_repeat('Wybierz miasto docelowe. ', 4), [
            $this->option(12, 0, 'Ithan'),
            $this->option(13, 1, 'Torneg'),
            $this->option(14, 2, 'Werbin'),
            $this->option(15, 3, 'Eder'),
            $this->option(16, 4, 'Tuzmer'),
        ]);
        $decline = $this->node(4, 'special', 'Na nogach.', [
            $this->option(17, 0, 'Zakoncz.'),
        ]);
        $ithan = $this->node(5, 'teleportation');
        $torneg = $this->node(6, 'teleportation');
        $werbin = $this->node(7, 'teleportation');

        $dialog = new Dialog;
        $dialog->setAttribute('id', 1);
        $dialog->setRelation('nodes', new EloquentCollection([
            $start,
            $intro,
            $hub,
            $decline,
            $ithan,
            $torneg,
            $werbin,
        ]));
        $dialog->setRelation('edges', new EloquentCollection([
            $this->edge(1, targetNodeId: 2, sourceNodeId: 1),
            $this->edge(2, targetNodeId: 3, sourceOptionId: 10),
            $this->edge(3, targetNodeId: 4, sourceOptionId: 11),
            $this->edge(4, targetNodeId: 5, sourceOptionId: 12),
            $this->edge(5, targetNodeId: 6, sourceOptionId: 13),
            $this->edge(6, targetNodeId: 7, sourceOptionId: 14),
        ]));

        return $dialog;
    }

    /**
     * @param  list<DialogNodeOption>  $options
     */
    private function node(int $id, string $type, ?string $content = null, array $options = []): DialogNode
    {
        $node = new DialogNode;
        $node->setAttribute('id', $id);
        $node->setAttribute('type', $type);
        $node->setAttribute('content', $content);
        $node->setAttribute('source_dialog_id', 1);
        $node->setAttribute('position', ['x' => 0, 'y' => 0]);
        $node->setRelation('options', new EloquentCollection($options));

        foreach ($options as $option) {
            $option->setAttribute('node_id', $id);
        }

        return $node;
    }

    private function option(int $id, int $order, string $label): DialogNodeOption
    {
        $option = new DialogNodeOption;
        $option->setAttribute('id', $id);
        $option->setAttribute('order', $order);
        $option->setAttribute('label', $label);
        $option->setRelation('edges', new EloquentCollection);

        return $option;
    }

    private function edge(int $id, int $targetNodeId, ?int $sourceOptionId = null, ?int $sourceNodeId = null): DialogEdge
    {
        $edge = new DialogEdge;
        $edge->setAttribute('id', $id);
        $edge->setAttribute('source_dialog_id', 1);
        $edge->setAttribute('source_option_id', $sourceOptionId);
        $edge->setAttribute('source_node_id', $sourceNodeId);
        $edge->setAttribute('target_node_id', $targetNodeId);

        return $edge;
    }
}
