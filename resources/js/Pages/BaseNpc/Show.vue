<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";
import {NpcLocationResource} from "@/Resources/NpcLocation.resource";
import BaseNpcLocationsTable from "@/Pages/BaseNpc/Partials/BaseNpcLocationsTable.vue";
import {route} from "ziggy-js";
import ItemHeader from "@/Components/ItemHeader.vue";
import RemoveBaseNpc from "@/Pages/BaseNpc/Partials/RemoveBaseNpc.vue";
import MergeBaseNpc from "@/Pages/BaseNpc/Partials/MergeBaseNpc.vue";
import DetailsCardList from "@/Components/DetailsCardList.vue";
import DetailsCardListItem from "@/Components/DetailsCardListItem.vue";
import {Link} from "@inertiajs/vue3";
import EditBaseNpcDialog from "@/Pages/BaseNpc/Components/EditBaseNpcDialog.vue";
import {ref} from "vue";
import {BaseNpcWithLoots} from "../../Resources/BaseNpc.resource";
import BaseNpcLootsTable from "./Partials/BaseNpcLootsTable.vue";
import EditBaseNpcSrcDialog from "./Components/EditBaseNpcSrcDialog.vue";
import BaseNpcActivityLogsTable from "./Partials/BaseNpcActivityLogsTable.vue";
import ConvertBaseNpcToLayer from "./Partials/ConvertBaseNpcToLayer.vue";
import AddBaseNpcSpecialAttacksDialog from "./Components/AddBaseNpcSpecialAttacksDialog.vue";
import {useConfirm} from "primevue/useconfirm";
import {useToast} from "primevue";
import {router} from "@inertiajs/vue3";
import {useDialog} from "primevue/usedialog";

const {baseNpc, locations, logs} = defineProps<{
    baseNpc: BaseNpcWithLoots
    locations: NpcLocationResource[]
    logs?: any[]
}>()

// Function to format time from seconds to a human-readable format
const formatTimeFromSeconds = (seconds: number): string => {
  if (seconds === null || seconds === 0) return '0s';

  const days = Math.floor(seconds / (24 * 60 * 60));
  seconds %= (24 * 60 * 60);

  const hours = Math.floor(seconds / (60 * 60));
  seconds %= (60 * 60);

  const minutes = Math.floor(seconds / 60);
  seconds %= 60;

  const parts = [];
  if (days > 0) parts.push(`${days}d`);
  if (hours > 0) parts.push(`${hours}h`);
  if (minutes > 0) parts.push(`${minutes}m`);
  if (seconds > 0) parts.push(`${seconds}s`);

  return parts.join(' ');
};

const isEditBaseNpcDialogVisible = ref(false);
const isEditSrcVisible = ref(false);
const isAddSpecialAttackDialogVisible = ref(false);

const confirm = useConfirm();
const toast = useToast();
const primeDialog = useDialog();

const showAttachSpecialAttackModal = () => {
    primeDialog.open(AddBaseNpcSpecialAttacksDialog, {
        props: {
            header: 'Dodaj cios specjalny',
            modal: true,
            style: {
                width: '600px'
            }
        },
        data: {
            baseNpc: baseNpc
        },
        onClose: (options) => {
            if (options?.data?.specialAttack) {
                const specialAttackId = options.data.specialAttack.id;
                router.post(route('base-npcs.special-attacks.attach', {
                    baseNpc: baseNpc.id
                }), {
                    specialAttackId,
                }, {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.add({
                            severity: 'success',
                            summary: 'Sukces',
                            detail: `Cios specjalny "${options.data.specialAttack.name}" został dodany`,
                            life: 3000
                        });
                    },
                    onError: (errors) => {
                        toast.add({
                            severity: 'error',
                            summary: 'Błąd',
                            detail: Object.values(errors)[0] || 'Nie udało się dodać ciosu specjalnego',
                            life: 5000
                        });
                    }
                });
            }
        }
    });
}

