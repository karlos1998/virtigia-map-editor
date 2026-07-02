<script setup lang="ts">
import axios from "axios";
import { route } from "ziggy-js";
import { computed, nextTick, ref, watch } from "vue";
import BaseItemSearchSelect from "@/Components/BaseItemSearchSelect.vue";
import type { BaseItemResource } from "@/Resources/BaseItem.resource";
import { extractBaseItemId, extractBaseItemResource, resolveRewardBaseItemId } from "./lootChestEditorPayload";

type LootChestMode = "random" | "guaranteed";

type LootChestReward = {
    uid: string;
    baseItemId: number | null;
    chancePercent: number;
    minQuantity: number;
    maxQuantity: number;
    resolvedItem?: BaseItemResource | null;
};

type LootChestConfig = {
    mode: LootChestMode;
    minRewards: number;
    maxRewards: number;
    items: LootChestReward[];
};

type StoredLootChestReward = {
    baseItemId?: number | string | BaseItemResource | null;
    chancePercent?: number | string | null;
    minQuantity?: number | string | null;
    maxQuantity?: number | string | null;
};

type StoredLootChestConfig = {
    mode?: LootChestMode | string | null;
    minRewards?: number | string | null;
    maxRewards?: number | string | null;
    items?: StoredLootChestReward[] | null;
};

const attributes = defineModel<Record<string, any>>("attributes", { required: true });

const modeOptions = [
    { label: "Losowy", value: "random" },
    { label: "Pewny", value: "guaranteed" },
];

const createUid = (): string => `loot-${Date.now()}-${Math.random().toString(16).slice(2)}`;

const toInteger = (value: unknown, fallback: number): number => {
    const parsed = Number(value);

    if (!Number.isFinite(parsed)) {
        return fallback;
    }

    return Math.round(parsed);
};

const clamp = (value: number, min: number, max: number): number => Math.min(max, Math.max(min, value));

const normalizeReward = (reward?: StoredLootChestReward | null): LootChestReward => {
    const minQuantity = clamp(toInteger(reward?.minQuantity, 1), 1, 999);
    const maxQuantity = clamp(toInteger(reward?.maxQuantity, minQuantity), minQuantity, 999);

    return {
        uid: createUid(),
        baseItemId: extractBaseItemId(reward?.baseItemId),
        chancePercent: clamp(Number(reward?.chancePercent ?? 100), 0, 100),
        minQuantity,
        maxQuantity,
        resolvedItem: null,
    };
};

const normalizeConfig = (stored?: StoredLootChestConfig | null): LootChestConfig => {
    const mode = stored?.mode === "guaranteed" ? "guaranteed" : "random";
    const items = Array.isArray(stored?.items) ? stored.items.map(normalizeReward) : [];
    const minRewards = clamp(toInteger(stored?.minRewards, mode === "guaranteed" ? items.length : 1), 0, 999);
    const maxRewards = clamp(toInteger(stored?.maxRewards, Math.max(minRewards, Math.min(items.length || 1, 1))), minRewards, 999);

    return {
        mode,
        minRewards,
        maxRewards,
        items,
    };
};

const serializeReward = (reward: LootChestReward, mode: LootChestMode): StoredLootChestReward | null => {
    const baseItemId = resolveRewardBaseItemId(reward);

    if (baseItemId === null) {
        return null;
    }

    const minQuantity = clamp(toInteger(reward.minQuantity, 1), 1, 999);

    return {
        baseItemId,
        chancePercent: mode === "guaranteed" ? 100 : clamp(Number(reward.chancePercent), 0, 100),
        minQuantity,
        maxQuantity: clamp(toInteger(reward.maxQuantity, minQuantity), minQuantity, 999),
    };
};

const serializeConfig = (current: LootChestConfig): StoredLootChestConfig => {
    const items = current.items
        .map((reward) => serializeReward(reward, current.mode))
        .filter((reward): reward is StoredLootChestReward => reward !== null);

    return {
        mode: current.mode,
        minRewards: current.mode === "guaranteed" ? items.length : current.minRewards,
        maxRewards: current.mode === "guaranteed" ? items.length : Math.max(current.minRewards, current.maxRewards),
        items,
    };
};

const config = ref<LootChestConfig>(normalizeConfig(attributes.value?.lootChest));
let skipAttributeSync = false;

