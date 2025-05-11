<script setup lang="ts">
import Button from 'primevue/button';
import { Ref, ref, inject } from "vue";
import { DynamicDialogInstance } from "primevue/dynamicdialogoptions";
import { MapResource } from "@/Resources/Map.resource";
import { NpcResource } from "@/Resources/Npc.resource";
import axios from "axios";
import { route } from "ziggy-js";
import { useToast } from "primevue";
import {router} from "@inertiajs/vue3";

// Inject dialog reference with proper typing
const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        x: number,
        y: number,
        map: MapResource,
    }
}> | null>('dialogRef');

const toast = useToast();
const filteredHeroNpcs = ref<NpcResource[]>([]);
const selectedHero = ref<NpcResource | string | null>(null);

// Search for hero NPCs
const searchHero = async (query: string) => {
    const { data } = await axios.get<NpcResource[]>(route('npcs.search-hero', {query}));
    console.log('data', data)
    return data;
};

// Filter hero NPCs based on search query
const filterHeroNpcs = async ({ query }: { query: string }) => {
    filteredHeroNpcs.value = await searchHero(query);
};

// Add hero location
const addHeroLocation = () => {
    if (!selectedHero.value || !dialogRef || typeof selectedHero.value == 'string') return;

    // Add a new location to the existing NPC
    router.post(route('npcs.locations.store', { npc: selectedHero.value.id }), {
        map_id: dialogRef.value.data.map.id,
        x: dialogRef.value.data.x,
        y: dialogRef.value.data.y,
    }, {
        onSuccess() {
            toast.add({
                severity: 'success',
                summary: 'Udało się',
                detail: 'Dodano nowe miejsce wystąpienia herosa',
                life: 3000
            });
            // Close the dialog on success
            dialogRef.value.close({
                success: true
            });
        },
        onError() {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wystąpił problem podczas dodawania miejsca wystąpienia herosa', life: 3000 });
            console.error('Error adding hero location:', error);
        }
    });
};
</script>

<template>
    <div class="flex flex-col gap-2">
        <AutoComplete
            class="w-full p-0"
            v-model="selectedHero"
            placeholder="Wyszukaj herosa"
            :suggestions="filteredHeroNpcs"
            @complete="filterHeroNpcs"
            fluid
        >
            <template #option="slotProps">
                <div class="name-item flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <img
                            class="w-12 object-cover"
                            :src="slotProps.option.src"
                            alt="Option Image"
                            v-tip.npc="slotProps.option"
                        />
                        <div class="text-center">
                            <span class="font-semibold text-gray-800">
                                [{{ slotProps.option.id }}] {{ slotProps.option.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </AutoComplete>

        <div class="font-bold my-6" v-if="dialogRef">
            <span>{{dialogRef.data.map.name}}</span>
            <span class="ml-2">({{dialogRef.data.x}},{{dialogRef.data.y}})</span>
        </div>

        <div v-if="selectedHero && typeof selectedHero != 'string'">
            <img
                class="w-12 object-cover"
                :src="selectedHero.src"
                alt="Option Image"
            />
            <div class="font-bold">{{ selectedHero.name }}</div>
            <div class="text-sm text-gray-600">
                Istniejące lokalizacje: {{ selectedHero.locations.length }}
            </div>
        </div>

        <div class="gap-1">
            <Button fluid @click="addHeroLocation">Dodaj</Button>
        </div>
    </div>
</template>

<style scoped>
</style>
