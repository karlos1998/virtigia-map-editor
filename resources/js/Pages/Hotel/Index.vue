<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import { HotelResource } from "@/Resources/Hotel.resource";
import { Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";

defineProps<{
    hotels: HotelResource[]
}>()
</script>

<template>
    <AppLayout>
        <div class="card mb-4 flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Hotele</h1>
                <p class="text-surface-500 dark:text-surface-400">
                    Lista hoteli i pokoi przypiętych do kluczy oraz drzwi.
                </p>
            </div>

            <Link :href="route('hotels.create')">
                <Button label="Utwórz hotel" icon="pi pi-plus" />
            </Link>
        </div>

        <div v-if="hotels.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="hotel in hotels"
                :key="hotel.id"
                :href="route('hotels.show', hotel.id)"
                class="block"
            >
                <div class="card h-full border border-surface-200 transition hover:border-primary-400 hover:shadow-lg dark:border-surface-700">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm text-surface-500 dark:text-surface-400">#{{ hotel.id }}</div>
                            <div class="text-xl font-semibold">{{ hotel.name }}</div>
                        </div>

                        <Tag :value="`${hotel.rooms_count} pokoi`" />
                    </div>

                    <div class="text-sm text-surface-500 dark:text-surface-400">
                        Przejdź do hotelu, aby zarządzać listą pokoi i szybko dodawać kolejne na bazie istniejących.
                    </div>
                </div>
            </Link>
        </div>

        <div v-else class="card">
            <Message severity="info" :closable="false">
                Nie ma jeszcze żadnych hoteli. Utwórz pierwszy i zacznij dodawać pokoje.
            </Message>
        </div>
    </AppLayout>
</template>
