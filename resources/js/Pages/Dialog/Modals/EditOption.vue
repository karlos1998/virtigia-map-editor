<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Drawer from 'primevue/drawer';
import InputGroup from 'primevue/inputgroup';
import Select from 'primevue/dropdown';
import Message from 'primevue/message';
import Accordion from 'primevue/accordion';
import AccordionPanel from 'primevue/accordionpanel';
import Checkbox from 'primevue/checkbox';
import InputNumber from 'primevue/inputnumber';
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useToast } from 'primevue';
import axios from 'axios';
import { DialogNodeOptionEdgeWithRules, DialogOptionResource } from '@/Resources/DialogOption.resource';
import { DropdownListType } from '@/Resources/DropdownList.type';
import EditRules from '../Componnts/EditRules.vue';
import { DialogNodeRulesResource } from '@/Resources/DialogNodeRules.resource';
import TreeSelectAdapter from '../Componnts/TreeSelectAdapter.vue';
import { useQuestStepSelection } from '../Composables/useQuestStepSelection';
import AdditionalActionsEditor from '@/Pages/Dialog/Componnts/AdditionalActionsEditor.vue';
import { DialogNodeAdditionalActionsResource } from '@/Resources/DialogNodeAdditionalActions.resource';

const dialogNodeOptionAdditionalActionsList = ref(usePage<{dialogNodeOptionAdditionalActionsList: DropdownListType}>().props.dialogNodeOptionAdditionalActionsList);

const props = defineProps<{
    visible: boolean,
    option: DialogOptionResource,
    parent: string,
    dialog_id: number,
}>();

const emit = defineEmits<{
    'update:visible': [value: boolean],
    close: [data: { remove?: boolean, dialogOption?: DialogOptionResource }]
}>();

type FormOptionData = {
    label: string
    additional_action: any// todo
    additional_action_data?: string
    additional_actions: DialogNodeAdditionalActionsResource
    cooldown: number|null
    rules: DialogNodeRulesResource
    edges: DialogNodeOptionEdgeWithRules[]
}

const form = useForm<FormOptionData>({
    label: '',
    additional_action: null,
    additional_action_data: '',
    additional_actions: {},
    cooldown: null,
    rules: {},
    edges: [],
});

const toast = useToast();
const additionalActionsEditor = ref<{
    getPayload: () => DialogNodeAdditionalActionsResource | null;
} | null>(null);

const { questNodes, loading, loadQuests, loadQuestStepById, onQuestNodeExpand } = useQuestStepSelection();

const formLoaded = ref(false);
const drawerVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

watch(() => form.additional_action, (newValue) => {
    if (newValue !== 'setQuestStep') {
        form.additional_action_data = '';
    }
});

const closeDrawer = () => {
    drawerVisible.value = false;
    emit('close', {});
};

watch(() => props.visible, (visible) => {
    if (!visible || !props.option) {
        return;
    }

    form.label = props.option?.label ?? '';
    form.additional_action = props.option?.additional_action ?? '';
    form.additional_action_data = props.option?.additional_action_data ?? '';
    form.additional_actions = !Array.isArray(props.option?.additional_actions) ? props.option?.additional_actions ?? {} : {};
    form.cooldown = props.option?.cooldown ?? null;
    form.rules = Object.keys(props.option?.rules || {}).length > 0 ? props.option?.rules : {};
    form.edges = props.option?.edges.map(edge => ({
        ...edge,
        rules: edge.rules || {}
    })) || [];

    loadQuests();

    if (form.additional_action === 'setQuestStep' && form.additional_action_data && form.additional_action_data.startsWith('s-')) {
        const stepId = parseInt(form.additional_action_data.substring(2));
        if (!isNaN(stepId)) {
            loadQuestStepById(stepId);
        }
    }

    formLoaded.value = true
});

const processing = ref(false);

const formattedCooldown = computed(() => {
    const seconds = form.cooldown;
    if (!seconds || seconds <= 0) return '';

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainingSeconds = seconds % 60;

    const parts = [];
    if (hours > 0) parts.push(`${hours}h`);
    if (minutes > 0) parts.push(`${minutes}m`);
    if (remainingSeconds > 0 || parts.length === 0) parts.push(`${remainingSeconds}s`);

    return parts.join(' ');
});

const hasCooldown = computed({
    get: () => form.cooldown !== null && form.cooldown > 0,
    set: (value: boolean) => {
        form.cooldown = value ? (form.cooldown || 60) : null;
    }
});

const save = () => {
    if(!props.option) return;

    if (form.additional_action === 'setQuestStep') {
        if (!form.additional_action_data) {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wybierz krok questa', life: 3000 });
            return;
        }

        if (form.additional_action_data.startsWith('q-')) {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wybierz krok questa, nie cały quest', life: 3000 });
            return;
        }
    }

    const additionalActionsPayload = additionalActionsEditor.value?.getPayload();

    if (!additionalActionsPayload) {
        return;
    }

    drawerVisible.value = false;
    processing.value = true;

    const transformData = (data: FormOptionData) => {
        const d = { ...data, additional_actions: additionalActionsPayload };

        if (d.additional_action !== 'setQuestStep') {
            d.additional_action_data = undefined;
        }

        if (!hasCooldown.value) {
            d.cooldown = null;
        }

        return d;
    }

    const data = transformData(form.data());
    axios.patch<DialogOptionResource>(route('dialogs.nodes.options.update', {
        dialogNodeOption: props.option.id,
        dialog: props.dialog_id,
        dialogNode: props.option.node_id,
    }), data)
        .then(({data}) => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Opcja dialogowa została edytowana', life: 3000 });
            emit('close', { dialogOption: data });
        })
        .catch(() => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wystąpił problem podczas edycji opcji dialogowej', life: 3000 });
        })
        .finally(() => {
            processing.value = false;
        });
};

