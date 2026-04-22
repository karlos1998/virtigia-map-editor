<script setup lang="ts">
import AutoComplete from "primevue/autocomplete";
import axios from "axios";
import { route } from "ziggy-js";
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import { BaseItemResource } from "@/Resources/BaseItem.resource";

type ValueMode = "id" | "object";
type ItemSearchModelValue = number | number[] | BaseItemResource | BaseItemResource[] | null;

interface PaginatedItemSearchResponse {
    data: BaseItemResource[];
    meta: {
        has_more: boolean;
    };
}

const props = withDefaults(
    defineProps<{
        modelValue: ItemSearchModelValue;
        multiple?: boolean;
        valueMode?: ValueMode;
        category?: string | null;
        placeholder?: string;
        dropdown?: boolean;
    }>(),
    {
        multiple: false,
        valueMode: "object",
        category: null,
        placeholder: "Szukaj przedmiotu",
        dropdown: true,
    }
);

const emit = defineEmits<{
    (event: "update:modelValue", value: ItemSearchModelValue): void;
    (event: "resolved-items", value: BaseItemResource[]): void;
}>();

const autoCompleteRef = ref<InstanceType<typeof AutoComplete> | null>(null);
const selectedSingle = ref<BaseItemResource | null>(null);
const selectedMultiple = ref<BaseItemResource[]>([]);
const suggestions = ref<BaseItemResource[]>([]);
const currentQuery = ref("");
const currentOffset = ref(0);
const hasMore = ref(false);
const loading = ref(false);

let requestId = 0;
let isSyncingFromModel = false;
let scrollContainer: HTMLElement | null = null;

const currentSelectedItems = computed<BaseItemResource[]>(() => {
    if (props.multiple) {
        return selectedMultiple.value;
    }

    return selectedSingle.value ? [selectedSingle.value] : [];
});

const extractIdsFromValue = (value: ItemSearchModelValue): number[] => {
    if (value === null) {
        return [];
    }

    if (Array.isArray(value)) {
        return value
            .map((item) => (typeof item === "number" ? item : item.id))
            .filter((itemId) => Number.isInteger(itemId));
    }

    if (typeof value === "number") {
        return [value];
    }

    return [value.id];
};

const searchByIds = async (ids: number[]): Promise<BaseItemResource[]> => {
    if (ids.length === 0) {
        return [];
    }

    const { data } = await axios.get<BaseItemResource[]>(route("base-items.search"), {
        params: {
            query: "",
            ids,
            category: props.category,
        },
    });

    return data;
};

const syncSelectionFromModel = async (): Promise<void> => {
    isSyncingFromModel = true;

    try {
        if (props.valueMode === "object") {
            if (props.multiple) {
                selectedMultiple.value = Array.isArray(props.modelValue)
                    ? (props.modelValue as BaseItemResource[])
                    : [];
            } else {
                selectedSingle.value =
                    props.modelValue && !Array.isArray(props.modelValue) && typeof props.modelValue !== "number"
                        ? (props.modelValue as BaseItemResource)
                        : null;
            }

            emit("resolved-items", currentSelectedItems.value);

            return;
        }

        const ids = extractIdsFromValue(props.modelValue);
        const resolvedItems = await searchByIds(ids);

        if (props.multiple) {
            selectedMultiple.value = ids
                .map((id) => resolvedItems.find((item) => item.id === id))
                .filter((item): item is BaseItemResource => item !== undefined);
        } else {
            selectedSingle.value = resolvedItems[0] ?? null;
        }

        emit("resolved-items", currentSelectedItems.value);
    } finally {
        isSyncingFromModel = false;
    }
};

const emitModelValue = (): void => {
    if (isSyncingFromModel) {
        return;
    }

    if (props.valueMode === "object") {
        if (props.multiple) {
            emit("update:modelValue", [...selectedMultiple.value]);
        } else {
            emit("update:modelValue", selectedSingle.value);
        }
    } else if (props.multiple) {
        emit(
            "update:modelValue",
            selectedMultiple.value.map((item) => item.id)
        );
    } else {
        emit("update:modelValue", selectedSingle.value ? selectedSingle.value.id : null);
    }

    emit("resolved-items", currentSelectedItems.value);
};

const fetchSuggestions = async (append: boolean): Promise<void> => {
    const currentRequestId = ++requestId;

    loading.value = true;

    try {
        const { data } = await axios.get<PaginatedItemSearchResponse>(route("base-items.search"), {
            params: {
                query: currentQuery.value,
                category: props.category,
                paginated: true,
                offset: append ? currentOffset.value : 0,
                limit: 30,
            },
        });

        if (currentRequestId !== requestId) {
            return;
        }

        const nextSuggestions = data.data ?? [];

        if (append) {
            suggestions.value = [...suggestions.value, ...nextSuggestions].filter(
                (item, index, array) => array.findIndex((candidate) => candidate.id === item.id) === index
            );
        } else {
            suggestions.value = nextSuggestions;
        }

        currentOffset.value = append ? currentOffset.value + nextSuggestions.length : nextSuggestions.length;
        hasMore.value = Boolean(data.meta?.has_more);
    } finally {
        loading.value = false;
    }
};