const confirmDetachSpecialAttack = (attack: any) => {
    confirm.require({
        message: `Czy na pewno chcesz odłączyć cios specjalny "${attack.name}" od tego Base NPC?`,
        header: 'Potwierdź odłączenie',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('base-npcs.special-attacks.detach', {
                baseNpc: baseNpc.id,
                specialAttack: attack.id
            }), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Sukces',
                        detail: `Cios specjalny "${attack.name}" został odłączony`,
                        life: 3000
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: 'error',
                        summary: 'Błąd',
                        detail: Object.values(errors)[0] || 'Nie udało się odłączyć ciosu specjalnego',
                        life: 5000
                    });
                }
            });
        },
        reject: () => {
            // Optional: you can add a toast for rejection if needed
        }
    });
}

const handleSpecialAttackDialogClose = () => {
    isAddSpecialAttackDialogVisible.value = false;
}

</script>
<template>
    <AppLayout>

        <EditBaseNpcDialog :baseNpc v-model:visible="isEditBaseNpcDialogVisible" />

        <EditBaseNpcSrcDialog :baseNpc v-model:visible="isEditSrcVisible" />

        <ItemHeader
            :route-back="route('base-npcs.index')"
        >
            <template #header>
                <img v-tooltip="baseNpc.src" :src="baseNpc.src"  alt=""/>
                #{{ baseNpc.id }} - {{ baseNpc.name }}
            </template>
            <template #right-buttons>
                <Button @click="isEditBaseNpcDialogVisible = true" label="Edytuj" />
                <Button class="mx-2" @click="isEditSrcVisible = true" label="Zmień grafikę" />
            </template>
        </ItemHeader>

        <Message class="mb-8" severity="contrast">
            <div>Przeglądasz bazowgo NPC. Jest to najwyższy model NPC, któremu przydziela się nazwę, grafikę, czy statystyki do prowadzenia walk. Takiego bazowego NPC można umieścić w dowolnej ilości w dowolnych miejsicach na mapie np. jak Króliki. Jeśli widzisz na liście wiele lokalizacji tego NPC znaczy, że on jest w tych wszystkich miejscach na raz.</div>
        </Message>

        <DetailsCardList title="Informacje Podstawowe" >
            <DetailsCardListItem label="Nazwa" :value="baseNpc.name" />
            <DetailsCardListItem label="Link do grafiki" :value="baseNpc.src" />
            <DetailsCardListItem label="Lvl" :value="baseNpc.lvl" />
            <DetailsCardListItem label="Ranga">
                <template #value>
                    <Tag v-if="baseNpc.rank == 'NORMAL'" severity="info" value="Zwykły" />
                    <Tag v-else-if="baseNpc.rank == 'ELITE'" severity="success" value="Elita" />
                    <Tag v-else-if="baseNpc.rank == 'ELITE_II'" severity="warn" value="Elita II" />
                    <Tag v-else-if="baseNpc.rank == 'ELITE_III'" severity="warn" value="Elita III" />
                    <Tag v-else-if="baseNpc.rank == 'HERO'" severity="danger" value="Heros" />
                    <Tag v-else-if="baseNpc.rank == 'TITAN'" severity="contrast" value="Tytan" />
                </template>
            </DetailsCardListItem>
