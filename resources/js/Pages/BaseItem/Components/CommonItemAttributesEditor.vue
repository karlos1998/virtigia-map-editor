<script setup lang="ts">
import Checkbox from 'primevue/checkbox';
import Textarea from 'primevue/textarea';
import { commonOwnershipAttributes, descriptionAttribute } from '../AttributeOptions';

const attributes = defineModel<Record<string, any>>('attributes', { required: true });
</script>

<template>
    <section class="card my-4 p-3">
        <h4 class="font-semibold mb-3">Wspólne atrybuty przedmiotu</h4>

        <div class="flex flex-col gap-2">
            <label for="base-item-description" class="text-sm font-medium text-surface-700">
                {{ descriptionAttribute.label }}
            </label>
            <Textarea
                id="base-item-description"
                v-model="attributes.description"
                :placeholder="descriptionAttribute.placeholder"
                rows="4"
                class="w-full"
                auto-resize
            />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 mt-4">
            <label
                v-for="attribute in commonOwnershipAttributes"
                :key="attribute.key"
                :for="`base-item-${attribute.key}`"
                class="flex items-center gap-3 rounded-lg border border-surface-200 p-3 cursor-pointer"
            >
                <Checkbox
                    :input-id="`base-item-${attribute.key}`"
                    v-model="attributes[attribute.key]"
                    binary
                />
                <span class="text-sm font-medium text-surface-700">{{ attribute.label }}</span>
            </label>
        </div>
    </section>
</template>