const loadMore = async (): Promise<void> => {
    if (!hasMore.value || loading.value) {
        return;
    }

    await fetchSuggestions(true);
};

const onComplete = async (event: { query: string }): Promise<void> => {
    currentQuery.value = event.query ?? "";
    currentOffset.value = 0;
    await fetchSuggestions(false);
    attachPanelScrollListener();
};

const onDropdownClick = async (): Promise<void> => {
    if (suggestions.value.length > 0 || loading.value) {
        return;
    }

    await onComplete({ query: currentQuery.value });
};

const getPanelScrollContainer = (): HTMLElement | null => {
    const rootElement = autoCompleteRef.value?.$el as HTMLElement | undefined;
    const inputElement = rootElement?.querySelector("input");
    const panelId = inputElement?.getAttribute("aria-controls");

    if (!panelId) {
        return null;
    }

    const panelElement = document.getElementById(panelId);
    if (!panelElement) {
        return null;
    }

    return (panelElement.querySelector(".p-autocomplete-list-container") as HTMLElement | null) ?? panelElement;
};

const handleScroll = async (): Promise<void> => {
    if (!scrollContainer) {
        return;
    }

    const remainingSpace = scrollContainer.scrollHeight - (scrollContainer.scrollTop + scrollContainer.clientHeight);

    if (remainingSpace > 40) {
        return;
    }

    await loadMore();
};

const detachPanelScrollListener = (): void => {
    if (scrollContainer) {
        scrollContainer.removeEventListener("scroll", handleScroll);
    }

    scrollContainer = null;
};

const attachPanelScrollListener = (): void => {
    nextTick(() => {
        const nextScrollContainer = getPanelScrollContainer();

        if (!nextScrollContainer || nextScrollContainer === scrollContainer) {
            return;
        }

        detachPanelScrollListener();
        scrollContainer = nextScrollContainer;
        scrollContainer.addEventListener("scroll", handleScroll);
    });
};

watch(
    () => props.modelValue,
    async () => {
        await syncSelectionFromModel();
    },
    { immediate: true, deep: true }
);

watch(selectedSingle, emitModelValue);
watch(selectedMultiple, emitModelValue, { deep: true });

onBeforeUnmount(() => {
    detachPanelScrollListener();
});
</script>

<template>
    <AutoComplete
        ref="autoCompleteRef"
        v-if="multiple"
        v-model="selectedMultiple"
        multiple
        forceSelection
        :suggestions="suggestions"
        :placeholder="placeholder"
        :dropdown="dropdown"
        :delay="300"
        class="w-full"
        @complete="onComplete"
        @show="attachPanelScrollListener"
        @hide="detachPanelScrollListener"
        @dropdown-click="onDropdownClick"
    >
        <template #option="{ option }">
            <div class="name-item flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img
                        class="h-10 w-10 object-cover"
                        :src="option.src"
                        :alt="option.name"
                        v-tip.item.top.show-id="option"
                    />
                    <div class="text-center">
                        <span class="font-semibold text-gray-800">
                            [{{ option.id }}] {{ option.name }} ({{ option.in_use ? "W użyciu" : "Nieużywany" }})
                        </span>
                    </div>
                </div>
            </div>
        </template>
    </AutoComplete>

    <AutoComplete
        ref="autoCompleteRef"
        v-else
        v-model="selectedSingle"
        forceSelection
        :suggestions="suggestions"
        :placeholder="placeholder"
        :dropdown="dropdown"
        :delay="300"
        class="w-full"
        @complete="onComplete"
        @show="attachPanelScrollListener"
        @hide="detachPanelScrollListener"
        @dropdown-click="onDropdownClick"
    >
        <template #option="{ option }">
            <div class="name-item flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img
                        class="h-10 w-10 object-cover"
                        :src="option.src"
                        :alt="option.name"
                        v-tip.item.top.show-id="option"
                    />
                    <div class="text-center">
                        <span class="font-semibold text-gray-800">
                            [{{ option.id }}] {{ option.name }} ({{ option.in_use ? "W użyciu" : "Nieużywany" }})
                        </span>
                    </div>
                </div>
            </div>
        </template>
        <template #value="{ value }">
            <div v-if="value" class="name-item flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img
                        class="h-10 w-10 object-cover"
                        :src="value.src"
                        :alt="value.name"
                        v-tip.item.top.show-id="value"
                    />
                    <div class="text-center">
                        <span class="font-semibold text-gray-800">
                            [{{ value.id }}] {{ value.name }}
                        </span>
                    </div>
                </div>
            </div>
        </template>
    </AutoComplete>
</template>
