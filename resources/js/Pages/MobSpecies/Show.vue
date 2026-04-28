<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import {route} from "ziggy-js";
import {Link, router} from "@inertiajs/vue3";
import {ref} from "vue";
import axios from "axios";

type BaseNpcLite = {
    id: number
    name: string
    lvl: number
    profession: string
    src: string
}

const props = defineProps<{
    mobSpecies: {
        id: number
        name: string
        base_npcs: BaseNpcLite[]
    }
}>();

const addVisible = ref(false);
const filteredBaseNpcs = ref<any[]>([]);
const selectedBaseNpc = ref<any | null>(null);
const addLoading = ref(false);

const searchBaseNpcs = async ({ query }: { query: string }) => {
    const { data } = await axios.get(route('base-npcs.search', { query }));
    filteredBaseNpcs.value = data;
};

const attachNpc = () => {
    if (!selectedBaseNpc.value) return;
    addLoading.value = true;
    router.post(route('mob-species.base-npcs.attach', { mobSpecies: props.mobSpecies.id }), {
        base_npc_id: selectedBaseNpc.value.id
    }, {
        preserveScroll: true,
        onSuccess: () => {
            addVisible.value = false;
            selectedBaseNpc.value = null;
        },
        onFinish: () => {
            addLoading.value = false;
        }
    });
};

const detachNpc = (baseNpcId: number) => {
    router.delete(route('mob-species.base-npcs.detach', {
        mobSpecies: props.mobSpecies.id,
        baseNpc: baseNpcId
    }), { preserveScroll: true });
};
</script>

<template>
    <AppLayout>
        <div class="card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-surface-500">Gatunek</div>
                    <h2 class="m-0">{{ mobSpecies.name }}</h2>
                </div>
                <div class="flex gap-2">
                    <Button label="Dodaj Base NPC" icon="pi pi-plus" @click="addVisible = true" />
                    <Link :href="route('mob-species.index')">
                        <Button label="Powrót" severity="secondary" />
                    </Link>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 class="mt-0 mb-3">Powiązane Base NPC</h3>
            <div v-if="mobSpecies.base_npcs.length === 0" class="text-surface-500">Brak powiązanych mobów.</div>
            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                <div v-for="npc in mobSpecies.base_npcs" :key="npc.id" class="p-3 border rounded-lg bg-surface-0 flex items-center gap-3">
                    <img :src="npc.src" :alt="npc.name" class="w-14 h-14 object-cover rounded" />
                    <div class="grow min-w-0">
                        <div class="font-semibold truncate">#{{ npc.id }} {{ npc.name }}</div>
                        <div class="text-sm text-surface-500">{{ npc.lvl }}{{ npc.profession }}</div>
                    </div>
                    <Button icon="pi pi-times" severity="danger" text @click="detachNpc(npc.id)" />
                </div>
            </div>
        </div>

        <Dialog v-model:visible="addVisible" modal header="Dodaj Base NPC" :style="{ width: '36rem' }">
            <AutoComplete
                class="w-full"
                v-model="selectedBaseNpc"
                :suggestions="filteredBaseNpcs"
                @complete="searchBaseNpcs"
                :option-label="(baseNpc: any|null) => baseNpc?.name || ''"
                placeholder="Wyszukaj Base NPC"
                fluid
            >
                <template #option="slotProps">
                    <div class="flex items-center gap-3">
                        <img :src="slotProps.option.src" class="w-10 h-10 object-cover rounded" />
                        <span>[{{ slotProps.option.id }}] {{ slotProps.option.name }}</span>
                    </div>
                </template>
            </AutoComplete>
            <div class="flex justify-end gap-2 mt-4">
                <Button label="Anuluj" severity="secondary" @click="addVisible = false" />
                <Button :loading="addLoading" :disabled="!selectedBaseNpc" label="Dodaj" @click="attachNpc" />
            </div>
        </Dialog>
    </AppLayout>
</template>