const remove = () => {

    if(!props.option) return;

    drawerVisible.value = false;
    processing.value = true;

    axios.delete(route('dialogs.nodes.options.destroy', {
        dialogNodeOption: props.option.id,
        dialog: props.dialog_id,
        dialogNode: props.option.node_id,
    }))
        .then(() => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Opcja dialogowa została usunięta', life: 3000 });
            emit('close', { remove: true });
        })
        .catch(({response}) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
        })
        .finally(() => {
            processing.value = false;
        });
};
</script>

<template>
    <Drawer
        v-model:visible="drawerVisible"
        position="right"
        :style="{ width: '50%' }"
        :modal="true"
        :dismissable="false"
        :closable="false"
        header="Edycja opcji dialogowej"
    >
        <div class="p-4 flex flex-col gap-4" v-if="formLoaded">
            <div class="card">
                <h3 class="text-xl mb-3">Podstawowe informacje</h3>
                <div class="mb-3">
                    <label class="block mb-2 font-medium">Tekst opcji dialogowej:</label>
                    <Textarea v-model="form.label" rows="5" cols="50" maxlength="1000" class="w-full" />
                    <Message severity="error" size="small" variant="simple">{{ form.errors.label }}</Message>
                </div>

                <div class="mb-3">
                    <label class="block mb-2 font-medium">Dodatkowa akcja:</label>
                    <InputGroup>
                        <Button icon="pi pi-times" severity="danger" aria-label="Cancel" @click="form.additional_action = null" />
                        <Select v-model="form.additional_action" :options="dialogNodeOptionAdditionalActionsList" optionLabel="label" option-value="value" placeholder="Wybierz dodatkową akcje" class="w-full" />
                    </InputGroup>
                </div>

                <!-- TreeSelectAdapter for setQuestStep action -->
                <div v-if="form.additional_action === 'setQuestStep'" class="mb-3">
                    <label class="block mb-2 font-medium">Wybierz krok questa:</label>
                    <TreeSelectAdapter
                        v-model="form.additional_action_data"
                        :loading="loading"
                        :options="questNodes"
                        :onNodeExpand="onQuestNodeExpand"
                        class="w-full"
                    />
                    <small class="text-gray-500 mt-1 block">Uwaga: Można wybrać tylko krok questa (s-X), nie cały quest (q-X).</small>
                </div>

                <!-- Cooldown section -->
                <div class="mb-3">
                    <div class="flex items-center gap-2 mb-2">
                        <Checkbox v-model="hasCooldown" :binary="true" input-id="cooldown-check" />
                        <label for="cooldown-check" class="font-medium cursor-pointer">Ustaw cooldown</label>
                    </div>

                    <div v-if="hasCooldown" class="flex items-center gap-3 ml-6">
                        <div class="flex-1">
                            <label class="block mb-1 text-sm text-gray-600">Czas w sekundach:</label>
                            <InputNumber
                                v-model="form.cooldown"
                                :min="1"
                                :max="999999"
                                class="w-full"
                                placeholder="Wpisz czas w sekundach"
                            />
                        </div>
                        <div v-if="formattedCooldown" class="text-sm text-green-600 font-medium">
                            = {{ formattedCooldown }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 class="text-xl mb-3">Akcje dodatkowe opcji</h3>
                <AdditionalActionsEditor ref="additionalActionsEditor" v-model:actions="form.additional_actions" />
            </div>

            <div class="card">
                <h3 class="text-xl mb-3">Reguły dostępu opcji dialogowej</h3>
                <EditRules v-model:rules="form.rules" />
            </div>

            <div class="card">
                <h3 class="text-xl mb-3">Reguły przejścia do kolejnych dialogów</h3>
                <Accordion v-if="form.edges && form.edges.length > 0">
                    <AccordionPanel v-for="(edge, index) in form.edges" :key="edge.edge_id">
                        <AccordionHeader>
                            {{ edge.node.content || edge.node.type }}
                        </AccordionHeader>
                        <AccordionContent>
                            <EditRules v-model:rules="form.edges[index].rules" />
                        </AccordionContent>
                    </AccordionPanel>
                </Accordion>
                <Message v-else severity="warn">Brak kolejnych kroków dialogowych</Message>
            </div>

            <div class="flex gap-3 justify-end mt-4">
                <Button :loading="processing" @click="closeDrawer" severity="secondary">Anuluj</Button>
                <Button :loading="processing" @click="remove" severity="danger">Usuń</Button>
                <Button :loading="processing" @click="save" severity="primary">Zapisz</Button>
            </div>
        </div>
    </Drawer>
</template>

<style scoped>
.card {
    background-color: var(--surface-card);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    margin-bottom: 1rem;
}

:deep(.p-drawer-content) {
    padding: 0;
}

:deep(.p-accordion .p-accordion-header:not(.p-disabled).p-highlight .p-accordion-header-link) {
    background-color: var(--primary-color);
    color: var(--primary-color-text);
}

:deep(.p-accordion .p-accordion-header:not(.p-disabled).p-highlight:hover .p-accordion-header-link) {
    background-color: var(--primary-600);
}
</style>