<!--            <DetailsCardListItem label="Type" :value="baseNpc.type" />-->
            <DetailsCardListItem label="Kategoria">
                <template #value>
                    <Tag v-if="baseNpc.category == 'MOB'" severity="info" value="MOB" />
                    <Tag v-else-if="baseNpc.category == 'NPC'" severity="secendary" value="NPC" />
                    <Tag v-else value="Nieznany rodzaj" />
                </template>
            </DetailsCardListItem>
            <DetailsCardListItem label="Profesja" :value="baseNpc.profession_name" />
            <DetailsCardListItem label="Typ">
                <template #value>
                    <Tag v-if="baseNpc.type === 4" severity="success" value="Warstwa" />
                    <Tag v-else severity="info" :value="baseNpc.type || 'Zwykły (0)'" />
                </template>
            </DetailsCardListItem>
            <DetailsCardListItem label="Agresywny">
                <template #value>
                    <Tag v-if="baseNpc.is_aggressive" severity="danger" value="Tak" />
                    <Tag v-else severity="success" value="Nie" />
                </template>
            </DetailsCardListItem>
            <DetailsCardListItem label="Boska interwencja">
                <template #value>
                    <Tag v-if="baseNpc.divine_intervention === null" severity="info" value="Domyślnie dla silnika" />
                    <Tag v-else-if="baseNpc.divine_intervention" severity="success" value="Tak" />
                    <Tag v-else-if="!baseNpc.divine_intervention" severity="danger" value="Nie" />
                </template>
            </DetailsCardListItem>
            <DetailsCardListItem label="Min. czas respawnu">
                <template #value>
                    <Tag v-if="baseNpc.min_respawn_time === null" severity="info" value="Domyślnie dla silnika" />
                    <Tag v-else severity="success" :value="formatTimeFromSeconds(baseNpc.min_respawn_time)" />
                </template>
            </DetailsCardListItem>
            <DetailsCardListItem label="Max. czas respawnu">
                <template #value>
                    <Tag v-if="baseNpc.max_respawn_time === null" severity="info" value="Domyślnie dla silnika" />
                    <Tag v-else severity="success" :value="formatTimeFromSeconds(baseNpc.max_respawn_time)" />
                </template>
            </DetailsCardListItem>
        </DetailsCardList>

        <div class="card">
            <BaseNpcLootsTable  :base-npc />
        </div>

        <!-- Special Attacks Section -->
        <div v-if="baseNpc.special_attacks && baseNpc.special_attacks.length > 0" class="card">
            <h3 class="mb-4">Ciosy Specjalne ({{ baseNpc.special_attacks.length }})</h3>
            <div class="flex gap-2 mb-4">
                <Button label="Dodaj cios specjalny" @click="showAttachSpecialAttackModal"/>
            </div>
            <div class="grid gap-4">
                <div v-for="attack in baseNpc.special_attacks" :key="attack.id"
                     class="border rounded-lg p-4 bg-surface-section">
                    <div class="flex justify-between items-start mb-3">
                        <h4 class="text-lg font-semibold text-color">{{ attack.name }}</h4>
                        <div class="flex gap-2">
                            <Tag :severity="attack.attack_type === 'SPECIAL' ? 'danger' : 'info'"
                                 :value="attack.attack_type"/>
                            <Tag severity="info" :value="attack.target"/>
                            <Button severity="danger" icon="pi pi-times" size="small"
                                    @click="confirmDetachSpecialAttack(attack)"/>
                        </div>
                    </div>

                    <div v-if="attack.charge_turns > 0" class="mb-3">
                        <Tag severity="warning" :value="`Ładowanie: ${attack.charge_turns} tur`"/>
                    </div>

                    <!-- Effects -->
                    <div v-if="attack.effects && attack.effects.length > 0" class="mb-3">
                        <h5 class="font-medium mb-2 text-color-secondary">Efekty:</h5>
                        <div class="flex flex-wrap gap-2">
                            <Tag v-for="effect in attack.effects" :key="`${effect.type}-${effect.value}`"
                                 severity="success"
                                 :value="`${effect.type}: ${effect.value}${effect.duration > 0 ? ` (${effect.duration} tur)` : ''}`"/>
                        </div>
                    </div>

                    <!-- Damages -->
                    <div v-if="attack.damages && attack.damages.length > 0">
                        <h5 class="font-medium mb-2 text-color-secondary">Obrażenia:</h5>
                        <div class="flex flex-wrap gap-2">
                            <Tag v-for="damage in attack.damages" :key="`${damage.element}-${damage.min_damage}`"
                                 :severity="damage.element === 'FIRE' ? 'danger' : damage.element === 'LIGHTNING' ? 'warning' : 'info'"
                                 :value="`${damage.element}: ${damage.min_damage}-${damage.max_damage}`"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" v-else>
            <div class="flex justify-between items-center mb-4">
                <h3>Ciosy Specjalne</h3>
                <Button label="Dodaj cios specjalny" @click="showAttachSpecialAttackModal"/>
            </div>
            <p class="text-gray-600">Ten Base NPC nie ma przypisanych żadnych ciosów specjalnych.</p>
        </div>

        <div class="card">
            <BaseNpcLocationsTable />
        </div>

        <BaseNpcActivityLogsTable v-if="logs" :logs="logs" :base-npc-id="baseNpc.id" />

        <ConvertBaseNpcToLayer :baseNpc />

        <MergeBaseNpc :baseNpc />

        <RemoveBaseNpc :baseNpc />

    </AppLayout>
</template>

<style scoped>

</style>
