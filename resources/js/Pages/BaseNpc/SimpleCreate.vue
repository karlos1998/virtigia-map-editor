<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { DropdownListType } from "@/Resources/DropdownList.type";

const props = defineProps<{
    availableRanks: DropdownListType;
    availableCategories: DropdownListType;
}>();

const form = useForm({
    name: '',
    lvl: 1,
    rank: 'NORMAL',
    category: 'ANIMAL',
    image: '',
});

const imagePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        const reader = new FileReader();

        reader.onload = (e) => {
            if (e.target && typeof e.target.result === 'string') {
                imagePreview.value = e.target.result;
                form.image = e.target.result;
            }
        };

        reader.readAsDataURL(file);
    }
};

const submit = () => {
    form.post(route('base-npcs.store-simple'));
};
</script>

<template>
    <AppLayout>
        <div class="card">
            <h1 class="text-2xl font-bold mb-6">Create New NPC</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column - Image Upload -->
                    <div class="flex flex-col items-center space-y-4">
                        <div
                            class="border-2 border-dashed border-gray-300 rounded-lg p-4 w-full h-64 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-50"
                            @click="fileInput?.click()"
                        >
                            <input
                                type="file"
                                ref="fileInput"
                                class="hidden"
                                accept="image/png,image/gif"
                                @change="handleImageUpload"
                            />

                            <div v-if="!imagePreview" class="text-center">
                                <i class="pi pi-image text-4xl text-gray-400 mb-2"></i>
                                <p class="text-gray-500">Click to upload an image (PNG or GIF, max 128x118px)</p>
                            </div>

                            <img
                                v-else
                                :src="imagePreview"
                                alt="Preview"
                                class="max-h-full max-w-full object-contain"
                            />
                        </div>

                        <div v-if="form.errors.image" class="text-red-500 text-sm">
                            {{ form.errors.image }}
                        </div>
                    </div>

                    <!-- Right Column - Form Fields -->
                    <div class="space-y-4">
                        <div class="flex flex-col">
                            <label for="name" class="mb-1 font-medium">Name</label>
                            <InputText
                                id="name"
                                v-model="form.name"
                                :class="{ 'p-invalid': form.errors.name }"
                            />
                            <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
                        </div>

                        <div class="flex flex-col">
                            <label for="lvl" class="mb-1 font-medium">Level</label>
                            <InputNumber
                                id="lvl"
                                v-model="form.lvl"
                                :min="0"
                                :max="300"
                                :class="{ 'p-invalid': form.errors.lvl }"
                            />
                            <small v-if="form.errors.lvl" class="text-red-500">{{ form.errors.lvl }}</small>
                        </div>

                        <div class="flex flex-col">
                            <label for="rank" class="mb-1 font-medium">Rank</label>
                            <Dropdown
                                id="rank"
                                v-model="form.rank"
                                :options="props.availableRanks"
                                optionLabel="label"
                                optionValue="value"
                                :class="{ 'p-invalid': form.errors.rank }"
                            />
                            <small v-if="form.errors.rank" class="text-red-500">{{ form.errors.rank }}</small>
                        </div>

                        <div class="flex flex-col">
                            <label for="category" class="mb-1 font-medium">Category</label>
                            <Dropdown
                                id="category"
                                v-model="form.category"
                                :options="props.availableCategories"
                                optionLabel="label"
                                optionValue="value"
                                :class="{ 'p-invalid': form.errors.category }"
                            />
                            <small v-if="form.errors.category" class="text-red-500">{{ form.errors.category }}</small>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <Button
                        type="button"
                        label="Cancel"
                        severity="secondary"
                        :link="true"
                        :href="route('base-npcs.index')"
                    />
                    <Button
                        type="submit"
                        label="Create NPC"
                        :loading="form.processing"
                        :disabled="!form.name || !form.image"
                    />
                </div>
            </form>
        </div>
    </AppLayout>
</template>