const baseItemIds = computed<number[]>(() => {
    return Array.from(
        new Set(
            config.value.items
                .map((reward) => extractBaseItemId(reward.baseItemId))
                .filter((baseItemId): baseItemId is number => baseItemId !== null)
        )
    );
});

const addReward = (): void => {
    config.value.items.push(normalizeReward());
};

const removeReward = (uid: string): void => {
    config.value.items = config.value.items.filter((reward) => reward.uid !== uid);
};

const duplicateReward = (reward: LootChestReward): void => {
    config.value.items.push({
        ...reward,
        uid: createUid(),
        resolvedItem: reward.resolvedItem ?? null,
    });
};

const fixRewardRange = (reward: LootChestReward): void => {
    const nextMinQuantity = clamp(toInteger(reward.minQuantity, 1), 1, 999);
    const nextMaxQuantity = clamp(toInteger(reward.maxQuantity, nextMinQuantity), nextMinQuantity, 999);
    const nextChancePercent = clamp(Number(reward.chancePercent), 0, 100);

    if (reward.minQuantity !== nextMinQuantity) {
        reward.minQuantity = nextMinQuantity;
    }
    if (reward.maxQuantity !== nextMaxQuantity) {
        reward.maxQuantity = nextMaxQuantity;
    }
    if (reward.chancePercent !== nextChancePercent) {
        reward.chancePercent = nextChancePercent;
    }
};

const resolveRewardItems = async (): Promise<void> => {
    const missingIds = baseItemIds.value.filter((baseItemId) => {
        return config.value.items.some((reward) => {
            return extractBaseItemId(reward.baseItemId) === baseItemId && reward.resolvedItem?.id !== baseItemId;
        });
    });

    if (missingIds.length === 0) {
        return;
    }

    const { data } = await axios.get<BaseItemResource[]>(route("base-items.search"), {
        params: {
            query: "",
            ids: missingIds,
        },
    });

    const itemsById = new Map(data.map((item) => [item.id, item]));

    config.value.items.forEach((reward) => {
        const baseItemId = extractBaseItemId(reward.baseItemId);

        if (baseItemId === null) {
            reward.resolvedItem = null;
            return;
        }

        reward.baseItemId = baseItemId;
        reward.resolvedItem = itemsById.get(baseItemId) ?? reward.resolvedItem ?? null;
    });
};

const handleBaseItemChange = (reward: LootChestReward, value: unknown): void => {
    const baseItem = extractBaseItemResource(value);

    if (baseItem) {
        reward.baseItemId = baseItem.id;
        reward.resolvedItem = baseItem;
        return;
    }

    const baseItemId = extractBaseItemId(value);
    reward.baseItemId = baseItemId;

    if (baseItemId === null) {
        reward.resolvedItem = null;
    } else if (reward.resolvedItem?.id !== baseItemId) {
        reward.resolvedItem = null;
    }
};

const handleResolvedItems = (reward: LootChestReward, items: unknown): void => {
    const item = extractBaseItemResource(items);

    if (item) {
        reward.baseItemId = item.id;
        reward.resolvedItem = item;
        return;
    }

    reward.resolvedItem = extractBaseItemId(reward.baseItemId) === null ? null : reward.resolvedItem ?? null;
};

const syncAttributes = (): void => {
    skipAttributeSync = true;
    attributes.value = {
        ...(attributes.value ?? {}),
        lootChest: serializeConfig(config.value),
    };

    nextTick(() => {
        skipAttributeSync = false;
    });
};

watch(
    () => attributes.value?.lootChest,
    (lootChest) => {
        if (skipAttributeSync) {
            return;
        }

        config.value = normalizeConfig(lootChest);
    },
    { deep: true }
);

watch(
    config,
    () => {
        const nextMinRewards = clamp(toInteger(config.value.minRewards, 1), 0, 999);
        const nextMaxRewards = clamp(toInteger(config.value.maxRewards, nextMinRewards), nextMinRewards, 999);

        if (config.value.minRewards !== nextMinRewards) {
            config.value.minRewards = nextMinRewards;
        }
        if (config.value.maxRewards !== nextMaxRewards) {
            config.value.maxRewards = nextMaxRewards;
        }

        config.value.items.forEach(fixRewardRange);
        syncAttributes();
    },
    { deep: true }
);

