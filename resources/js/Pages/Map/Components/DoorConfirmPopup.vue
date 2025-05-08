<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { DoorResource } from '@/Resources/Door.resource';
import { useDialog } from 'primevue/usedialog';
import DoorLevelModal from "@/Pages/Map/Modals/DoorLevelModal.vue";
import DoorRequiredItemModal from "@/Pages/Map/Modals/DoorRequiredItemModal.vue";

const primeDialog = useDialog();

const emit = defineEmits<{
    (e: 'moveDoor', door: DoorResource): void;
}>();

const removeDoor = (door: DoorResource) => {
    router.delete(route('doors.destroy', {
        door
    }));
};

const openLevelModal = (door: DoorResource) => {
    primeDialog.open(DoorLevelModal, {
        props: {
            header: 'Ustaw poziom przejścia',
            modal: true,
            breakpoints: {
                '960px': '75vw',
                '640px': '90vw'
            },
            style: 'max-width:500px'
        },
        data: {
            door
        },
        onClose() {
            // Refresh the page to get updated door data
            router.reload({ only: ['doors'] });
        }
    });
};

const openRequiredItemModal = (door: DoorResource) => {
    primeDialog.open(DoorRequiredItemModal, {
        props: {
            header: 'Ustaw wymagany przedmiot',
            modal: true,
            breakpoints: {
                '960px': '75vw',
                '640px': '90vw'
            },
            style: 'max-width:500px'
        },
        data: {
            door
        },
        onClose() {
            // Refresh the page to get updated door data
            router.reload({ only: ['doors'] });
        }
    });
};
</script>

<template>
    <ConfirmPopup group="door">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="flex flex-col items-center w-full gap-4 border-b border-surface-200 dark:border-surface-700 p-4 mb-4 pb-0">
                <p>{{ message.door.name }}</p>
            </div>

            <div class="flex justify-center items-center gap-2 mt-4">
                <Button label="Zamknij" severity="contrast" @click="rejectCallback" size="small" />

                <Link :href="route('maps.show', message.door.go_map_id)">
                    <Button label="Przejdź" @click="rejectCallback" size="small" />
                </Link>

                <Button label="Usuń" @click="removeDoor(message.door); rejectCallback()" severity="danger" size="small" />

                <Button label="Przesuń" @click="emit('moveDoor', message.door); rejectCallback()" severity="info" size="small" />

                <Button label="Poziom" @click="openLevelModal(message.door); rejectCallback()" severity="success" size="small" />

                <Button label="Przedmiot" @click="openRequiredItemModal(message.door); rejectCallback()" severity="help" size="small" />
            </div>
        </template>
    </ConfirmPopup>
</template>
