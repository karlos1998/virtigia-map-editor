<?php

namespace App\Services\Mcp;

use App\Enums\BaseItemCategory;
use App\Enums\BaseItemCurrency;
use App\Enums\BaseItemRarity;
use App\Models\AiChangeSet;
use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Models\BaseNpcLoot;
use App\Models\Dialog;
use App\Models\DialogEdge;
use App\Models\DialogNode;
use App\Models\DialogNodeOption;
use App\Models\Map as GameMap;
use App\Models\Npc;
use App\Models\Quest;
use App\Models\QuestStep;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Models\User;
use App\Services\BaseItemService;
use App\Services\DialogLayoutService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AiChangeSetService
{
    private const OPERATION_TYPES = [
        'create_quest',
        'create_dialog',
        'replace_dialog',
        'patch_dialog',
        'assign_dialog_to_npc',
        'place_npc',
        'create_base_item',
        'clone_base_item',
        'update_base_item',
        'attach_item_to_shop',
        'attach_item_to_base_npc_loot',
    ];

    public function __construct(
        private readonly McpWorldService $worldService,
        private readonly DialogLayoutService $dialogLayoutService,
        private readonly BaseItemService $baseItemService,
    ) {}

    /** @param array<int, array<string, mixed>> $operations */
    public function draft(User $user, string $world, string $title, ?string $prompt, array $operations): AiChangeSet
    {
        $world = $this->worldService->use($world);
        $validation = $this->validateOperations($operations);

        if (! $validation['valid']) {
            throw ValidationException::withMessages(['operations' => $validation['errors']]);
        }

        return AiChangeSet::query()->create([
            'user_id' => $user->id,
            'world' => $world,
            'title' => $title,
            'prompt' => $prompt,
            'status' => AiChangeSet::STATUS_DRAFT,
            'revision' => 1,
            'operations' => $operations,
            'validation' => $validation,
        ]);
    }

    public function apply(User $user, string $changeSetId, int $expectedRevision): AiChangeSet
    {
        $changeSet = $this->findOwned($user, $changeSetId);

        if ($changeSet->status !== AiChangeSet::STATUS_DRAFT) {
            throw ValidationException::withMessages(['change_set' => 'Można zastosować tylko szkic zmian.']);
        }

        if ($changeSet->revision !== $expectedRevision) {
            throw ValidationException::withMessages([
                'revision' => "Wersja szkicu uległa zmianie. Aktualna wersja to {$changeSet->revision}.",
            ]);
        }

        $this->worldService->use($changeSet->world);
        $operations = $changeSet->operations ?? [];
        $validation = $this->validateOperations($operations);

        if (! $validation['valid']) {
            $changeSet->update(['validation' => $validation, 'revision' => $changeSet->revision + 1]);
            throw ValidationException::withMessages(['operations' => $validation['errors']]);
        }

        $changeSet->update([
            'status' => 'applying',
            'validation' => $validation,
            'revision' => $changeSet->revision + 1,
        ]);

        try {
            [$result, $beforeSnapshot, $afterSnapshot] = DB::connection($this->connectionName())->transaction(
                fn (): array => $this->executeOperations($operations),
            );
        } catch (Throwable $throwable) {
            $changeSet->update([
                'status' => AiChangeSet::STATUS_DRAFT,
                'revision' => $changeSet->revision + 1,
            ]);

            throw $throwable;
        }

        $changeSet->update([
            'status' => AiChangeSet::STATUS_APPLIED,
            'result' => $result,
            'before_snapshot' => $beforeSnapshot,
            'after_snapshot' => $afterSnapshot,
            'applied_at' => now(),
            'revision' => $changeSet->revision + 1,
        ]);

        return $changeSet->fresh();
    }

    public function revert(User $user, string $changeSetId): AiChangeSet
    {
        $changeSet = $this->findOwned($user, $changeSetId);

        if ($changeSet->status !== AiChangeSet::STATUS_APPLIED) {
            throw ValidationException::withMessages(['change_set' => 'Można cofnąć tylko zastosowany commit AI.']);
        }

        $this->worldService->use($changeSet->world);
        $currentSnapshot = $this->snapshotTouchedEntities($changeSet->result ?? []);

        if ($this->canonicalJson($currentSnapshot) !== $this->canonicalJson($changeSet->after_snapshot ?? [])) {
            throw ValidationException::withMessages([
                'change_set' => 'Nie można automatycznie cofnąć zmian, ponieważ część danych była później edytowana.',
            ]);
        }

        $this->assertCreatedEntitiesHaveNoOutsideReferences($changeSet->result ?? []);

        DB::connection($this->connectionName())->transaction(function () use ($changeSet): void {
            $result = $changeSet->result ?? [];
            $before = $changeSet->before_snapshot ?? [];

            foreach (Npc::query()->whereIn('id', data_get($result, 'created.npcs', []))->get() as $npc) {
                $npc->locations()->delete();
                $npc->delete();
            }

            foreach (data_get($before, 'npcs', []) as $snapshot) {
                $this->restoreNpc($snapshot);
            }

            foreach (Dialog::query()->whereIn('id', data_get($result, 'created.dialogs', []))->get() as $dialog) {
                $this->deleteDialogGraph($dialog);
                $dialog->delete();
            }

            foreach (data_get($before, 'dialogs', []) as $snapshot) {
                $this->restoreDialog($snapshot);
            }

            Quest::query()->whereIn('id', data_get($result, 'created.quests', []))->delete();

            ShopItem::query()->whereIn('id', data_get($result, 'created.shop_items', []))->delete();
            BaseNpcLoot::query()->whereIn('id', data_get($result, 'created.base_npc_loots', []))->delete();

            foreach (data_get($before, 'base_items', []) as $snapshot) {
                $this->restoreBaseItem($snapshot);
            }

            BaseItem::query()
                ->whereIn('id', data_get($result, 'created.base_items', []))
                ->get()
                ->each(fn (BaseItem $baseItem) => $baseItem->forceDelete());
        });

        $changeSet->update([
            'status' => AiChangeSet::STATUS_REVERTED,
            'reverted_at' => now(),
            'revision' => $changeSet->revision + 1,
        ]);

        return $changeSet->fresh();
    }

    /** @return array<int, array<string, mixed>> */
    public function list(User $user, ?string $world, int $limit): array
    {
        return AiChangeSet::query()
            ->where('user_id', $user->id)
            ->when($world, fn ($query, string $value) => $query->where('world', $value))
            ->latest()
            ->limit(min(max($limit, 1), 50))
            ->get()
            ->map(fn (AiChangeSet $changeSet): array => $this->present($changeSet))
            ->all();
    }

    /** @return array<string, mixed> */
    public function present(AiChangeSet $changeSet): array
    {
        return [
            'id' => $changeSet->id,
            'world' => $changeSet->world,
            'title' => $changeSet->title,
            'prompt' => $changeSet->prompt,
            'status' => $changeSet->status,
            'revision' => $changeSet->revision,
            'operations' => $this->redactBinaryPayloads($changeSet->operations),
            'validation' => $changeSet->validation,
            'result' => $changeSet->result,
            'applied_at' => $changeSet->applied_at?->toIso8601String(),
            'reverted_at' => $changeSet->reverted_at?->toIso8601String(),
            'created_at' => $changeSet->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $operations
     * @return array{valid: bool, errors: array<int, string>, warnings: array<int, string>}
     */
    public function validateOperations(array $operations): array
    {
        $validator = Validator::make(['operations' => $operations], [
            'operations' => ['required', 'array', 'min:1', 'max:30'],
            'operations.*' => ['required', 'array'],
            'operations.*.type' => ['required', 'string', 'in:'.implode(',', self::OPERATION_TYPES)],
            'operations.*.key' => ['nullable', 'string', 'regex:/^[a-z0-9][a-z0-9_-]{0,63}$/'],
            'operations.*.data' => ['nullable', 'array'],
        ]);

        $errors = $validator->errors()->all();
        $warnings = [];

        if ($errors !== []) {
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $questKeys = [];
        $stepKeys = [];
        $dialogKeys = [];
        $itemKeys = [];
        $plannedShopPositions = [];
        $plannedBaseNpcLoots = [];

        foreach ($operations as $index => $operation) {
            $type = $operation['type'];
            $data = $operation['data'] ?? [];
            $prefix = 'Operacja '.($index + 1);

            if (in_array($type, ['create_quest', 'create_dialog', 'create_base_item', 'clone_base_item'], true)) {
                $key = $operation['key'] ?? null;
                if (! is_string($key) || $key === '') {
                    $errors[] = "{$prefix}: pole key jest wymagane dla {$type}.";

                    continue;
                }

                $bucket = match ($type) {
                    'create_quest' => $questKeys,
                    'create_dialog' => $dialogKeys,
                    default => $itemKeys,
                };
                if (in_array($key, $bucket, true)) {
                    $errors[] = "{$prefix}: key [{$key}] występuje więcej niż raz.";
                }

                if ($type === 'create_quest') {
                    $questKeys[] = $key;
                } elseif ($type === 'create_dialog') {
                    $dialogKeys[] = $key;
                } else {
                    $itemKeys[] = $key;
                }
            }

            if ($type === 'create_quest') {
                $this->validateQuestOperation($data, $prefix, (string) ($operation['key'] ?? ''), $stepKeys, $errors);
            }

            if (in_array($type, ['create_dialog', 'replace_dialog'], true)) {
                if ($type === 'replace_dialog' && ! Dialog::query()->whereKey($operation['dialog_id'] ?? null)->exists()) {
                    $errors[] = "{$prefix}: dialog_id nie wskazuje istniejącego dialogu.";
                }

                $this->validateDialogGraph($data, $prefix, $errors);
            }

            if ($type === 'patch_dialog') {
                $dialog = Dialog::query()->find($operation['dialog_id'] ?? null);
                if ($dialog === null) {
                    $errors[] = "{$prefix}: dialog_id nie wskazuje istniejącego dialogu.";
                } else {
                    $this->validateDialogPatch($dialog, $data, $prefix, $errors);
                }
            }

            if ($type === 'assign_dialog_to_npc') {
                if (! Npc::query()->whereKey($operation['npc_id'] ?? null)->exists()) {
                    $errors[] = "{$prefix}: npc_id nie wskazuje istniejącego NPC.";
                }

                $this->validateDialogReference($operation, $dialogKeys, $prefix, $errors);
            }

            if ($type === 'place_npc') {
                if (! BaseNpc::query()->whereKey($operation['base_npc_id'] ?? null)->exists()) {
                    $errors[] = "{$prefix}: można wystawić NPC tylko z istniejącego base_npc_id.";
                }

                $locations = $operation['locations'] ?? [];
                if (! is_array($locations) || $locations === []) {
                    $errors[] = "{$prefix}: podaj co najmniej jedną lokalizację NPC.";
                } else {
                    foreach ($locations as $locationIndex => $location) {
                        $map = GameMap::query()->find($location['map_id'] ?? null);
                        if ($map === null) {
                            $errors[] = "{$prefix}: lokalizacja ".($locationIndex + 1).' wskazuje nieistniejącą mapę.';

                            continue;
                        }

                        $x = filter_var($location['x'] ?? null, FILTER_VALIDATE_INT);
                        $y = filter_var($location['y'] ?? null, FILTER_VALIDATE_INT);
                        if ($x === false || $y === false || $x < 0 || $y < 0 || $x >= $map->x || $y >= $map->y) {
                            $errors[] = "{$prefix}: lokalizacja ".($locationIndex + 1)." wykracza poza mapę [{$map->name}].";
                        }
                    }
                }

                if (isset($operation['dialog_id']) || isset($operation['dialog_key'])) {
                    $this->validateDialogReference($operation, $dialogKeys, $prefix, $errors);
                }
            }

            if (in_array($type, ['create_base_item', 'clone_base_item', 'update_base_item'], true)) {
                $this->validateBaseItemOperation($operation, $prefix, $errors);
            }

            if ($type === 'attach_item_to_shop') {
                $this->validateShopItemOperation($operation, $itemKeys, $plannedShopPositions, $prefix, $errors);
            }

            if ($type === 'attach_item_to_base_npc_loot') {
                $this->validateBaseNpcLootOperation($operation, $itemKeys, $plannedBaseNpcLoots, $prefix, $errors);
            }

            $this->validateExistingItemReferences($operation, $prefix, $errors);
        }

        foreach ($this->placeholderReferences($operations, '@quest:') as $reference) {
            if (! in_array($reference, $questKeys, true)) {
                $errors[] = "Nieznany tymczasowy quest [{$reference}].";
            }
        }

        foreach ($this->placeholderReferences($operations, '@step:') as $reference) {
            if (! in_array($reference, $stepKeys, true)) {
                $errors[] = "Nieznany tymczasowy krok questa [{$reference}].";
            }
        }

        foreach ($this->placeholderReferences($operations, '@item:') as $reference) {
            if (! in_array($reference, $itemKeys, true)) {
                $errors[] = "Nieznany tymczasowy item [{$reference}].";
            }
        }

        $warnings[] = 'Commit może tworzyć i edytować BaseItemy oraz przypisywać je do sklepów, lootów i dialogów questowych. Nadal nie tworzy BaseNPC ani map.';

        return [
            'valid' => $errors === [],
            'errors' => array_values(array_unique($errors)),
            'warnings' => $warnings,
        ];
    }

    /** @param array<int, string> $stepKeys @param array<int, string> $errors */
    private function validateQuestOperation(array $data, string $prefix, string $questKey, array &$stepKeys, array &$errors): void
    {
        if (! is_string($data['name'] ?? null) || trim($data['name']) === '') {
            $errors[] = "{$prefix}: quest musi mieć nazwę.";
        }

        if (! is_array($data['steps'] ?? null) || $data['steps'] === []) {
            $errors[] = "{$prefix}: quest musi mieć co najmniej jeden krok.";

            return;
        }

        foreach ($data['steps'] as $stepIndex => $step) {
            $key = $step['key'] ?? null;
            if (! is_string($key) || preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $key) !== 1) {
                $errors[] = "{$prefix}: krok ".($stepIndex + 1).' musi mieć poprawny key.';
            } elseif (in_array($questKey.':'.$key, $stepKeys, true)) {
                $errors[] = "{$prefix}: key kroku [{$key}] występuje więcej niż raz w queście.";
            } else {
                $stepKeys[] = $questKey.':'.$key;
            }

            if (! is_string($step['name'] ?? null) || trim($step['name']) === '') {
                $errors[] = "{$prefix}: krok ".($stepIndex + 1).' musi mieć nazwę.';
            }

            if (! is_string($step['description'] ?? null)) {
                $errors[] = "{$prefix}: krok ".($stepIndex + 1).' musi mieć opis.';
            }
        }
    }

    /** @param array<int, string> $errors */
    private function validateDialogGraph(array $data, string $prefix, array &$errors): void
    {
        if (! is_string($data['name'] ?? null) || trim($data['name']) === '') {
            $errors[] = "{$prefix}: dialog musi mieć nazwę.";
        }

        $nodes = $data['nodes'] ?? null;
        if (! is_array($nodes) || $nodes === []) {
            $errors[] = "{$prefix}: dialog musi mieć co najmniej jeden węzeł.";

            return;
        }

        $nodeKeys = [];
        $optionKeys = [];

        foreach ($nodes as $nodeIndex => $node) {
            $nodeKey = $node['key'] ?? null;
            if (! is_string($nodeKey) || preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $nodeKey) !== 1) {
                $errors[] = "{$prefix}: węzeł ".($nodeIndex + 1).' musi mieć poprawny key.';

                continue;
            }

            if (in_array($nodeKey, $nodeKeys, true)) {
                $errors[] = "{$prefix}: key węzła [{$nodeKey}] występuje więcej niż raz.";
            }
            $nodeKeys[] = $nodeKey;

            foreach ($node['options'] ?? [] as $optionIndex => $option) {
                $optionKey = $option['key'] ?? null;
                if (! is_string($optionKey) || preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $optionKey) !== 1) {
                    $errors[] = "{$prefix}: opcja ".($optionIndex + 1)." węzła [{$nodeKey}] musi mieć poprawny key.";

                    continue;
                }

                $compoundKey = $nodeKey.':'.$optionKey;
                if (in_array($compoundKey, $optionKeys, true)) {
                    $errors[] = "{$prefix}: opcja [{$compoundKey}] występuje więcej niż raz.";
                }
                $optionKeys[] = $compoundKey;

                if (! is_string($option['label'] ?? null) || trim($option['label']) === '') {
                    $errors[] = "{$prefix}: opcja [{$compoundKey}] musi mieć etykietę.";
                }
            }
        }

        foreach ($data['edges'] ?? [] as $edgeIndex => $edge) {
            $target = $edge['target_node_key'] ?? null;
            if (! is_string($target) || ! in_array($target, $nodeKeys, true)) {
                $errors[] = "{$prefix}: połączenie ".($edgeIndex + 1).' ma nieprawidłowy target_node_key.';
            }

            $sourceNode = $edge['source_node_key'] ?? null;
            if ($sourceNode !== null && (! is_string($sourceNode) || ! in_array($sourceNode, $nodeKeys, true))) {
                $errors[] = "{$prefix}: połączenie ".($edgeIndex + 1).' ma nieprawidłowy source_node_key.';
            }

            if (isset($edge['source_option_key'])) {
                $compoundKey = $sourceNode.':'.$edge['source_option_key'];
                if (! in_array($compoundKey, $optionKeys, true)) {
                    $errors[] = "{$prefix}: połączenie ".($edgeIndex + 1).' wskazuje nieistniejącą opcję.';
                }
            }
        }
    }

    /** @param array<int, string> $errors */
    private function validateDialogPatch(Dialog $dialog, array $data, string $prefix, array &$errors): void
    {
        $nodeKeys = [];
        $optionKeys = [];
        $dialogNodeIds = $dialog->nodes()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $dialogOptionIds = DialogNodeOption::query()
            ->whereIn('node_id', $dialogNodeIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $dialogEdgeIds = $dialog->edges()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        foreach ($data['nodes'] ?? [] as $nodeIndex => $node) {
            $nodeId = isset($node['id']) && is_numeric($node['id']) ? (int) $node['id'] : null;
            $nodeKey = $node['key'] ?? null;

            if ($nodeId === null && (! is_string($nodeKey) || preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $nodeKey) !== 1)) {
                $errors[] = "{$prefix}: węzeł ".($nodeIndex + 1).' musi wskazywać istniejące id albo mieć poprawny key.';

                continue;
            }

            if ($nodeId !== null && ! in_array($nodeId, $dialogNodeIds, true)) {
                $errors[] = "{$prefix}: węzeł [{$nodeId}] nie należy do tego dialogu.";
            }

            if (is_string($nodeKey)) {
                if (in_array($nodeKey, $nodeKeys, true)) {
                    $errors[] = "{$prefix}: key węzła [{$nodeKey}] występuje więcej niż raz.";
                }
                $nodeKeys[] = $nodeKey;
            }

            foreach ($node['options'] ?? [] as $optionIndex => $option) {
                $optionId = isset($option['id']) && is_numeric($option['id']) ? (int) $option['id'] : null;
                $optionKey = $option['key'] ?? null;

                if ($optionId === null && (! is_string($optionKey) || preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $optionKey) !== 1)) {
                    $errors[] = "{$prefix}: opcja ".($optionIndex + 1).' musi wskazywać istniejące id albo mieć poprawny key.';

                    continue;
                }

                if ($optionId !== null && ! in_array($optionId, $dialogOptionIds, true)) {
                    $errors[] = "{$prefix}: opcja [{$optionId}] nie należy do tego dialogu.";
                }

                if ($optionId === null && (! is_string($option['label'] ?? null) || trim($option['label']) === '')) {
                    $errors[] = "{$prefix}: nowa opcja [{$optionKey}] musi mieć etykietę.";
                }

                if (is_string($optionKey)) {
                    $compoundKey = ($nodeKey ?? 'node-'.$nodeId).':'.$optionKey;
                    if (in_array($compoundKey, $optionKeys, true)) {
                        $errors[] = "{$prefix}: key opcji [{$compoundKey}] występuje więcej niż raz.";
                    }
                    $optionKeys[] = $compoundKey;
                }
            }
        }

        foreach ($data['edges'] ?? [] as $edgeIndex => $edge) {
            $edgeId = isset($edge['id']) && is_numeric($edge['id']) ? (int) $edge['id'] : null;
            if ($edgeId !== null && ! in_array($edgeId, $dialogEdgeIds, true)) {
                $errors[] = "{$prefix}: połączenie [{$edgeId}] nie należy do tego dialogu.";
            }

            if ($edgeId === null && ! isset($edge['target_node_id']) && ! isset($edge['target_node_key'])) {
                $errors[] = "{$prefix}: nowe połączenie ".($edgeIndex + 1).' musi mieć target_node_id albo target_node_key.';
            }

            foreach (['source_node_id', 'target_node_id'] as $field) {
                if (isset($edge[$field]) && ! in_array((int) $edge[$field], $dialogNodeIds, true)) {
                    $errors[] = "{$prefix}: {$field} połączenia ".($edgeIndex + 1).' nie należy do tego dialogu.';
                }
            }

            if (isset($edge['source_option_id']) && ! in_array((int) $edge['source_option_id'], $dialogOptionIds, true)) {
                $errors[] = "{$prefix}: source_option_id połączenia ".($edgeIndex + 1).' nie należy do tego dialogu.';
            }
        }

        foreach (($data['delete_node_ids'] ?? []) as $id) {
            if (! in_array((int) $id, $dialogNodeIds, true)) {
                $errors[] = "{$prefix}: usuwany węzeł [{$id}] nie należy do tego dialogu.";
            }
        }

        foreach (($data['delete_option_ids'] ?? []) as $id) {
            if (! in_array((int) $id, $dialogOptionIds, true)) {
                $errors[] = "{$prefix}: usuwana opcja [{$id}] nie należy do tego dialogu.";
            }
        }

        foreach (($data['delete_edge_ids'] ?? []) as $id) {
            if (! in_array((int) $id, $dialogEdgeIds, true)) {
                $errors[] = "{$prefix}: usuwane połączenie [{$id}] nie należy do tego dialogu.";
            }
        }

        if (($data['nodes'] ?? []) === [] && ($data['edges'] ?? []) === []
            && ($data['delete_node_ids'] ?? []) === [] && ($data['delete_option_ids'] ?? []) === []
            && ($data['delete_edge_ids'] ?? []) === [] && ! array_key_exists('name', $data)) {
            $errors[] = "{$prefix}: patch dialogu nie zawiera żadnych zmian.";
        }
    }

    /** @param array<int, string> $dialogKeys @param array<int, string> $errors */
    private function validateDialogReference(array $operation, array $dialogKeys, string $prefix, array &$errors): void
    {
        $dialogId = $operation['dialog_id'] ?? null;
        $dialogKey = $operation['dialog_key'] ?? null;

        if ($dialogId !== null && Dialog::query()->whereKey($dialogId)->exists()) {
            return;
        }

        if (is_string($dialogKey) && in_array($dialogKey, $dialogKeys, true)) {
            return;
        }

        $errors[] = "{$prefix}: podaj istniejący dialog_id albo dialog_key tworzony w tym samym commicie.";
    }

    /** @param array<int, string> $errors */
    private function validateBaseItemOperation(array $operation, string $prefix, array &$errors): void
    {
        $type = $operation['type'];
        $data = $operation['data'] ?? [];
        $allowedFields = [
            'name',
            'category',
            'rarity',
            'price',
            'currency',
            'specific_currency_price',
            'attributes',
            'attributes_patch',
            'remove_attributes',
            'attribute_points',
            'manual_attribute_points',
            'reverse_attributes',
            'image_data_uri',
        ];

        if ($type === 'clone_base_item' && ! BaseItem::query()->whereKey($operation['source_base_item_id'] ?? null)->exists()) {
            $errors[] = "{$prefix}: source_base_item_id nie wskazuje istniejącego BaseItemu.";
        }

        if ($type === 'update_base_item' && ! BaseItem::query()->whereKey($operation['item_id'] ?? null)->exists()) {
            $errors[] = "{$prefix}: item_id nie wskazuje istniejącego BaseItemu.";
        }

        if (! is_array($data)) {
            $errors[] = "{$prefix}: data musi być obiektem.";

            return;
        }

        $unknownFields = array_values(array_diff(array_keys($data), $allowedFields));
        if ($unknownFields !== []) {
            $errors[] = "{$prefix}: nieobsługiwane pola BaseItemu: ".implode(', ', $unknownFields).'.';
        }

        if ($type === 'update_base_item' && $data === []) {
            $errors[] = "{$prefix}: aktualizacja itemu nie zawiera żadnych zmian.";
        }

        $requiredFields = $type === 'create_base_item'
            ? ['name', 'category', 'rarity', 'price', 'currency', 'image_data_uri']
            : [];

        foreach ($requiredFields as $field) {
            if (! array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                $errors[] = "{$prefix}: pole data.{$field} jest wymagane dla nowego itemu.";
            }
        }

        if (array_key_exists('name', $data) && (! is_string($data['name']) || mb_strlen(trim($data['name'])) < 4 || mb_strlen($data['name']) > 50)) {
            $errors[] = "{$prefix}: nazwa itemu musi mieć od 4 do 50 znaków.";
        }

        $this->validateEnumValue($data, 'category', BaseItemCategory::valuesToList(), $prefix, $errors);
        $this->validateEnumValue($data, 'rarity', BaseItemRarity::valuesToList(), $prefix, $errors);
        $this->validateEnumValue($data, 'currency', BaseItemCurrency::valuesToList(), $prefix, $errors);

        foreach (['price' => 1_000_000_000, 'specific_currency_price' => 1_000_000] as $field => $maximum) {
            if (array_key_exists($field, $data)
                && ($data[$field] !== null && (! is_int($data[$field]) || $data[$field] < 0 || $data[$field] > $maximum))) {
                $errors[] = "{$prefix}: data.{$field} musi być liczbą całkowitą od 0 do {$maximum}.";
            }
        }

        foreach (['attributes', 'attributes_patch', 'attribute_points', 'manual_attribute_points', 'reverse_attributes'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && ! is_array($data[$field])) {
                $errors[] = "{$prefix}: data.{$field} musi być obiektem JSON albo null.";
            }
        }

        if (array_key_exists('attributes', $data) && array_key_exists('attributes_patch', $data)) {
            $errors[] = "{$prefix}: użyj data.attributes albo data.attributes_patch, nie obu jednocześnie.";
        }

        if (array_key_exists('remove_attributes', $data)
            && (! is_array($data['remove_attributes']) || collect($data['remove_attributes'])->contains(fn ($key): bool => ! is_string($key) || trim($key) === ''))) {
            $errors[] = "{$prefix}: data.remove_attributes musi być listą nazw atrybutów.";
        }

        if (array_key_exists('image_data_uri', $data)) {
            $this->validateItemImage($data['image_data_uri'], $prefix, $errors);
        }
    }

    /** @param array<int, string> $allowedValues @param array<int, string> $errors */
    private function validateEnumValue(array $data, string $field, array $allowedValues, string $prefix, array &$errors): void
    {
        if (array_key_exists($field, $data) && ! in_array($data[$field], $allowedValues, true)) {
            $errors[] = "{$prefix}: data.{$field} ma nieobsługiwaną wartość.";
        }
    }

    /** @param array<int, string> $errors */
    private function validateItemImage(mixed $image, string $prefix, array &$errors): void
    {
        if (! is_string($image) || strlen($image) > 200_000
            || preg_match('/^data:image\/(png|gif);base64,/', $image) !== 1) {
            $errors[] = "{$prefix}: grafika itemu musi być PNG lub GIF 32×32 przekazanym jako data URI.";

            return;
        }

        $decoded = base64_decode(substr($image, strpos($image, ',') + 1), true);
        $imageInfo = is_string($decoded) ? @getimagesizefromstring($decoded) : false;

        if ($imageInfo === false || ! in_array($imageInfo['mime'], ['image/png', 'image/gif'], true)
            || $imageInfo[0] !== 32 || $imageInfo[1] !== 32) {
            $errors[] = "{$prefix}: grafika itemu musi być prawidłowym plikiem PNG lub GIF o wymiarach dokładnie 32×32 px.";
        }
    }

    /**
     * @param  array<int, string>  $itemKeys
     * @param  array<int, array{positions: array<int, int>, items: array<int, string>}>  $plannedShopPositions
     * @param  array<int, string>  $errors
     */
    private function validateShopItemOperation(
        array $operation,
        array $itemKeys,
        array &$plannedShopPositions,
        string $prefix,
        array &$errors,
    ): void {
        $shop = Shop::query()->find($operation['shop_id'] ?? null);
        if ($shop === null) {
            $errors[] = "{$prefix}: shop_id nie wskazuje istniejącego sklepu.";

            return;
        }

        $itemReference = $this->validateItemReference($operation, $itemKeys, $prefix, $errors);
        $position = $this->shopPosition($operation, $prefix, $errors);
        if ($itemReference === null || $position === null) {
            return;
        }

        $shopId = (int) $shop->id;
        $plannedShopPositions[$shopId] ??= [
            'positions' => $shop->items()->pluck('shop_items.position')->map(fn ($value): int => (int) $value)->all(),
            'items' => $shop->items()->pluck('base_items.id')->map(fn ($value): string => 'id:'.(int) $value)->all(),
        ];

        if (in_array($position, $plannedShopPositions[$shopId]['positions'], true)) {
            $errors[] = "{$prefix}: pozycja {$position} w sklepie [{$shop->name}] jest już zajęta.";
        }

        if (in_array($itemReference, $plannedShopPositions[$shopId]['items'], true)) {
            $errors[] = "{$prefix}: ten item jest już przypisany do sklepu [{$shop->name}].";
        }

        $plannedShopPositions[$shopId]['positions'][] = $position;
        $plannedShopPositions[$shopId]['items'][] = $itemReference;
    }

    /** @param array<int, string> $errors */
    private function shopPosition(array $operation, string $prefix, array &$errors): ?int
    {
        $position = $operation['position'] ?? null;
        $row = $operation['row'] ?? null;
        $column = $operation['column'] ?? null;

        if ($position === null && ($row === null || $column === null)) {
            $errors[] = "{$prefix}: podaj position 0–79 albo row 0–9 i column 0–7.";

            return null;
        }

        if (($row === null) !== ($column === null)) {
            $errors[] = "{$prefix}: row i column muszą być podane razem.";

            return null;
        }

        if ($row !== null && (! is_int($row) || $row < 0 || $row > 9 || ! is_int($column) || $column < 0 || $column > 7)) {
            $errors[] = "{$prefix}: sklep ma 10 rzędów 0–9 i 8 kolumn 0–7.";

            return null;
        }

        $gridPosition = $row !== null ? ($row * 8) + $column : null;
        if ($position !== null && (! is_int($position) || $position < 0 || $position > 79)) {
            $errors[] = "{$prefix}: position musi mieścić się w zakresie 0–79.";

            return null;
        }

        if ($position !== null && $gridPosition !== null && $position !== $gridPosition) {
            $errors[] = "{$prefix}: position nie zgadza się z row × 8 + column.";

            return null;
        }

        return $position ?? $gridPosition;
    }

    /** @param array<int, string> $itemKeys @param array<int, array<int, string>> $plannedBaseNpcLoots @param array<int, string> $errors */
    private function validateBaseNpcLootOperation(
        array $operation,
        array $itemKeys,
        array &$plannedBaseNpcLoots,
        string $prefix,
        array &$errors,
    ): void {
        $baseNpcId = (int) ($operation['base_npc_id'] ?? 0);
        if (! BaseNpc::query()->whereKey($baseNpcId)->exists()) {
            $errors[] = "{$prefix}: base_npc_id nie wskazuje istniejącego BaseNPC.";
        }

        $itemReference = $this->validateItemReference($operation, $itemKeys, $prefix, $errors);
        if ($itemReference === null || $baseNpcId < 1) {
            return;
        }

        $plannedBaseNpcLoots[$baseNpcId] ??= BaseNpcLoot::query()
            ->where('base_npc_id', $baseNpcId)
            ->pluck('base_item_id')
            ->map(fn ($value): string => 'id:'.(int) $value)
            ->all();

        if (in_array($itemReference, $plannedBaseNpcLoots[$baseNpcId], true)) {
            $errors[] = "{$prefix}: ten item jest już lootem wskazanego BaseNPC.";
        }

        $plannedBaseNpcLoots[$baseNpcId][] = $itemReference;
    }

    /** @param array<int, string> $itemKeys @param array<int, string> $errors */
    private function validateItemReference(array $operation, array $itemKeys, string $prefix, array &$errors): ?string
    {
        $itemId = $operation['item_id'] ?? null;
        $itemKey = $operation['item_key'] ?? null;

        if ($itemId !== null && $itemKey !== null) {
            $errors[] = "{$prefix}: podaj item_id albo item_key, nie oba.";

            return null;
        }

        if (is_numeric($itemId) && BaseItem::query()->whereKey((int) $itemId)->exists()) {
            return 'id:'.(int) $itemId;
        }

        if (is_string($itemKey) && in_array($itemKey, $itemKeys, true)) {
            return 'key:'.$itemKey;
        }

        $errors[] = "{$prefix}: podaj istniejący item_id albo item_key utworzony wcześniej w tym commicie.";

        return null;
    }

    /** @param array<int, string> $errors */
    private function validateExistingItemReferences(array $operation, string $prefix, array &$errors): void
    {
        $itemIds = [];
        $this->collectItemIds($operation, $itemIds);
        $itemIds = array_values(array_unique(array_filter($itemIds, fn ($id): bool => is_int($id) && $id > 0)));

        if ($itemIds === []) {
            return;
        }

        $existingIds = BaseItem::query()->whereIn('id', $itemIds)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $missingIds = array_values(array_diff($itemIds, $existingIds));

        if ($missingIds !== []) {
            $errors[] = "{$prefix}: itemy [".implode(', ', $missingIds).'] nie istnieją; użyj istniejących ID albo placeholderów @item:key z tego commita.';
        }
    }

    /** @param array<int, int> $itemIds */
    private function collectItemIds(mixed $value, array &$itemIds, ?string $parentKey = null): void
    {
        if (! is_array($value)) {
            return;
        }

        foreach ($value as $key => $child) {
            $keyName = is_string($key) ? $key : $parentKey;

            if (in_array($keyName, ['addItems', 'removeItems', 'items', 'equippedItems'], true) && is_array($child)) {
                foreach (Arr::wrap(data_get($child, 'value')) as $itemId) {
                    if (is_numeric($itemId)) {
                        $itemIds[] = (int) $itemId;
                    }
                }
            }

            if ($keyName === 'blessing' && is_array($child) && is_numeric(data_get($child, 'value'))) {
                $itemIds[] = (int) data_get($child, 'value');
            }

            $this->collectItemIds($child, $itemIds, $keyName);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $operations
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private function executeOperations(array $operations): array
    {
        $result = [
            'created' => [
                'quests' => [],
                'dialogs' => [],
                'npcs' => [],
                'base_items' => [],
                'shop_items' => [],
                'base_npc_loots' => [],
            ],
            'updated' => ['dialogs' => [], 'npcs' => [], 'base_items' => []],
            'references' => ['quests' => [], 'steps' => [], 'dialogs' => [], 'items' => []],
        ];
        $before = ['dialogs' => [], 'npcs' => [], 'base_items' => []];

        foreach ($operations as $operation) {
            if (! in_array($operation['type'], ['create_base_item', 'clone_base_item', 'update_base_item'], true)) {
                continue;
            }

            $data = $operation['data'] ?? [];
            if ($operation['type'] === 'create_base_item') {
                $baseItem = new BaseItem;
                $baseItem->forceFill([
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'rarity' => $data['rarity'],
                    'price' => $data['price'],
                    'currency' => $data['currency'],
                    'specific_currency_price' => $data['specific_currency_price'] ?? null,
                    'attributes' => $data['attributes'] ?? null,
                    'attribute_points' => $data['attribute_points'] ?? null,
                    'manual_attribute_points' => $data['manual_attribute_points'] ?? null,
                    'reverse_attributes' => $data['reverse_attributes'] ?? null,
                    'edited_manually' => true,
                    'src' => '',
                    'stats' => '',
                    'cl' => 0,
                    'pr' => 0,
                ])->save();
            } elseif ($operation['type'] === 'clone_base_item') {
                $sourceBaseItem = BaseItem::query()->findOrFail($operation['source_base_item_id']);
                $baseItem = $sourceBaseItem->replicate();
                $baseItem->forceFill(['stats' => '', 'edited_manually' => true])->save();
                $this->applyBaseItemData($baseItem, $data);
            } else {
                $baseItem = BaseItem::query()->findOrFail($operation['item_id']);
                $before['base_items'][(string) $baseItem->id] ??= $this->snapshotBaseItem($baseItem);
                $this->applyBaseItemData($baseItem, $data);
                $result['updated']['base_items'][] = $baseItem->id;
            }

            if ($operation['type'] !== 'update_base_item') {
                if ($operation['type'] === 'create_base_item') {
                    $this->applyBaseItemData($baseItem, $data);
                }
                $result['created']['base_items'][] = $baseItem->id;
                $result['references']['items'][$operation['key']] = $baseItem->id;
            }
        }

        foreach ($operations as $operation) {
            if ($operation['type'] !== 'create_quest') {
                continue;
            }

            $quest = Quest::query()->create(['name' => $operation['data']['name']]);
            $result['created']['quests'][] = $quest->id;
            $result['references']['quests'][$operation['key']] = $quest->id;

            foreach ($operation['data']['steps'] as $stepData) {
                $step = $quest->steps()->create([
                    'name' => $stepData['name'],
                    'description' => $stepData['description'],
                    'visible_in_quest_list' => $stepData['visible_in_quest_list'] ?? true,
                    'auto_advance_next_day' => $stepData['auto_advance_next_day'] ?? false,
                    'auto_advance_to_step_id' => null,
                ]);
                $result['references']['steps'][$operation['key'].':'.$stepData['key']] = $step->id;
            }
        }

        foreach ($operations as $operation) {
            if (! in_array($operation['type'], ['create_dialog', 'replace_dialog', 'patch_dialog'], true)) {
                continue;
            }

            $data = $this->resolvePlaceholders($operation['data'], $result['references']);

            if ($operation['type'] === 'patch_dialog') {
                $dialog = Dialog::query()->findOrFail($operation['dialog_id']);
                $before['dialogs'][(string) $dialog->id] ??= $this->snapshotDialog($dialog);
                $result['updated']['dialogs'][] = $dialog->id;
                $this->patchDialogGraph($dialog, $data);

                continue;
            }

            if ($operation['type'] === 'create_dialog') {
                $dialog = Dialog::query()->create(['name' => $data['name']]);
                $result['created']['dialogs'][] = $dialog->id;
                $result['references']['dialogs'][$operation['key']] = $dialog->id;
            } else {
                $dialog = Dialog::query()->findOrFail($operation['dialog_id']);
                $before['dialogs'][(string) $dialog->id] = $this->snapshotDialog($dialog);
                $result['updated']['dialogs'][] = $dialog->id;
                $this->deleteDialogGraph($dialog);
                $dialog->update(['name' => $data['name']]);
            }

            $this->createDialogGraph($dialog, $data);
        }

        foreach ($operations as $operation) {
            if ($operation['type'] === 'assign_dialog_to_npc') {
                $npc = Npc::query()->findOrFail($operation['npc_id']);
                $before['npcs'][(string) $npc->id] ??= $this->snapshotNpc($npc);
                $npc->forceFill(['dialog_id' => $this->resolveDialogId($operation, $result['references'])])->save();
                $result['updated']['npcs'][] = $npc->id;
            }

            if ($operation['type'] === 'place_npc') {
                $npc = new Npc;
                $npc->forceFill([
                    'base_npc_id' => $operation['base_npc_id'],
                    'dialog_id' => isset($operation['dialog_id']) || isset($operation['dialog_key'])
                        ? $this->resolveDialogId($operation, $result['references'])
                        : null,
                    'enabled' => $operation['enabled'] ?? true,
                    'auto_start_dialog' => $operation['auto_start_dialog'] ?? false,
                    'auto_start_dialog_range' => $operation['auto_start_dialog_range'] ?? 1,
                ])->save();

                foreach ($operation['locations'] as $location) {
                    $npc->locations()->create([
                        'map_id' => $location['map_id'],
                        'x' => $location['x'],
                        'y' => $location['y'],
                    ]);
                }

                $result['created']['npcs'][] = $npc->id;
            }

            if ($operation['type'] === 'attach_item_to_shop') {
                $shopItem = new ShopItem;
                $shopItem->forceFill([
                    'shop_id' => $operation['shop_id'],
                    'item_id' => $this->resolveItemId($operation, $result['references']),
                    'position' => $this->resolvedShopPosition($operation),
                ])->save();
                $result['created']['shop_items'][] = $shopItem->id;
            }

            if ($operation['type'] === 'attach_item_to_base_npc_loot') {
                $baseNpcLoot = new BaseNpcLoot;
                $baseNpcLoot->forceFill([
                    'base_npc_id' => $operation['base_npc_id'],
                    'base_item_id' => $this->resolveItemId($operation, $result['references']),
                ])->save();
                $result['created']['base_npc_loots'][] = $baseNpcLoot->id;
            }
        }

        $result['created'] = array_map(fn (array $ids): array => array_values(array_unique($ids)), $result['created']);
        $result['updated'] = array_map(fn (array $ids): array => array_values(array_unique($ids)), $result['updated']);

        return [$result, $before, $this->snapshotTouchedEntities($result)];
    }

    /** @param array<string, mixed> $data */
    private function createDialogGraph(Dialog $dialog, array $data): void
    {
        $nodes = [];
        $options = [];

        foreach ($data['nodes'] as $nodeData) {
            $node = $dialog->nodes()->create([
                'type' => $nodeData['type'] ?? 'special',
                'position' => $nodeData['position'] ?? ['x' => 0, 'y' => 0],
                'content' => $nodeData['content'] ?? null,
                'action_data' => $nodeData['action_data'] ?? null,
                'additional_actions' => $nodeData['additional_actions'] ?? null,
                'shop_id' => $nodeData['shop_id'] ?? null,
                'hotel_id' => $nodeData['hotel_id'] ?? null,
            ]);
            $nodes[$nodeData['key']] = $node;

            foreach ($nodeData['options'] ?? [] as $optionIndex => $optionData) {
                $option = $node->options()->create([
                    'label' => $optionData['label'],
                    'rules' => $optionData['rules'] ?? null,
                    'additional_action' => $optionData['additional_action'] ?? null,
                    'additional_actions' => $optionData['additional_actions'] ?? null,
                    'cooldown' => $optionData['cooldown'] ?? null,
                    'order' => $optionData['order'] ?? $optionIndex,
                ]);
                $options[$nodeData['key'].':'.$optionData['key']] = $option;
            }
        }

        foreach ($data['edges'] ?? [] as $edgeData) {
            $edge = new DialogEdge;
            $edge->forceFill([
                'source_dialog_id' => $dialog->id,
                'source_node_id' => isset($edgeData['source_node_key']) ? $nodes[$edgeData['source_node_key']]->id : null,
                'source_option_id' => isset($edgeData['source_option_key'])
                    ? $options[$edgeData['source_node_key'].':'.$edgeData['source_option_key']]->id
                    : null,
                'source_handle' => $edgeData['source_handle'] ?? null,
                'target_node_id' => $nodes[$edgeData['target_node_key']]->id,
                'rules' => $edgeData['rules'] ?? null,
            ])->save();
        }
    }

    /** @param array<string, mixed> $data */
    private function patchDialogGraph(Dialog $dialog, array $data): void
    {
        if (array_key_exists('name', $data)) {
            $dialog->update(['name' => $data['name']]);
        }

        $deleteNodeIds = array_map('intval', $data['delete_node_ids'] ?? []);
        $deleteOptionIds = array_map('intval', $data['delete_option_ids'] ?? []);
        $deleteEdgeIds = array_map('intval', $data['delete_edge_ids'] ?? []);

        if ($deleteNodeIds !== []) {
            $optionIds = DialogNodeOption::query()->whereIn('node_id', $deleteNodeIds)->pluck('id');
            DialogEdge::query()
                ->where('source_dialog_id', $dialog->id)
                ->where(function ($query) use ($deleteNodeIds, $optionIds): void {
                    $query->whereIn('source_node_id', $deleteNodeIds)
                        ->orWhereIn('target_node_id', $deleteNodeIds)
                        ->orWhereIn('source_option_id', $optionIds);
                })
                ->delete();
            DialogNodeOption::query()->whereIn('node_id', $deleteNodeIds)->delete();
            DialogNode::query()->whereIn('id', $deleteNodeIds)->delete();
        }

        if ($deleteOptionIds !== []) {
            DialogEdge::query()->where('source_dialog_id', $dialog->id)->whereIn('source_option_id', $deleteOptionIds)->delete();
            DialogNodeOption::query()->whereIn('id', $deleteOptionIds)->delete();
        }

        if ($deleteEdgeIds !== []) {
            DialogEdge::query()->where('source_dialog_id', $dialog->id)->whereIn('id', $deleteEdgeIds)->delete();
        }

        $nodesByKey = [];
        $optionsByKey = [];

        foreach ($data['nodes'] ?? [] as $nodeData) {
            if (isset($nodeData['id'])) {
                $node = DialogNode::query()
                    ->where('source_dialog_id', $dialog->id)
                    ->findOrFail($nodeData['id']);
            } else {
                $node = $dialog->nodes()->create([
                    'type' => $nodeData['type'] ?? 'special',
                    'position' => $nodeData['position'] ?? ['x' => 0, 'y' => 0],
                    'content' => $nodeData['content'] ?? null,
                    'action_data' => $nodeData['action_data'] ?? null,
                    'additional_actions' => $nodeData['additional_actions'] ?? null,
                    'shop_id' => $nodeData['shop_id'] ?? null,
                    'hotel_id' => $nodeData['hotel_id'] ?? null,
                ]);
            }

            $nodeFields = array_intersect_key($nodeData, array_flip([
                'type', 'position', 'content', 'action_data', 'additional_actions', 'shop_id', 'hotel_id',
            ]));
            if ($node->exists && $nodeFields !== []) {
                $node->forceFill($nodeFields)->save();
            }

            if (isset($nodeData['key'])) {
                $nodesByKey[$nodeData['key']] = $node;
            }

            foreach ($nodeData['options'] ?? [] as $optionIndex => $optionData) {
                if (isset($optionData['id'])) {
                    $option = $node->options()->findOrFail($optionData['id']);
                } else {
                    $option = $node->options()->create([
                        'label' => $optionData['label'],
                        'rules' => $optionData['rules'] ?? null,
                        'additional_action' => $optionData['additional_action'] ?? null,
                        'additional_actions' => $optionData['additional_actions'] ?? null,
                        'cooldown' => $optionData['cooldown'] ?? null,
                        'order' => $optionData['order'] ?? $optionIndex,
                    ]);
                }

                $optionFields = array_intersect_key($optionData, array_flip([
                    'label', 'rules', 'additional_action', 'additional_actions', 'cooldown', 'order',
                ]));
                if ($option->exists && $optionFields !== []) {
                    $option->forceFill($optionFields)->save();
                }

                if (isset($optionData['key'])) {
                    $optionsByKey[($nodeData['key'] ?? 'node-'.$node->id).':'.$optionData['key']] = $option;
                }
            }
        }

        foreach ($data['edges'] ?? [] as $edgeData) {
            $edge = isset($edgeData['id'])
                ? $dialog->edges()->findOrFail($edgeData['id'])
                : new DialogEdge;

            $fields = array_intersect_key($edgeData, array_flip(['source_handle', 'rules']));
            $fields['source_dialog_id'] = $dialog->id;

            foreach (['source_node', 'target_node'] as $reference) {
                $idField = $reference.'_id';
                $keyField = $reference.'_key';
                if (array_key_exists($idField, $edgeData)) {
                    $fields[$idField] = $edgeData[$idField];
                } elseif (isset($edgeData[$keyField])) {
                    $fields[$idField] = $nodesByKey[$edgeData[$keyField]]->id;
                }
            }

            if (array_key_exists('source_option_id', $edgeData)) {
                $fields['source_option_id'] = $edgeData['source_option_id'];
            } elseif (isset($edgeData['source_option_key'])) {
                $sourceNodeReference = $edgeData['source_node_key'] ?? 'node-'.$edgeData['source_node_id'];
                $fields['source_option_id'] = $optionsByKey[$sourceNodeReference.':'.$edgeData['source_option_key']]->id;
            }

            $edge->forceFill($fields)->save();
        }

        $dialog->unsetRelation('nodes')->unsetRelation('edges');
        $positions = $this->dialogLayoutService->calculate($dialog->fresh());
        $this->dialogLayoutService->save($dialog, $positions);
    }

    private function deleteDialogGraph(Dialog $dialog): void
    {
        $dialog->edges()->delete();
        DialogNodeOption::query()->whereIn('node_id', $dialog->nodes()->pluck('id'))->delete();
        $dialog->nodes()->delete();
    }

    /** @return array<string, mixed> */
    private function snapshotTouchedEntities(array $result): array
    {
        $questIds = data_get($result, 'created.quests', []);
        $dialogIds = array_values(array_unique(array_merge(
            data_get($result, 'created.dialogs', []),
            data_get($result, 'updated.dialogs', []),
        )));
        $npcIds = array_values(array_unique(array_merge(
            data_get($result, 'created.npcs', []),
            data_get($result, 'updated.npcs', []),
        )));
        $baseItemIds = array_values(array_unique(array_merge(
            data_get($result, 'created.base_items', []),
            data_get($result, 'updated.base_items', []),
        )));
        $shopItemIds = data_get($result, 'created.shop_items', []);
        $baseNpcLootIds = data_get($result, 'created.base_npc_loots', []);

        return [
            'quests' => Quest::query()->whereIn('id', $questIds)->orderBy('id')->get()
                ->mapWithKeys(fn (Quest $quest): array => [(string) $quest->id => $this->snapshotQuest($quest)])->all(),
            'dialogs' => Dialog::query()->whereIn('id', $dialogIds)->orderBy('id')->get()
                ->mapWithKeys(fn (Dialog $dialog): array => [(string) $dialog->id => $this->snapshotDialog($dialog)])->all(),
            'npcs' => Npc::query()->whereIn('id', $npcIds)->orderBy('id')->get()
                ->mapWithKeys(fn (Npc $npc): array => [(string) $npc->id => $this->snapshotNpc($npc)])->all(),
            'base_items' => BaseItem::query()->whereIn('id', $baseItemIds)->orderBy('id')->get()
                ->mapWithKeys(fn (BaseItem $baseItem): array => [(string) $baseItem->id => $this->snapshotBaseItem($baseItem)])->all(),
            'shop_items' => ShopItem::query()->whereIn('id', $shopItemIds)->orderBy('id')->get()
                ->mapWithKeys(fn (ShopItem $shopItem): array => [(string) $shopItem->id => $this->snapshotShopItem($shopItem)])->all(),
            'base_npc_loots' => BaseNpcLoot::query()->whereIn('id', $baseNpcLootIds)->orderBy('id')->get()
                ->mapWithKeys(fn (BaseNpcLoot $baseNpcLoot): array => [(string) $baseNpcLoot->id => $this->snapshotBaseNpcLoot($baseNpcLoot)])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function snapshotQuest(Quest $quest): array
    {
        $quest->load('steps');

        return [
            'id' => $quest->id,
            'name' => $quest->name,
            'steps' => $quest->steps->sortBy('id')->map(fn (QuestStep $step): array => [
                'id' => $step->id,
                'quest_id' => $step->quest_id,
                'name' => $step->name,
                'description' => $step->description,
                'visible_in_quest_list' => $step->visible_in_quest_list,
                'auto_advance_next_day' => $step->auto_advance_next_day,
                'auto_advance_to_step_id' => $step->auto_advance_to_step_id,
            ])->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function snapshotDialog(Dialog $dialog): array
    {
        $dialog->load(['nodes.options', 'edges']);

        return [
            'id' => $dialog->id,
            'name' => $dialog->name,
            'nodes' => $dialog->nodes->sortBy('id')->map(fn (DialogNode $node): array => [
                'id' => $node->id,
                'source_dialog_id' => $node->source_dialog_id,
                'type' => $node->type,
                'position' => $node->position,
                'content' => $node->content,
                'action_data' => $node->action_data,
                'additional_actions' => $node->additional_actions,
                'shop_id' => $node->shop_id,
                'hotel_id' => $node->hotel_id,
                'options' => $node->options->sortBy('id')->map(fn (DialogNodeOption $option): array => [
                    'id' => $option->id,
                    'node_id' => $option->node_id,
                    'label' => $option->label,
                    'rules' => $option->rules,
                    'additional_action' => $option->additional_action?->value,
                    'additional_actions' => $option->additional_actions,
                    'order' => $option->order,
                    'cooldown' => $option->cooldown,
                ])->values()->all(),
            ])->values()->all(),
            'edges' => $dialog->edges->sortBy('id')->map(fn (DialogEdge $edge): array => [
                'id' => $edge->id,
                'source_dialog_id' => $edge->source_dialog_id,
                'source_node_id' => $edge->source_node_id,
                'source_option_id' => $edge->source_option_id,
                'source_handle' => $edge->source_handle,
                'target_node_id' => $edge->target_node_id,
                'rules' => $edge->rules,
            ])->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function snapshotNpc(Npc $npc): array
    {
        $npc->load('locations');

        return [
            'id' => $npc->id,
            'base_npc_id' => $npc->base_npc_id,
            'dialog_id' => $npc->dialog_id,
            'group_id' => $npc->group_id,
            'manually_group_detached' => $npc->manually_group_detached,
            'enabled' => $npc->enabled,
            'auto_start_dialog' => $npc->auto_start_dialog,
            'auto_start_dialog_range' => $npc->auto_start_dialog_range,
            'locations' => $npc->locations->sortBy('id')->map(fn ($location): array => [
                'id' => $location->id,
                'npc_id' => $location->npc_id,
                'map_id' => $location->map_id,
                'x' => $location->x,
                'y' => $location->y,
            ])->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function snapshotBaseItem(BaseItem $baseItem): array
    {
        return [
            'id' => $baseItem->id,
            'name' => $baseItem->name,
            'src' => $baseItem->src,
            'stats' => $baseItem->stats,
            'cl' => $baseItem->cl,
            'pr' => $baseItem->pr,
            'edited_manually' => $baseItem->edited_manually,
            'attributes' => $baseItem->attributes,
            'attribute_points' => $baseItem->attribute_points,
            'manual_attribute_points' => $baseItem->manual_attribute_points,
            'reverse_attributes' => $baseItem->reverse_attributes,
            'category' => $baseItem->category?->value,
            'currency' => $baseItem->currency?->value,
            'price' => $baseItem->price,
            'specific_currency_price' => $baseItem->specific_currency_price,
            'rarity' => $baseItem->rarity,
        ];
    }

    /** @return array<string, mixed> */
    private function snapshotShopItem(ShopItem $shopItem): array
    {
        return [
            'id' => $shopItem->id,
            'shop_id' => $shopItem->shop_id,
            'item_id' => $shopItem->item_id,
            'position' => $shopItem->position,
        ];
    }

    /** @return array<string, mixed> */
    private function snapshotBaseNpcLoot(BaseNpcLoot $baseNpcLoot): array
    {
        return [
            'id' => $baseNpcLoot->id,
            'base_npc_id' => $baseNpcLoot->base_npc_id,
            'base_item_id' => $baseNpcLoot->base_item_id,
        ];
    }

    /** @param array<string, mixed> $snapshot */
    private function restoreDialog(array $snapshot): void
    {
        $dialog = Dialog::query()->findOrFail($snapshot['id']);
        $this->deleteDialogGraph($dialog);
        $dialog->forceFill(['name' => $snapshot['name']])->save();

        foreach ($snapshot['nodes'] as $nodeData) {
            $options = $nodeData['options'];
            unset($nodeData['options']);
            $node = new DialogNode;
            $node->forceFill($nodeData)->save();

            foreach ($options as $optionData) {
                $option = new DialogNodeOption;
                $option->forceFill($optionData)->save();
            }
        }

        foreach ($snapshot['edges'] as $edgeData) {
            $edge = new DialogEdge;
            $edge->forceFill($edgeData)->save();
        }
    }

    /** @param array<string, mixed> $snapshot */
    private function restoreNpc(array $snapshot): void
    {
        $locations = $snapshot['locations'];
        unset($snapshot['locations']);
        $npc = Npc::query()->findOrFail($snapshot['id']);
        $npc->forceFill($snapshot)->save();
        $npc->locations()->delete();

        foreach ($locations as $locationData) {
            $location = $npc->locations()->make();
            $location->forceFill($locationData)->save();
        }
    }

    /** @param array<string, mixed> $snapshot */
    private function restoreBaseItem(array $snapshot): void
    {
        BaseItem::query()->findOrFail($snapshot['id'])->forceFill($snapshot)->save();
    }

    /** @param array<string, mixed> $data */
    private function applyBaseItemData(BaseItem $baseItem, array $data): void
    {
        $fields = Arr::only($data, [
            'name',
            'category',
            'rarity',
            'price',
            'currency',
            'specific_currency_price',
            'attribute_points',
            'manual_attribute_points',
            'reverse_attributes',
        ]);

        if (array_key_exists('attributes', $data)) {
            $fields['attributes'] = $data['attributes'] === [] ? null : $data['attributes'];
        } elseif (array_key_exists('attributes_patch', $data) || array_key_exists('remove_attributes', $data)) {
            $attributes = $baseItem->attributes ?? [];
            foreach ($data['attributes_patch'] ?? [] as $key => $value) {
                if ($value === null) {
                    unset($attributes[$key]);
                } else {
                    $attributes[$key] = $value;
                }
            }
            foreach ($data['remove_attributes'] ?? [] as $key) {
                unset($attributes[$key]);
            }
            $fields['attributes'] = $attributes === [] ? null : $attributes;
        }

        $baseItem->forceFill([...$fields, 'edited_manually' => true])->save();

        if (isset($data['image_data_uri'])) {
            $this->baseItemService->updateImageFromBase64(
                $baseItem,
                Str::of($data['image_data_uri']),
                Str::of($baseItem->name),
                'img',
            );
        }
    }

    private function assertCreatedEntitiesHaveNoOutsideReferences(array $result): void
    {
        $createdDialogIds = data_get($result, 'created.dialogs', []);
        $touchedNpcIds = array_merge(data_get($result, 'created.npcs', []), data_get($result, 'updated.npcs', []));
        $outsideNpc = Npc::query()
            ->whereIn('dialog_id', $createdDialogIds)
            ->whereNotIn('id', $touchedNpcIds)
            ->exists();

        if ($outsideNpc) {
            throw ValidationException::withMessages([
                'change_set' => 'Nie można cofnąć commita: utworzony dialog został później przypisany do innego NPC.',
            ]);
        }

        $touchedDialogIds = array_merge($createdDialogIds, data_get($result, 'updated.dialogs', []));
        foreach (Quest::query()->whereIn('id', data_get($result, 'created.quests', []))->get() as $quest) {
            $outsideDialogs = $quest->getDialogs()->pluck('id')->diff($touchedDialogIds);
            if ($outsideDialogs->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'change_set' => 'Nie można cofnąć commita: utworzony quest jest używany przez później zmieniony dialog.',
                ]);
            }
        }

        $createdItemIds = data_get($result, 'created.base_items', []);
        $createdShopItemIds = data_get($result, 'created.shop_items', []);
        $createdBaseNpcLootIds = data_get($result, 'created.base_npc_loots', []);
        foreach (BaseItem::query()->whereIn('id', $createdItemIds)->get() as $baseItem) {
            $hasOutsideShop = ShopItem::query()
                ->where('item_id', $baseItem->id)
                ->whereNotIn('id', $createdShopItemIds)
                ->exists();
            $hasOutsideLoot = BaseNpcLoot::query()
                ->where('base_item_id', $baseItem->id)
                ->whereNotIn('id', $createdBaseNpcLootIds)
                ->exists();
            $outsideDialogs = $baseItem->dialogs()->pluck('dialogs.id')->diff($touchedDialogIds);

            if ($hasOutsideShop || $hasOutsideLoot || $outsideDialogs->isNotEmpty() || $baseItem->hotelRooms()->exists()) {
                throw ValidationException::withMessages([
                    'change_set' => "Nie można cofnąć commita: utworzony item [{$baseItem->id}] został później użyty poza tym commitem.",
                ]);
            }
        }
    }

    /** @param array<string, mixed> $references */
    private function resolvePlaceholders(mixed $value, array $references, array $path = []): mixed
    {
        if (is_array($value)) {
            $resolved = [];
            foreach ($value as $key => $child) {
                $resolved[$key] = $this->resolvePlaceholders($child, $references, [...$path, (string) $key]);
            }

            return $resolved;
        }

        if (! is_string($value)) {
            return $value;
        }

        if (str_starts_with($value, '@quest:')) {
            return 'q-'.data_get($references, 'quests.'.substr($value, 7));
        }

        if (str_starts_with($value, '@step:')) {
            $id = data_get($references, 'steps.'.substr($value, 6));

            return str_ends_with(implode('.', $path), 'setQuestStep.value') ? $id : 's-'.$id;
        }

        if (str_starts_with($value, '@item:')) {
            return (int) data_get($references, 'items.'.substr($value, 6));
        }

        return $value;
    }

    /** @param array<string, mixed> $references */
    private function resolveDialogId(array $operation, array $references): int
    {
        return isset($operation['dialog_id'])
            ? (int) $operation['dialog_id']
            : (int) data_get($references, 'dialogs.'.$operation['dialog_key']);
    }

    /** @param array<string, mixed> $references */
    private function resolveItemId(array $operation, array $references): int
    {
        return isset($operation['item_id'])
            ? (int) $operation['item_id']
            : (int) data_get($references, 'items.'.$operation['item_key']);
    }

    private function resolvedShopPosition(array $operation): int
    {
        return isset($operation['position'])
            ? (int) $operation['position']
            : ((int) $operation['row'] * 8) + (int) $operation['column'];
    }

    /** @return array<int, string> */
    private function placeholderReferences(mixed $value, string $prefix): array
    {
        if (is_string($value)) {
            return str_starts_with($value, $prefix) ? [substr($value, strlen($prefix))] : [];
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->flatMap(fn ($child) => $this->placeholderReferences($child, $prefix))
            ->unique()
            ->values()
            ->all();
    }

    private function findOwned(User $user, string $changeSetId): AiChangeSet
    {
        return AiChangeSet::query()
            ->where('user_id', $user->id)
            ->findOrFail($changeSetId);
    }

    private function connectionName(): string
    {
        return (string) (new Quest)->getConnectionName();
    }

    private function canonicalJson(array $value): string
    {
        $this->sortRecursively($value);

        return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function sortRecursively(array &$value): void
    {
        foreach ($value as &$child) {
            if (is_array($child)) {
                $this->sortRecursively($child);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }
    }

    private function redactBinaryPayloads(mixed $value, ?string $key = null): mixed
    {
        if ($key === 'image_data_uri' && is_string($value)) {
            return '[image data omitted; '.strlen($value).' bytes]';
        }

        if (! is_array($value)) {
            return $value;
        }

        return collect($value)
            ->mapWithKeys(fn ($child, $childKey): array => [
                $childKey => $this->redactBinaryPayloads($child, is_string($childKey) ? $childKey : null),
            ])
            ->all();
    }
}