watch(
    baseItemIds,
    async () => {
        await resolveRewardItems();
    },
    { immediate: true }
);
</script>

<template>
    <section class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h4 class="text-lg font-semibold">Zawartość kuferka</h4>
            </div>
            <Button label="Dodaj przedmiot" icon="pi pi-plus" severity="success" @click="addReward" />
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-surface-700">Tryb</label>
                <SelectButton
                    v-model="config.mode"
                    :options="modeOptions"
                    option-label="label"
                    option-value="value"
                    :allow-empty="false"
                />
            </div>

            <template v-if="config.mode === 'random'">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-surface-700">Minimalnie różnych przedmiotów</label>
                    <InputNumber v-model="config.minRewards" :min="0" :max="999" show-buttons />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-surface-700">Maksymalnie różnych przedmiotów</label>
                    <InputNumber v-model="config.maxRewards" :min="config.minRewards" :max="999" show-buttons />
                </div>
            </template>

            <div v-else class="lg:col-span-2 flex items-end text-sm text-surface-600">
                W trybie pewnym gracz dostaje każdy skonfigurowany wiersz.
            </div>
        </div>

        <div v-if="config.items.length === 0" class="rounded border border-dashed border-surface-300 p-6 text-center text-surface-500">
            Dodaj pierwszy przedmiot do kuferka.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full min-w-[900px] border-collapse text-sm">
                <thead>
                    <tr class="border-b text-left text-surface-600">
                        <th class="w-[42%] py-2 pr-3">Przedmiot</th>
                        <th class="w-[16%] py-2 px-3">Szansa</th>
                        <th class="w-[16%] py-2 px-3">Ilość min</th>
                        <th class="w-[16%] py-2 px-3">Ilość max</th>
                        <th class="w-[10%] py-2 pl-3 text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="reward in config.items" :key="reward.uid" class="border-b align-top">
                        <td class="py-3 pr-3">
                            <BaseItemSearchSelect
                                :model-value="reward.resolvedItem"
                                value-mode="object"
                                placeholder="Wyszukaj przedmiot do kuferka"
                                @update:model-value="(value) => handleBaseItemChange(reward, value)"
                                @resolved-items="(items) => handleResolvedItems(reward, items)"
                            />
                            <div
                                v-if="reward.resolvedItem"
                                class="mt-2 flex items-center gap-2 rounded border border-surface-200 bg-surface-50 px-2 py-1 text-xs text-surface-700"
                            >
                                <img
                                    :src="reward.resolvedItem.src"
                                    :alt="reward.resolvedItem.name"
                                    class="h-8 w-8 object-cover"
                                    v-tip.item.top.show-id="reward.resolvedItem"
                                />
                                <span class="font-medium">[{{ reward.resolvedItem.id }}] {{ reward.resolvedItem.name }}</span>
                            </div>
                            <div
                                v-else-if="reward.baseItemId !== null"
                                class="mt-2 text-xs text-surface-500"
                            >
                                Wybrany przedmiot: #{{ reward.baseItemId }}
                            </div>
                        </td>
                        <td class="py-3 px-3">
                            <InputNumber
                                v-model="reward.chancePercent"
                                :min="0"
                                :max="100"
                                suffix="%"
                                show-buttons
                                :disabled="config.mode === 'guaranteed'"
                            />
                        </td>
                        <td class="py-3 px-3">
                            <InputNumber v-model="reward.minQuantity" :min="1" :max="999" show-buttons />
                        </td>
                        <td class="py-3 px-3">
                            <InputNumber v-model="reward.maxQuantity" :min="reward.minQuantity" :max="999" show-buttons />
                        </td>
                        <td class="py-3 pl-3">
                            <div class="flex justify-end gap-2">
                                <Button
                                    icon="pi pi-copy"
                                    severity="secondary"
                                    text
                                    rounded
                                    aria-label="Duplikuj"
                                    @click="duplicateReward(reward)"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    severity="danger"
                                    text
                                    rounded
                                    aria-label="Usuń"
                                    @click="removeReward(reward.uid)"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Message v-if="config.mode === 'random' && config.maxRewards > config.items.length" severity="warn">
            Maksymalna liczba losowanych pozycji jest większa niż liczba wpisów w kuferku.
        </Message>
    </section>
</template>
