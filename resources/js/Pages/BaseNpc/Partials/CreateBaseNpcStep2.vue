<script setup lang="ts">
import { ref } from "vue";

const npcTypes = [
    { label: "Zwykły", value: 0 },
    { label: "Elita", value: 1 },
    { label: "Elita II", value: 2 },
    { label: "Elita III", value: 3 },
    { label: "Heros", value: 4 },
    { label: "Tytan", value: 5 }
];

const type = defineModel<number>('type');
const name = defineModel<string>('name');
const lvl = defineModel<number>('lvl');

defineProps<{
    errors: {
        type: string
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
                    v-for="npcType in npcTypes"
                    :key="npcType.value"
                    class="flex items-center gap-2">
                    <RadioButton
                        :inputId="npcType.value.toString()"
                        :value="npcType.value" />
                    <label :for="npcType.value.toString()">{{ npcType.label }}</label>
                </div>
            </RadioButtonGroup>
        </Fieldset>
        <Message severity="error" size="small" variant="simple">{{errors.type}}</Message>

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
