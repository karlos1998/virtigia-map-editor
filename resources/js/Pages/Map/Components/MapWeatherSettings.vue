<script setup lang="ts">
import {watch} from 'vue';
import {useForm} from '@inertiajs/vue3';
import {useToast} from 'primevue';
import {route} from 'ziggy-js';
import type {MapResource, MapWeatherSettings, MapParticleWeatherName, MapParticleWeatherSettings} from '@/Resources/Map.resource';

const props = defineProps<{map: MapResource}>();
const toast = useToast();
const particleEffects = [
    {key: 'rain', label: 'Deszcz', wind: .2},
    {key: 'snow', label: 'Śnieg', wind: .2},
    {key: 'bubbles', label: 'Bąbelki', wind: .1},
    {key: 'fish', label: 'Ryby', wind: .2},
    {key: 'bats', label: 'Nietoperze', wind: .2},
    {key: 'halloweenBats', label: 'Nietoperze halloweenowe', wind: .2},
    {key: 'lights', label: 'Świetlne cząsteczki', wind: .2},
    {key: 'lanterns', label: 'Lampiony', wind: .2},
] as const;
const overlayEffects = [{key: 'fog', label: 'Mgła'}, {key: 'clouds', label: 'Chmury'}] as const;
const initialSettings = (): Required<MapWeatherSettings> => ({
    ...Object.fromEntries(particleEffects.map(effect => [effect.key, {
        enabled: false, density: .5, speed: 1, wind: effect.wind, ...props.map.weather_settings?.[effect.key],
    }])) as Record<MapParticleWeatherName, MapParticleWeatherSettings>,
    fog: {enabled: false, opacity: .45, speedX: .14, speedY: -.05, color: '#dce1e6', ...props.map.weather_settings?.fog},
    clouds: {enabled: false, opacity: .45, speedX: .35, speedY: .06, color: '#ebf2ff', ...props.map.weather_settings?.clouds},
});
const form = useForm({weather_settings: initialSettings()});
const particleFields = [
    {key: 'density', label: 'Gęstość', min: 0, max: 1, step: .05},
    {key: 'speed', label: 'Prędkość ruchu', min: .1, max: 3, step: .1},
    {key: 'wind', label: 'Wiatr (minus w lewo, plus w prawo)', min: -2, max: 2, step: .1},
] as const;
const overlayFields = [
    {key: 'opacity', label: 'Krycie', min: 0, max: 1, step: .05},
    {key: 'speedX', label: 'Ruch poziomy (minus w lewo, plus w prawo)', min: -1, max: 1, step: .05},
    {key: 'speedY', label: 'Ruch pionowy (minus w górę, plus w dół)', min: -1, max: 1, step: .05},
] as const;

watch(() => [props.map.id, props.map.weather_settings], () => {
    form.defaults({weather_settings: initialSettings()});
    form.reset();
});

function save() {
    form.patch(route('maps.update.weather-settings', props.map.id), {
        preserveScroll: true,
        onSuccess: () => toast.add({severity: 'success', summary: 'Zapisano', detail: 'Pogoda na mapie została zapisana.', life: 3000}),
    });
}
</script>

<template>
    <form class="card flex flex-col gap-4" aria-labelledby="map-weather-heading" @submit.prevent="save">
        <h2 id="map-weather-heading" class="m-0 text-xl font-semibold">Pogoda na mapie</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <fieldset v-for="effect in particleEffects" :key="effect.key" class="flex flex-col gap-3 rounded border border-surface-200 p-4 dark:border-surface-700">
                <legend class="px-1 font-semibold">{{ effect.label }}</legend>
                <div class="flex items-center gap-2">
                    <Checkbox v-model="form.weather_settings[effect.key].enabled" :inputId="`map-${effect.key}-enabled`" binary />
                    <label :for="`map-${effect.key}-enabled`">Włącz efekt</label>
                </div>
                <template v-if="form.weather_settings[effect.key].enabled">
                    <div v-for="field in particleFields" :key="field.key" class="flex flex-col gap-1">
                        <label :for="`map-${effect.key}-${field.key}`">{{ field.label }}</label>
                        <InputNumber v-model="form.weather_settings[effect.key][field.key]" :inputId="`map-${effect.key}-${field.key}`" :min="field.min" :max="field.max" :step="field.step" :maxFractionDigits="2" :useGrouping="false" showButtons />
                    </div>
                </template>
            </fieldset>
            <fieldset v-for="effect in overlayEffects" :key="effect.key" class="flex flex-col gap-3 rounded border border-surface-200 p-4 dark:border-surface-700">
                <legend class="px-1 font-semibold">{{ effect.label }}</legend>
                <div class="flex items-center gap-2">
                    <Checkbox v-model="form.weather_settings[effect.key].enabled" :inputId="`map-${effect.key}-enabled`" binary />
                    <label :for="`map-${effect.key}-enabled`">Włącz efekt</label>
                </div>
                <template v-if="form.weather_settings[effect.key].enabled">
                    <div v-for="field in overlayFields" :key="field.key" class="flex flex-col gap-1">
                        <label :for="`map-${effect.key}-${field.key}`">{{ field.label }}</label>
                        <InputNumber v-model="form.weather_settings[effect.key][field.key]" :inputId="`map-${effect.key}-${field.key}`" :min="field.min" :max="field.max" :step="field.step" :maxFractionDigits="2" :useGrouping="false" showButtons />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label :for="`map-${effect.key}-color`">Kolor</label>
                        <input :id="`map-${effect.key}-color`" v-model="form.weather_settings[effect.key].color" type="color" :name="`${effect.key}-color`" class="h-12 w-full cursor-pointer rounded border border-surface-300 dark:border-surface-600" />
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
