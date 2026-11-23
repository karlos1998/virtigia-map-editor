<script setup lang="ts">
import {watch} from 'vue';
import {useForm} from '@inertiajs/vue3';
import {useToast} from 'primevue';
import {route} from 'ziggy-js';
import type {MapResource, MapWeatherSettings} from '@/Resources/Map.resource';

const props = defineProps<{map: MapResource}>();
const toast = useToast();
const initialSettings = (): Required<MapWeatherSettings> => ({
    rain: {enabled: false, density: .5, speed: 1, wind: .2, ...props.map.weather_settings?.rain},
    fog: {enabled: false, opacity: .45, speedX: .14, speedY: -.05, color: '#dce1e6', ...props.map.weather_settings?.fog},
});
const form = useForm({weather_settings: initialSettings()});
const rainFields = [
    {key: 'density', label: 'Gęstość deszczu', min: 0, max: 1, step: .05},
    {key: 'speed', label: 'Prędkość opadania', min: .1, max: 3, step: .1},
    {key: 'wind', label: 'Wiatr (minus w lewo, plus w prawo)', min: -2, max: 2, step: .1},
] as const;
const fogFields = [
    {key: 'opacity', label: 'Krycie mgły', min: 0, max: 1, step: .05},
    {key: 'speedX', label: 'Ruch poziomy (minus w lewo, plus w prawo)', min: -1, max: 1, step: .05},
    {key: 'speedY', label: 'Ruch pionowy (minus w górę, plus w dół)', min: -1, max: 1, step: .05},
] as const;

watch(() => [props.map.id, props.map.weather_settings], () => {
    form.defaults({weather_settings: initialSettings()});
    form.reset();
});

function save() {
    form.transform(data => ({weather_settings:
        data.weather_settings.rain.enabled || data.weather_settings.fog.enabled ? data.weather_settings : null,
    })).patch(route('maps.update.weather-settings', props.map.id), {
        preserveScroll: true,
        onSuccess: () => toast.add({severity: 'success', summary: 'Zapisano', detail: 'Pogoda na mapie została zapisana.', life: 3000}),
    });
}
</script>

<template>
    <form class="card flex flex-col gap-4" aria-labelledby="map-weather-heading" @submit.prevent="save">
        <h2 id="map-weather-heading" class="m-0 text-xl font-semibold">Pogoda na mapie</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <fieldset class="flex flex-col gap-3 rounded border border-surface-200 p-4 dark:border-surface-700">
                <legend class="px-1 font-semibold">Deszcz</legend>
                <div class="flex items-center gap-2">
                    <Checkbox v-model="form.weather_settings.rain.enabled" inputId="map-rain-enabled" binary />
                    <label for="map-rain-enabled">Włącz deszcz</label>
                </div>
                <template v-if="form.weather_settings.rain.enabled">
                    <div v-for="field in rainFields" :key="field.key" class="flex flex-col gap-1">
                        <label :for="`map-rain-${field.key}`">{{ field.label }}</label>
                        <InputNumber v-model="form.weather_settings.rain[field.key]" :inputId="`map-rain-${field.key}`" :min="field.min" :max="field.max" :step="field.step" :maxFractionDigits="2" :useGrouping="false" showButtons />
                    </div>
                </template>
            </fieldset>
            <fieldset class="flex flex-col gap-3 rounded border border-surface-200 p-4 dark:border-surface-700">
                <legend class="px-1 font-semibold">Mgła</legend>
                <div class="flex items-center gap-2">
                    <Checkbox v-model="form.weather_settings.fog.enabled" inputId="map-fog-enabled" binary />
                    <label for="map-fog-enabled">Włącz mgłę</label>
                </div>
                <template v-if="form.weather_settings.fog.enabled">
                    <div v-for="field in fogFields" :key="field.key" class="flex flex-col gap-1">
                        <label :for="`map-fog-${field.key}`">{{ field.label }}</label>
                        <InputNumber v-model="form.weather_settings.fog[field.key]" :inputId="`map-fog-${field.key}`" :min="field.min" :max="field.max" :step="field.step" :maxFractionDigits="2" :useGrouping="false" showButtons />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="map-fog-color">Kolor mgły</label>
                        <input id="map-fog-color" v-model="form.weather_settings.fog.color" type="color" name="fog-color" class="h-12 w-full cursor-pointer rounded border border-surface-300 dark:border-surface-600" />
                    </div>
                </template>
            </fieldset>
        </div>
        <ul v-if="Object.keys(form.errors).length" class="m-0 list-inside list-disc text-red-600 dark:text-red-400" role="alert">
            <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
        </ul>
        <Button type="submit" label="Zapisz pogodę" icon="pi pi-save" :loading="form.processing" :disabled="form.processing" class="self-start" />
    </form>
</template>
