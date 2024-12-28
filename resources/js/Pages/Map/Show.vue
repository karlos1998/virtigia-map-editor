<script setup lang="ts">
import AppLayout from '@/layout/AppLayout.vue';
import { MapResource } from '@/Resources/Map.resource';
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { NpcResource } from '@/Resources/Npc.resource';
import { DoorResource } from '@/Resources/Door.resource';
import { useConfirm } from 'primevue';

defineProps<{
    map: MapResource;
    npcs: NpcResource[];
    doors: DoorResource[];
}>();

const scale = ref(1);

const zoomIn = () => {
    scale.value = Math.min(scale.value + 0.1, 2);
};

const zoomOut = () => {
    scale.value = Math.max(scale.value - 0.1, 0.5);
};


const npcWidths = ref<Record<string, number>>({});
const npcHeights = ref<Record<string, number>>({});
const adjustNpcOffset = (id: string, element: HTMLImageElement) => {
    npcWidths.value[id] = element.width;
    npcHeights.value[id] = element.height;
};

const confirm = useConfirm();

const showNpcConfirmDialog = (event: MouseEvent, npc: NpcResource) => {
    confirm.require({
        target: event.currentTarget as HTMLElement,
        group: 'npc',
        message: 'Please confirm to proceed moving forward.',
        icon: 'pi pi-exclamation-circle',
        npc,
        rejectProps: {
            icon: 'pi pi-times',
            label: 'Cancel',
            outlined: true
        },
        acceptProps: {
            icon: 'pi pi-check',
            label: 'Confirm'
        },
        accept: () => {
            // toast.add({severity:'info', summary:'Confirmed', detail:'You have accepted', life: 3000});
        },
        reject: () => {
            // toast.add({severity:'error', summary:'Rejected', detail:'You have rejected', life: 3000});
        }
    });
};
</script>

<template>
    <AppLayout>

        <ConfirmPopup group="npc">
            <template #container="{ message, acceptCallback, rejectCallback }">
                <div
                    class="flex flex-col items-center w-full gap-4 border-b border-surface-200 dark:border-surface-700 p-4 mb-4 pb-0">
                    <!--                    <i :class="slotProps.message.icon" class="text-6xl text-primary-500"></i>-->
                    <p>{{ message.npc.name }}</p>
                </div>

                <div class="flex justify-center items-center gap-2 mt-4">
                    <!--                    <Button label="Save" @click="acceptCallback" size="small"></Button>-->
                    <Button label="Zamknij" severity="contrast" @click="rejectCallback" size="small" />
                    <Button label="Kopiuj" severity="help" @click="rejectCallback" size="small" />
                    <Button label="Pokaż szczegóły" @click="rejectCallback" size="small" />
                    <Button label="Usuń" @click="rejectCallback" severity="danger" size="small" />
                </div>

            </template>

        </ConfirmPopup>

        <div class="card">
            <Link :href="route('maps.index')">
                <Button label="Powrót" severity="info" />
            </Link>
        </div>

        <div class="card">
            <div class="flex gap-2">
                <Button label="+" icon="pi pi-search-plus" @click="zoomIn" />
                <Button label="-" icon="pi pi-search-minus" @click="zoomOut" />
            </div>
        </div>

        <div class="card overflow-auto m-2">
            <div
                class="map-container relative"
                :style="{
                    backgroundImage: `url(https://virtigia-assets.letscode.it/img/locations/${map.src})`,
                    width: `${map.x * 32 * scale}px`,
                    height: `${map.y * 32 * scale}px`,
                    transformOrigin: 'top left',
                }"
            >
                <div
                    v-for="npc in npcs"
                    :key="npc.id"
                    class="absolute npc"
                    v-tooltip="npc.name"
                    @click="showNpcConfirmDialog($event, npc)"
                    :style="{
                        width: `${(npcWidths[npc.id] ?? 32) * scale}px`,
                        height: `${(npcHeights[npc.id] ?? 32) * scale}px`,
                        top: `${(npc.y * 32 - ((npcHeights[npc.id] ?? 32) - 32)) * scale}px`,
                        left: `${npc.x * 32 * scale}px`,
                    }"
                >
                    <!-- Czerwony kwadrat u podstawy -->
                    <div
                        class="npc-footer"
                        :style="{
                            width: `${32 * scale}px`,
                            height: `${32 * scale}px`,
                            bottom: 0,
                        }"
                    />
                    <!-- Obrazek NPC -->
                    <img
                        :src="`https://virtigia-assets.letscode.it/img/npc/${npc.src}`"
                        :style="{
                            position: 'relative',
                            width: `${npcWidths[npc.id] * scale}px`,
                            bottom: 0,
                            zIndex: 1,
                            left: (32 - npcWidths[npc.id]) * scale / 2,
                        }"
                        @load="adjustNpcOffset(npc.id, $event.target)"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.map-container {
    background-size: contain;
    background-repeat: no-repeat;
    background-position: top left;
    position: relative;
}

.npc {
    position: absolute;
}

.npc-footer {
    position: absolute;
    background-color: red;
}
</style>
