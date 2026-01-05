<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {Ref, ref} from "vue";

import {inject, onMounted, defineProps} from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import axios from "axios";
import {route} from "ziggy-js";
import {SpecialAttackResource} from "@/Resources/SpecialAttack.resource";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";

const dialogRef = inject<Ref<DynamicDialogInstance> | null>('dialogRef');

const {baseNpc} = defineProps<{
    baseNpc: BaseNpcResource
}>()

const save = () => {
    dialogRef.value.close({
        specialAttack: selectedSpecialAttack.value,
    });
}

const search = async (query: string) => {
    const {data} = await axios.get(route('web-api.special-attacks.search', {query}))
    console.log(data);
    return data;
}

const selectedSpecialAttack = ref<SpecialAttackResource | null>();

const filteredSpecialAttacks = ref<SpecialAttackResource[]>([]);

const filterSpecialAttacks = async ({query}: { query: string }) => {
    filteredSpecialAttacks.value = await search(query);
};

</script>

<template>
    <div class="flex flex-col gap-2">

        <AutoComplete
            class="w-full p-0"
            v-model="selectedSpecialAttack"
            placeholder="Wyszukaj cios specjalny"
            :suggestions="filteredSpecialAttacks"
            @complete="filterSpecialAttacks"
            :option-label="(specialAttack: SpecialAttackResource|null) => `${specialAttack?.name}`"
            fluid
        >
            <template #option="slotProps">
                <div class="name-item flex items-center justify-between">

                    <div class="flex items-center space-x-4">
                        <div class="text-center">
                            <span class="font-semibold text-gray-800">
                                [{{ slotProps.option.id }}] {{ slotProps.option.name }}
                            </span>
                            <div class="text-sm text-gray-600 flex gap-2 mt-1">
                                <Tag :severity="slotProps.option.attack_type === 'SPECIAL' ? 'danger' : 'info'"
                                     :value="slotProps.option.attack_type" size="small"/>
                                <Tag severity="info" :value="slotProps.option.target" size="small"/>
                                <Tag v-if="slotProps.option.charge_turns > 0"
                                     severity="warning"
                                     :value="`Ładowanie: ${slotProps.option.charge_turns}`"
                                     size="small"/>
                            </div>
                            <div v-if="slotProps.option.effects && slotProps.option.effects.length > 0"
                                 class="text-xs text-gray-500 mt-1">
                                Efekty: {{ slotProps.option.effects.map(e => e.type).join(', ') }}
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </AutoComplete>

        <div v-if="selectedSpecialAttack" class="mt-4 p-3 bg-surface-section rounded">
            <h4 class="font-semibold mb-2">{{ selectedSpecialAttack.name }}</h4>

            <div class="flex gap-2 mb-2">
                <Tag :severity="selectedSpecialAttack.attack_type === 'SPECIAL' ? 'danger' : 'info'"
                     :value="selectedSpecialAttack.attack_type"/>
                <Tag severity="info" :value="selectedSpecialAttack.target"/>
                <Tag v-if="selectedSpecialAttack.charge_turns > 0"
                     severity="warning"
                     :value="`Ładowanie: ${selectedSpecialAttack.charge_turns} tur`"/>
            </div>

            <!-- Effects -->
            <div v-if="selectedSpecialAttack.effects && selectedSpecialAttack.effects.length > 0" class="mb-2">
                <div class="text-sm font-medium text-color-secondary mb-1">Efekty:</div>
                <div class="flex flex-wrap gap-1">
                    <Tag v-for="effect in selectedSpecialAttack.effects" :key="`${effect.type}-${effect.value}`"
                         severity="success" size="small"
                         :value="`${effect.type}: ${effect.value}${effect.duration > 0 ? ` (${effect.duration} tur)` : ''}`"/>
                </div>
            </div>

            <!-- Damages -->
            <div v-if="selectedSpecialAttack.damages && selectedSpecialAttack.damages.length > 0">
                <div class="text-sm font-medium text-color-secondary mb-1">Obrażenia:</div>
                <div class="flex flex-wrap gap-1">
                    <Tag v-for="damage in selectedSpecialAttack.damages" :key="`${damage.element}-${damage.min_damage}`"
                         :severity="damage.element === 'FIRE' ? 'danger' : damage.element === 'LIGHTNING' ? 'warning' : 'info'"
                         size="small"
                         :value="`${damage.element}: ${damage.min_damage}-${damage.max_damage}`"/>
                </div>
            </div>
        </div>

        <Button fluid @click="save" :disabled="!selectedSpecialAttack">Dodaj cios specjalny</Button>
    </div>
</template>

<style scoped>

</style>
