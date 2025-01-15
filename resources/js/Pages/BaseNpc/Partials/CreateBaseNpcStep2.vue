<script setup lang="ts">
import { ref } from "vue";
import {BaseNpcRankEnum} from "@/Enums/BaseNpcRank.enum";
import {usePage} from "@inertiajs/vue3";
import {DropdownListType} from "@/Resources/DropdownList.type";

const availableRanks = usePage<{availableRanks: DropdownListType }>().props.availableRanks

const type = defineModel<BaseNpcRankEnum>('rank');
const name = defineModel<string>('name');
const lvl = defineModel<number>('lvl');

defineProps<{
    errors: {
        rank: string
        name: string
        lvl: string
    }
}>()
</script>

<template>
    <div class="flex flex-col items-center gap-5">

        <Fieldset legend="Typ Npc">
            <RadioButtonGroup v-model="type" name="schema" class="flex flex-wrap gap-4">
                <div
                    v-for="rank in availableRanks"
                    :key="rank.value"
                    class="flex items-center gap-2">
                    <RadioButton
                        :inputId="rank.value"
                        :value="rank.value"
                    />
                    <label :for="rank.value">{{ rank.label }}</label>
                </div>
            </RadioButtonGroup>
        </Fieldset>
        <Message severity="error" size="small" variant="simple">{{errors.rank}}</Message>

        <div class="flex flex-col gap-1">
            <label for="name">Nazwa</label>
            <InputText v-model="name" id="name" aria-describedby="name-help" />
            <Message severity="error" size="small" variant="simple">{{errors.name}}</Message>
        </div>

        <div class="flex flex-col gap-1">
            <label for="lvl">Poziom</label>
            <InputNumber v-model="lvl" id="lvl" aria-describedby="lvl-help" :min="0" max="300" />
            <Message severity="error" size="small" variant="simple">{{errors.lvl}}</Message>
        </div>
    </div>
</template>

<style scoped>
</style>
