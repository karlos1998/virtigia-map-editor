<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import axios from 'axios';
import { MapResource } from '@/Resources/Map.resource';
import InputSwitch from 'primevue/inputswitch';
import Checkbox from 'primevue/checkbox';
import Dialog from 'primevue/dialog';

type RelatedMap = { id: number; name: string; is_city: boolean };
const props = defineProps<{ map: MapResource }>();
const isCity = ref(props.map.is_city);
const visible = ref(false);
const loading = ref(false);
const error = ref('');
const related = ref<RelatedMap[]>([]);
const form = useForm({ is_city: false, related_map_ids: [] as number[] });
watch(() => props.map.is_city, value => { isCity.value = value; });

const confirmChange = async () => {
    loading.value = true;
    error.value = '';
    form.clearErrors();
    form.is_city = isCity.value;
    related.value = [];
    form.related_map_ids = [];
    try {
        if (form.is_city) {
            const response = await axios.get<RelatedMap[]>(route('maps.city.related', props.map.id));
            related.value = response.data;
            form.related_map_ids = related.value.map(map => map.id);
        }
        visible.value = true;
    } catch {
        error.value = 'Nie udało się pobrać powiązanych lokalizacji. Spróbuj ponownie.';
    } finally {
        loading.value = false;
    }
};
const save = () => form.patch(route('maps.update.city', props.map.id), {
    preserveScroll: true,
    onSuccess: () => { visible.value = false; },
});
</script>

<template>
    <div>
        <h3 class="font-semibold mb-2">Miasto — bez zużywania wyczerpania</h3>
        <div class="flex items-center gap-2">
            <InputSwitch v-model="isCity" aria-label="Lokalizacja jest miastem" :disabled="loading || form.processing" />
            <Button label="Zapisz" :loading="loading" @click="confirmChange" />
        </div>
        <p class="mt-2 text-sm">{{ props.map.is_city ? 'W tej lokalizacji wyczerpanie nie spada.' : 'W tej lokalizacji wyczerpanie jest zużywane.' }}</p>
        <Message v-if="error" severity="error">{{ error }}</Message>
        <Dialog v-model:visible="visible" modal header="Potwierdź ustawienie wyczerpania" :style="{ width: '42rem', maxWidth: '95vw' }" :closable="!form.processing" :close-on-escape="!form.processing">
            <p>{{ form.is_city ? 'Oznaczyć tę lokalizację jako miasto i wyłączyć zużywanie wyczerpania?' : 'Włączyć zużywanie wyczerpania w tej lokalizacji?' }}</p>
            <template v-if="form.is_city">
                <p class="my-3">Wyłączyć wyczerpanie również w powiązanych lokalizacjach? Lista obejmuje wnętrza i kolejne piętra dostępne przez drzwi poza krawędziami map. Odznaczone lokalizacje zachowają dotychczasowe ustawienie.</p>
                <div v-if="related.length" class="flex flex-col gap-3 max-h-80 overflow-auto">
                    <label v-for="map in related" :key="map.id" :for="`city-related-${map.id}`" class="flex items-center gap-2">
                        <Checkbox v-model="form.related_map_ids" :input-id="`city-related-${map.id}`" :value="map.id" :disabled="form.processing" />
                        <span>#{{ map.id }} — {{ map.name }}{{ map.is_city ? ' (wyczerpanie już wyłączone)' : '' }}</span>
                    </label>
                </div>
                <Message v-else severity="info">Nie znaleziono powiązanych wnętrz.</Message>
            </template>
            <Message v-for="(message, field) in form.errors" :key="field" severity="error">{{ message }}</Message>
            <template #footer>
                <Button label="Anuluj" severity="secondary" :disabled="form.processing" @click="visible = false" />
                <Button label="Potwierdź" :loading="form.processing" @click="save" />
            </template>
        </Dialog>
    </div>
</template>
