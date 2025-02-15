<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import CreateBaseNpcStep1 from "@/Pages/BaseNpc/Partials/CreateBaseNpcStep1.vue";
import CreateBaseNpcStep2 from "@/Pages/BaseNpc/Partials/CreateBaseNpcStep2.vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import { route } from "ziggy-js";
import {computed, ref} from "vue";
import { BaseNpcResource } from "@/Resources/BaseNpc.resource";

const form = useForm({
    src: '',
    rank: 'NORMAL',
    name: '',
    lvl: 0,
});

const results = ref<Record<string, BaseNpcResource[]>>({});
const loadingStates = ref<Record<string, boolean>>({});
const allLoaded = ref(false);

interface FilterConfig {
    title: string;
    filter: object;
}

const generateFilters = (form: any): Record<string, FilterConfig> => {
    const filters: Record<string, FilterConfig> = {};

    if (form.name) {
        filters["name"] = {
            title: "Nazwa",
            filter: {
                "name": {
                    "operator": "and",
                    "constraints": [{ "value": form.name, "matchMode": "contains" }]
                }
            }
        };
    }
    if (form.src) {
        filters["src"] = {
            title: "Grafika",
            filter: {
                "src": {
                    "operator": "and",
                    "constraints": [{ "value": form.src, "matchMode": "contains" }]
                }
            }
        };
    }
    if (form.name && form.src) {
        filters["name_and_src"] = {
            title: "Nazwa i Grafika",
            filter: {
                "name": filters["name"].filter["name"],
                "src": filters["src"].filter["src"]
            }
        };
    }
    if (form.name && form.lvl !== null) {
        filters["name_and_lvl"] = {
            title: "Nazwa i Poziom",
            filter: {
                "name": filters["name"].filter["name"],
                "lvl": {
                    "operator": "and",
                    "constraints": [{ "value": form.lvl, "matchMode": "equals" }]
                }
            }
        };
    }
    if (form.src && form.lvl !== null) {
        filters["src_and_lvl"] = {
            title: "Grafika i Poziom",
            filter: {
                "src": filters["src"].filter["src"],
                "lvl": {
                    "operator": "and",
                    "constraints": [{ "value": form.lvl, "matchMode": "equals" }]
                }
            }
        };
    }
    if (form.name && form.src && form.lvl !== null) {
        filters["name_src_lvl"] = {
            title: "Nazwa, Grafika i Poziom",
            filter: {
                "name": filters["name"].filter["name"],
                "src": filters["src"].filter["src"],
                "lvl": {
                    "operator": "and",
                    "constraints": [{ "value": form.lvl, "matchMode": "equals" }]
                }
            }
        };
    }

    return filters;
};

const findNpcsWithFilters = async (filters: Record<string, FilterConfig>) => {
    allLoaded.value = false;
    const requests = Object.entries(filters).map(async ([key, { title, filter }]) => {
        loadingStates.value[key] = true;
        try {
            const response = await axios.get(route('web-api.base-npcs.index', {
                "tables": {
                    "default": {
                        "page": 1,
                        "perPage": 30,
                        "globalFilter": "",
                        "filters": JSON.stringify(filter),
                        "sortOrder": "ASC",
                        "sortField": "id"
                    }
                }
            }));
            results.value[key] = response.data;
        } catch (error) {
            results.value[key] = [];
            console.error(`Error fetching data for ${title}:`, error.message);
        } finally {
            loadingStates.value[key] = false;
        }
    });

    await Promise.all(requests);
    allLoaded.value = true;
};

const analise = async () => {
    const filters = generateFilters(form);
    await findNpcsWithFilters(filters);
};

const hasDuplicates = computed(() => Object.values(results.value).some(list => Array.isArray(list) && list.length > 0));

const create = () => {
    form.post(route('base-npcs.store'));
}
</script>

