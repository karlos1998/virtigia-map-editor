<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layout/AppLayout.vue';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';
import Message from 'primevue/message';

interface ApiToken {
    id: number;
    name: string;
    last_used_at: string | null;
    expires_at: string | null;
    revoked_at: string | null;
    created_at: string | null;
    is_active: boolean;
}

interface NewApiTokenFlash {
    id: number;
    name: string;
    plain_text_token: string;
    expires_at: string | null;
}

const props = defineProps<{
    tokens: ApiToken[];
}>();

const page = usePage();

const newApiToken = computed<NewApiTokenFlash | null>(() => {
    return (page.props.flash?.newApiToken ?? null) as NewApiTokenFlash | null;
});

const createForm = reactive({
    name: '',
    expiresAt: null as Date | null,
});

const editingExpiry = ref<Record<number, Date | null>>({});

props.tokens.forEach((token) => {
    editingExpiry.value[token.id] = token.expires_at ? new Date(token.expires_at) : null;
});

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString('pl-PL');
};

const toIsoString = (value: Date | null): string | null => {
    return value ? value.toISOString() : null;
};

const submitCreateToken = (): void => {
    router.post(route('api-tokens.store'), {
        name: createForm.name,
        expires_at: toIsoString(createForm.expiresAt),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            createForm.name = '';
            createForm.expiresAt = null;
        },
    });
};

const saveExpiry = (token: ApiToken): void => {
    router.patch(route('api-tokens.update', token.id), {
        expires_at: toIsoString(editingExpiry.value[token.id] ?? null),
    }, {
        preserveScroll: true,
    });
};

const revokeToken = (token: ApiToken): void => {
    router.delete(route('api-tokens.destroy', token.id), {
        preserveScroll: true,
    });
};

const copyToken = async (): Promise<void> => {
    if (!newApiToken.value?.plain_text_token) {
        return;
    }

    await navigator.clipboard.writeText(newApiToken.value.plain_text_token);
};
</script>

<template>
    <AppLayout title="Tokeny API">
        <div class="space-y-6">
            <Card>
                <template #title>Tokeny API użytkownika</template>
                <template #content>
                    <div class="flex flex-col gap-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Tokenów używasz do autoryzacji REST API przez nagłówek
                            <code>Authorization: Bearer TOKEN</code>.
                        </p>

                        <div class="flex flex-wrap items-end gap-3">
                            <div class="w-full md:w-64">
                                <label class="mb-1 block text-sm font-medium">Nazwa tokenu</label>
                                <InputText v-model="createForm.name" class="w-full" placeholder="np. Integracja map" />
                            </div>

                            <div class="w-full md:w-72">
                                <label class="mb-1 block text-sm font-medium">Data ważności (opcjonalnie)</label>
                                <Calendar
                                    v-model="createForm.expiresAt"
                                    showTime
                                    hourFormat="24"
                                    dateFormat="yy-mm-dd"
                                    class="w-full"
                                    :showIcon="true"
                                />
                            </div>

                            <Button label="Utwórz token" icon="pi pi-plus" @click="submitCreateToken" />
                            <a href="/api/docs">
                                <Button label="Otwórz Swagger" icon="pi pi-book" severity="secondary" outlined />
                            </a>
                        </div>
                    </div>
                </template>
            </Card>

            <Message v-if="newApiToken" severity="warn" :closable="false">
                <div class="flex flex-col gap-2">
                    <p class="font-semibold">Nowy token został wygenerowany. Skopiuj go teraz, później nie będzie widoczny ponownie.</p>
                    <code class="break-all">{{ newApiToken.plain_text_token }}</code>
                    <div class="flex gap-2">
                        <Button label="Kopiuj token" size="small" icon="pi pi-copy" @click="copyToken" />
                    </div>
                </div>
            </Message>

            <Card>
                <template #title>Aktywne i historyczne tokeny</template>
                <template #content>
                    <DataTable :value="props.tokens" stripedRows>
                        <Column field="name" header="Nazwa" />
                        <Column header="Status">
                            <template #body="{ data }">
                                <Tag :value="data.is_active ? 'Aktywny' : 'Nieaktywny'" :severity="data.is_active ? 'success' : 'danger'" />
                            </template>
                        </Column>
                        <Column header="Data ważności">
                            <template #body="{ data }">
                                {{ formatDateTime(data.expires_at) }}
                            </template>
                        </Column>
                        <Column header="Ostatnie użycie">
                            <template #body="{ data }">
                                {{ formatDateTime(data.last_used_at) }}
                            </template>
                        </Column>
                        <Column header="Utworzono">
                            <template #body="{ data }">
                                {{ formatDateTime(data.created_at) }}
                            </template>
                        </Column>
                        <Column header="Akcje" style="min-width: 21rem;">
                            <template #body="{ data }">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Calendar
                                        v-model="editingExpiry[data.id]"
                                        showTime
                                        hourFormat="24"
                                        dateFormat="yy-mm-dd"
                                        class="w-44"
                                        :showIcon="true"
                                    />
                                    <Button
                                        size="small"
                                        icon="pi pi-save"
                                        label="Zapisz datę"
                                        severity="secondary"
                                        @click="saveExpiry(data)"
                                    />
                                    <Button
                                        size="small"
                                        icon="pi pi-ban"
                                        label="Unieważnij"
                                        severity="danger"
                                        outlined
                                        :disabled="!data.is_active"
                                        @click="revokeToken(data)"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
