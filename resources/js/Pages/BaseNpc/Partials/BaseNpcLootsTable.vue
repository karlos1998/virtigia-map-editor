<script setup lang="ts">

import {BaseNpcWithLoots} from "../../../Resources/BaseNpc.resource";
import {router} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import AddShopItemDialog from "../../Shop/Components/AddShopItemDialog.vue";
import AddBaseNpcLootsDialog from "../Components/AddBaseNpcLootsDialog.vue";
import {useDialog} from "primevue/usedialog";
import {useToast} from "primevue";
import {BaseItemResource} from "../../../Resources/BaseItem.resource";
import {computed, ref} from 'vue';

const { baseNpc } = defineProps<{
    baseNpc: BaseNpcWithLoots
}>()

const primeDialog = useDialog();
const toast = useToast();
const guaranteed = ref(baseNpc.guaranteed_loot ?? false);

// Grupowanie przedmiotów według rarity
const groupedLoots = computed(() => {
    const groups: Record<string, BaseItemResource[]> = {
        common: [],
        unique: [],
        heroic: [],
        legendary: [],
        upgraded: [],
        artefact: []
    };

    baseNpc.loots.forEach(item => {
        const rarity = item.rarity || 'common'; // fallback na common jeśli rarity nie istnieje
        if (groups[rarity]) {
            groups[rarity].push(item);
        }
    });

    return groups;
});

// Lista rarity w odpowiedniej kolejności
const rarityOrder = ['common', 'unique', 'heroic', 'legendary', 'upgraded', 'artefact'];

const showAttachItemModal = () => {
    primeDialog.open(AddShopItemDialog, {
        props: {
            header: 'Dodaj przedmiot do zdobycia',
            modal: true,
        },
        onClose(options) {

            if (options?.data?.item) {
                const baseItemId = options.data.item.id;
                router.post(route('base-npcs.loots.attach', {
                    baseNpc: baseNpc.id
                }), {
                    baseItemId,
                }, {
                    onError: (errors) =>  {
                        toast.add({
                            severity: 'error',
                            summary: 'Wystąpił błąd',
                            detail: Object.values(errors)[0],
                            life: 5000,
                        })
                    }
                })
            }
        }
    })
}

const showAttachLootsFromBaseNpcModal = () => {
    primeDialog.open(AddBaseNpcLootsDialog, {
        props: {
            header: 'Dodaj looty z innego potwora',
            modal: true,
        },
        onClose(options) {
            if (options?.data?.baseNpc) {
                const sourceBaseNpcId = options.data.baseNpc.id;
                router.post(route('base-npcs.loots.attach-from-base-npc', {
                    baseNpc: baseNpc.id
                }), {
                    sourceBaseNpcId,
                }, {
                    onError: (errors) =>  {
                        toast.add({
                            severity: 'error',
                            summary: 'Wystąpił błąd',
                            detail: Object.values(errors)[0],
                            life: 5000,
                        })
                    }
                })
            }
        }
    })
}

const detachItem = (item: BaseItemResource) => {
    router.delete(route('base-npcs.loots.detach', {
        baseNpc: baseNpc.id,
        loot: item.id,
    }), {
        preserveScroll: true,
    });
}

const toggleGuaranteed = () => {
    router.patch(route('base-npcs.guaranteed.toggle', {baseNpc: baseNpc.id}), {
        guaranteed: guaranteed.value,
    }, {
        preserveState: true,
        onSuccess: () => {
            toast.add({severity: 'success', summary: 'Zapisano', detail: 'Ustawienie zapisane', life: 3000});
        },
        onError: (errors) => {
            toast.add({severity: 'error', summary: 'Błąd', detail: Object.values(errors)[0], life: 5000});
        }
    });
}

// Funkcja zwracająca polską nazwę rarity
const getRarityDisplayName = (rarity: string): string => {
    const names: Record<string, string> = {
        common: 'Zwykłe',
        unique: 'Unikatowe',
        heroic: 'Heroiczne',
        legendary: 'Legendarne',
        upgraded: 'Ulepszone',
        artefact: 'Artefakty'
    };
    return names[rarity] || rarity;
}

// Funkcja zwracająca kolor dla rarity
const getRarityColor = (rarity: string): string => {
    const colors: Record<string, string> = {
        common: 'text-gray-600',
        unique: 'text-blue-600',
        heroic: 'text-purple-600',
        legendary: 'text-yellow-600',
        upgraded: 'text-green-600',
        artefact: 'text-red-600'
    };
    return colors[rarity] || 'text-gray-600';
}
</script>
<template>
    <div class="flex gap-2 mb-2 items-center">
        <Button label="Dodaj przedmiot" @click="showAttachItemModal" />
        <Button label="Dodaj looty z innego potwora" @click="showAttachLootsFromBaseNpcModal" />
        <div class="ml-4 flex items-center gap-2">
            <label class="text-sm text-surface-600">Gwarantowany loot</label>
            <InputSwitch v-model="guaranteed" @change="toggleGuaranteed"/>
        </div>
    </div>

    <!-- Grupowane przedmioty według rarity -->
    <div v-if="baseNpc.loots.length > 0" class="space-y-6">
        <div v-for="rarity in rarityOrder" :key="rarity"
             v-show="groupedLoots[rarity].length > 0">
            <div class="flex items-center gap-3 mb-3">
                <h4 :class="['text-lg font-semibold', getRarityColor(rarity)]">
                    {{ getRarityDisplayName(rarity) }}
                </h4>
                <Tag :value="`${groupedLoots[rarity].length} przedmiotów`" severity="info"/>
            </div>

            <div class="grid gap-4"
                 :class="groupedLoots[rarity].length > 1 ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1'">
                <div v-for="item in groupedLoots[rarity]" :key="item.id"
                     class="border rounded-lg p-4 bg-surface-section flex items-center gap-4">
                    <div class="w-12 h-12 flex-shrink-0 flex items-center justify-center bg-surface-100 rounded">
                        <img
                            class="w-8 h-8 object-contain"
                            :src="item.src"
                            :alt="item.name"
                            v-tip.item.top.show-id="item"
                        />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-color truncate">{{ item.name }}</div>
                        <div class="text-sm text-color-secondary">{{ item.category }}</div>
                    </div>
                    <Button severity="danger" icon="pi pi-times" outlined size="small" @click="detachItem(item)"/>
                </div>
            </div>
        </div>
    </div>

    <!-- Brak lootów -->
    <div v-else class="text-center py-8 text-color-secondary">
        Brak przedmiotów do zdobycia
    </div>

</template>
