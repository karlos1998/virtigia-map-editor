<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { NpcWithLocationResource } from '@/Resources/Npc.resource';

const emit = defineEmits<{
    (e: 'moveNpc', npc: NpcWithLocationResource): void;
    (e: 'addToGroup', npc: NpcWithLocationResource): void;
}>();

const removeNpc = (npc: NpcWithLocationResource) => {
    router.delete(route('npcs.locations.destroy', {
        npc: npc.id,
        npcLocation: npc.location.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            // confirm.close() is handled by the parent component
        }
    });
};

const removeFromGroup = (npc: NpcWithLocationResource) => {
    router.delete(route('npcs.group.detach', {
        npc: npc.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            // ...
        }
    });
};
</script>

<template>
    <ConfirmPopup group="npc">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="flex flex-col items-center w-full gap-4 border-b border-surface-200 dark:border-surface-700 p-4 mb-4 pb-0">
                <p>{{ message.npc.name }}</p>
            </div>

            <div class="flex justify-center items-center gap-2 mt-4">
                <Button label="Zamknij" severity="contrast" @click="rejectCallback" size="small" />

                <Button label="Wyklucz z grupy" severity="help" @click="removeFromGroup(message.npc); rejectCallback()" size="small" />

                <Button label="Dodaj do grupy" severity="success" @click="emit('addToGroup', message.npc); rejectCallback()" size="small" />

                <Link :href="route('npcs.show', message.npc.id)">
                    <Button label="Pokaż szczegóły" @click="rejectCallback" size="small" />
                </Link>

                <Button label="Usuń" @click="removeNpc(message.npc)" severity="danger" size="small" />

                <Button label="Przenieś" @click="emit('moveNpc', message.npc); rejectCallback()" severity="warn" size="small" />
            </div>
        </template>
    </ConfirmPopup>
</template>