<template>
    <AppLayout>
        <div class="card">
            <Stepper value="1" linear>
                <StepItem value="1">
                    <Step>Wybór grafiki</Step>
                    <StepPanel v-slot="{ activateCallback }">
                        <CreateBaseNpcStep1
                            v-model:src="form.src"
                            @selected="activateCallback('2')"
                        />
                        <div class="py-6">
                            <Button label="Następny krok" @click="activateCallback('2')" :disabled="!form.src" />
                        </div>
                    </StepPanel>
                </StepItem>
                <StepItem value="2">
                    <Step>Podstawowe dane</Step>
                    <StepPanel v-slot="{ activateCallback }">
                        <CreateBaseNpcStep2
                            v-model:rank="form.rank"
                            v-model:name="form.name"
                            v-model:lvl="form.lvl"
                            v-bind="{errors: form.errors}"
                        />
                        <div class="flex py-6 gap-2">
                            <Button label="Cofnij" severity="secondary" @click="activateCallback('1')" />
                            <Button :disabled="!form.name" label="Następny krok" @click="activateCallback('3'); analise()" />
                        </div>
                    </StepPanel>
                </StepItem>
                <StepItem value="3">
                    <Step>Podsumowanie</Step>
                    <StepPanel v-slot="{ activateCallback }">
                        <div class="mb-5">
                            <div class="font-bold mb-4">Twoje dane</div>
                            <div>
                                <span class="font-bold">Nazwa:</span>
                                <span class="ml-3">{{form.name}}</span>
                            </div>
                            <div>
                                <span class="font-bold">Grafika:</span>
                                <span class="ml-3">{{form.src}}</span>
                            </div>
                            <div>
                                <span class="font-bold">Lvl:</span>
                                <span class="ml-3">{{form.lvl}}</span>
                            </div>
                        </div>
                        <div v-if="!allLoaded" class="flex flex-col h-48 items-center justify-center">
                            <i class="pi pi-spin pi-spinner text-2xl"></i>
                            <p class="mt-4">Analizowanie danych, proszę czekać...</p>
                        </div>
                        <div v-else>
                            <div class="font-bold">Wyszukane powtórzenia dla róznych kombinacji:</div>
                            <div v-if="results" v-for="(npcs, key) in results" :key="key" class="mt-6">
                                <h4 class="font-bold text-lg">Kombinacja -> {{ generateFilters(form)[key]?.title || "Nieznane" }}</h4>
                                <div v-if="loadingStates[key]" class="text-primary mt-2">Ładowanie...</div>
                                <ul v-else-if="npcs.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    <li v-for="npc in npcs" :key="npc.id" class="p-4 border rounded shadow-sm flex items-center gap-4">
                                        <img :src="`${npc.src}`" alt="NPC Image" class="w-16 h-16 object-cover rounded" />
                                        <div>
                                            <p class="font-semibold">{{ npc.name }}</p>
                                            <p class="text-sm text-gray-500">{{ npc.lvl }} lvl</p>
                                        </div>
                                    </li>
                                </ul>
                                <div v-else>Brak powtórzeń</div>
                            </div>

                            <div class="mt-6">
                                <Message
                                    v-if="hasDuplicates"
                                    severity="warn"
                                >
                                    Istnieje szansa na zduplikowany NPC. Przejrzyj powyższe wyniki i upewnij się, czy na pewno chcesz stworzyć takiego NPC.
                                </Message>
                                <Message v-else severity="success">Nie znaleziono duplikatów. Możesz bezpiecznie utworzyć tego npc</Message>
                            </div>

                            <div class="py-6">
                                <Button label="Cofnij" severity="secondary" @click="activateCallback('2')" />

                                <Button v-if="hasDuplicates" :disabled="form.processing" label="Utwórz NPC mimo duplikatów" @click="create" />
                                <Button v-else :disabled="form.processing" label="Utwórz NPC" @click="create" />
                            </div>

                            <Message severity="error" size="small" variant="simple">{{form.errors.src}}</Message>
                            <Message severity="error" size="small" variant="simple">{{form.errors.name}}</Message>
                            <Message severity="error" size="small" variant="simple">{{form.errors.type}}</Message>
                            <Message severity="error" size="small" variant="simple">{{form.errors.lvl}}</Message>

                        </div>
                    </StepPanel>
                </StepItem>
            </Stepper>
        </div>
    </AppLayout>
</template>


<style scoped>
</style>
