<script setup lang="ts">
import AppLogo from '@/Components/AppLogo.vue';
import AppConfig from '@/layout/AppConfig.vue';
import AppLayout from '@/layout/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    status: number;
}>();

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth));

const errorDetails = computed(() => {
    const details = {
        401: {
            icon: 'pi pi-user',
            title: 'Wymagane logowanie',
            message: 'Zaloguj się, żeby przejść dalej w edytorze.',
        },
        403: {
            icon: 'pi pi-lock',
            title: 'Brak dostępu',
            message: 'Nie masz uprawnień do tego miejsca w edytorze.',
        },
        404: {
            icon: 'pi pi-map-marker',
            title: 'Nie znaleziono strony',
            message: 'Ten adres nie prowadzi do żadnej znanej lokacji w panelu.',
        },
        405: {
            icon: 'pi pi-directions',
            title: 'Niedozwolona akcja',
            message: 'Ten adres istnieje, ale nie obsługuje użytej metody żądania.',
        },
        419: {
            icon: 'pi pi-clock',
            title: 'Sesja wygasła',
            message: 'Odśwież stronę i spróbuj wykonać akcję jeszcze raz.',
        },
        429: {
            icon: 'pi pi-hourglass',
            title: 'Za dużo prób',
            message: 'Odczekaj chwilę przed wykonaniem kolejnej akcji.',
        },
        500: {
            icon: 'pi pi-exclamation-triangle',
            title: 'Błąd serwera',
            message: 'Coś po stronie aplikacji nie zadziałało poprawnie.',
        },
        503: {
            icon: 'pi pi-wrench',
            title: 'Przerwa techniczna',
            message: 'Edytor jest chwilowo niedostępny.',
        },
    };

    return details[props.status] ?? {
        icon: 'pi pi-info-circle',
        title: 'Nieoczekiwany błąd',
        message: 'Aplikacja nie mogła dokończyć tego żądania.',
    };
});

const primaryActionLabel = computed(() => isAuthenticated.value ? 'Wróć do dashboardu' : 'Przejdź do logowania');
const primaryActionRoute = computed(() => isAuthenticated.value ? route('dashboard') : route('login'));

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
        return;
    }

    router.visit(primaryActionRoute.value);
};
</script>

<template>
    <AppLayout v-if="isAuthenticated">
        <div class="mx-auto flex min-h-[55vh] max-w-5xl items-center justify-center px-4 py-8">
            <section class="w-full overflow-hidden rounded-lg border border-surface-200 bg-surface-0 shadow-card dark:border-surface-700 dark:bg-surface-900">
                <div class="grid gap-0 lg:grid-cols-[0.95fr_1.05fr]">
                    <div class="flex min-h-64 items-center justify-center bg-surface-950 px-8 py-10 text-white dark:bg-surface-800">
                        <div class="text-center">
                            <div class="mb-4 flex justify-center">
                                <span class="flex h-16 w-16 items-center justify-center rounded-lg bg-primary-500 text-3xl shadow-md">
                                    <i :class="errorDetails.icon"></i>
                                </span>
                            </div>
                            <div class="text-7xl font-bold leading-none tracking-normal">{{ status }}</div>
                            <div class="mt-3 text-sm font-semibold uppercase tracking-normal text-primary-200">Margatron Map Editor</div>
                        </div>
                    </div>

                    <div class="flex flex-col justify-center px-8 py-10 md:px-10">
                        <Tag severity="danger" :value="`HTTP ${status}`" class="mb-5 w-fit" />
                        <h1 class="mb-3 text-3xl font-bold text-surface-900 dark:text-surface-0">{{ errorDetails.title }}</h1>
                        <p class="mb-8 max-w-xl text-base leading-7 text-surface-600 dark:text-surface-300">
                            {{ errorDetails.message }}
                        </p>

                        <div class="flex flex-wrap gap-3">
                            <Button :label="primaryActionLabel" icon="pi pi-home" @click="router.visit(primaryActionRoute)" />
                            <Button label="Wróć" icon="pi pi-arrow-left" severity="secondary" outlined @click="goBack" />
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>

    <div v-else class="min-h-screen bg-surface-50 text-surface-900 dark:bg-surface-950 dark:text-surface-0">
        <div class="mx-auto flex min-h-screen w-full max-w-5xl flex-col px-6 py-8">
            <header class="flex items-center justify-between">
                <AppLogo />
                <Tag severity="danger" :value="`HTTP ${status}`" />
            </header>

            <main class="flex flex-1 items-center justify-center py-12">
                <section class="w-full max-w-2xl rounded-lg border border-surface-200 bg-surface-0 p-8 text-center shadow-card dark:border-surface-700 dark:bg-surface-900 md:p-10">
                    <div class="mb-5 flex justify-center">
                        <span class="flex h-16 w-16 items-center justify-center rounded-lg bg-primary-500 text-3xl text-white shadow-md">
                            <i :class="errorDetails.icon"></i>
                        </span>
                    </div>
                    <div class="mb-3 text-6xl font-bold leading-none text-primary-600 dark:text-primary-300">{{ status }}</div>
                    <h1 class="mb-3 text-3xl font-bold">{{ errorDetails.title }}</h1>
                    <p class="mx-auto mb-8 max-w-lg text-base leading-7 text-surface-600 dark:text-surface-300">
                        {{ errorDetails.message }}
                    </p>

                    <div class="flex flex-wrap justify-center gap-3">
                        <Button :label="primaryActionLabel" icon="pi pi-sign-in" @click="router.visit(primaryActionRoute)" />
                        <Button label="Wróć" icon="pi pi-arrow-left" severity="secondary" outlined @click="goBack" />
                    </div>
                </section>
            </main>
        </div>

        <AppConfig simple />
    </div>
</template>
