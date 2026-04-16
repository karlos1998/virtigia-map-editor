<script setup lang="ts">
import { DropdownListType } from "@/Resources/DropdownList.type";
import AppLayout from "@/layout/AppLayout.vue";
import { useForm, Link, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const { currencyList, periodList } = usePage<{
    currencyList: DropdownListType
    periodList: DropdownListType
}>().props

const form = useForm({
    name: "",
    currency: "dragonTear",
    period: "month",
})

const submit = (): void => {
    form.post(route("hotels.store"))
}
</script>

<template>
    <AppLayout>
        <div class="mx-auto flex max-w-3xl flex-col gap-4">
            <div class="card">
                <div class="mb-4">
                    <h1 class="text-2xl font-bold">Nowy hotel</h1>
                    <p class="text-surface-500 dark:text-surface-400">
                        Nadaj hotelowi nazwę, np. `Zajazd u Makiny`, a potem dodaj do niego pokoje.
                    </p>
                </div>

                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-2">
                        <label for="name" class="font-semibold">Nazwa hotelu</label>
                        <InputText id="name" v-model="form.name" autocomplete="off" />
                        <Message v-if="form.errors.name" severity="error" size="small" variant="simple">
                            {{ form.errors.name }}
                        </Message>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-2">
                            <label for="currency" class="font-semibold">Waluta hotelu</label>
                            <Dropdown
                                input-id="currency"
                                v-model="form.currency"
                                :options="currencyList"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                            />
                            <Message v-if="form.errors.currency" severity="error" size="small" variant="simple">
                                {{ form.errors.currency }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="period" class="font-semibold">Okres</label>
                            <Dropdown
                                input-id="period"
                                v-model="form.period"
                                :options="periodList"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                            />
                            <Message v-if="form.errors.period" severity="error" size="small" variant="simple">
                                {{ form.errors.period }}
                            </Message>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Link :href="route('hotels.index')">
                            <Button type="button" label="Wróć" severity="secondary" outlined />
                        </Link>
                        <Button label="Utwórz hotel" icon="pi pi-check" :loading="form.processing" @click="submit" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
