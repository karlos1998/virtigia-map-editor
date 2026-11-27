<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import type { DialogNodeEffectsResource } from '@/Resources/DialogNodeActionData.resource';

type AudioOption = { id: number; name: string };

const props = defineProps<{ effects?: DialogNodeEffectsResource | null }>();

const shakeEnabled = ref(false);
const shakeDuration = ref<number | null>(700);
const shakeIntensity = ref<number | null>(8);
const dimEnabled = ref(false);
const dimDuration = ref<number | null>(450);
const dimOpacity = ref<number | null>(65);
const dimUntilNextNode = ref(false);
const dimHold = ref<number | null>(1000);
const audio = ref<AudioOption | string | null>(null);
const audioOptions = ref<AudioOption[]>([]);
const validationError = ref('');
const audioSearchError = ref('');
let searchRequest: AbortController | undefined;

watch(() => props.effects, (effects, _, onCleanup) => {
    shakeEnabled.value = !!effects?.shake;
    shakeDuration.value = effects?.shake?.durationMs ?? 700;
    shakeIntensity.value = effects?.shake?.intensity ?? 8;
    dimEnabled.value = !!effects?.dim;
    dimDuration.value = effects?.dim?.durationMs ?? 450;
    dimOpacity.value = effects?.dim?.opacity ?? 65;
    dimUntilNextNode.value = !!effects?.dim && effects.dim.holdMs == null;
    dimHold.value = effects?.dim?.holdMs ?? 1000;

    const id = effects?.sound?.audioId;
    audio.value = id == null ? null : {id, name: 'Zapisany plik audio'};
    if (id == null) return;

    const request = new AbortController();
    const pending = audio.value;
    onCleanup(() => request.abort());
    axios.get<AudioOption>(route('audios.fetch', {audio: id}), {signal: request.signal})
        .then(({data}) => {
            if (audio.value === pending) audio.value = data;
        })
        .catch(() => {
            if (!request.signal.aborted && audio.value === pending) {
                audio.value = {id, name: 'Plik niedostępny'};
            }
        });
}, {immediate: true});

async function searchAudio({query}: {query: string}): Promise<void> {
    searchRequest?.abort();
    const request = new AbortController();
    searchRequest = request;
    audioSearchError.value = '';
    try {
        const {data} = await axios.get<AudioOption[]>(route('audios.search'), {
            params: {query, browse: 1},
            signal: request.signal,
        });
        if (!request.signal.aborted) audioOptions.value = data;
    } catch {
        if (!request.signal.aborted) {
            audioOptions.value = [];
            audioSearchError.value = 'Nie udało się pobrać plików audio.';
        }
    }
}

onBeforeUnmount(() => searchRequest?.abort());

function isWithin(value: number | null, min: number, max: number): value is number {
    return value != null && Number.isInteger(value) && value >= min && value <= max;
}

function getPayload(): DialogNodeEffectsResource | null | false {
    validationError.value = '';
    const effects: DialogNodeEffectsResource = {};
    if (shakeEnabled.value) {
        if (!isWithin(shakeDuration.value, 100, 5000) || !isWithin(shakeIntensity.value, 1, 32)) {
            validationError.value = 'Podaj czas wstrząsu od 100 do 5000 ms i siłę od 1 do 32 px.';
            return false;
        }
        effects.shake = {durationMs: shakeDuration.value, intensity: shakeIntensity.value};
    }
    if (dimEnabled.value) {
        const holdMs = dimUntilNextNode.value ? null : dimHold.value;
        if (!isWithin(dimDuration.value, 100, 5000) || !isWithin(dimOpacity.value, 1, 100)
            || (!dimUntilNextNode.value && !isWithin(holdMs, 0, 10000))) {
            validationError.value = 'Podaj czas przejścia od 100 do 5000 ms, krycie od 1 do 100% i czas utrzymania od 0 do 10000 ms.';
            return false;
        }
        effects.dim = {durationMs: dimDuration.value, opacity: dimOpacity.value, holdMs};
    }
    if (typeof audio.value === 'string' && audio.value.trim()) {
        validationError.value = 'Wybierz plik audio z listy.';
        return false;
    }
    if (audio.value && typeof audio.value !== 'string') {
        effects.sound = {audioId: audio.value.id};
    }
    return Object.keys(effects).length ? effects : null;
}

defineExpose({getPayload});
</script>

<template>
    <fieldset class="flex flex-col gap-4 rounded-lg border border-surface-200 p-3 dark:border-surface-700">
        <legend class="text-lg font-semibold">Efekty po wejściu w węzeł</legend>

        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2">
                <Checkbox v-model="shakeEnabled" inputId="dialog-shake-enabled" binary />
                <label for="dialog-shake-enabled">Wstrząs mapy</label>
            </div>
            <div v-if="shakeEnabled" class="grid gap-3 sm:grid-cols-2">
                <div class="flex flex-col gap-1">
                    <label for="dialog-shake-duration">Czas wstrząsu (ms)</label>
                    <InputNumber v-model="shakeDuration" inputId="dialog-shake-duration" :min="100" :max="5000" :useGrouping="false" fluid />
                </div>
                <div class="flex flex-col gap-1">
                    <label for="dialog-shake-intensity">Siła (px)</label>
                    <InputNumber v-model="shakeIntensity" inputId="dialog-shake-intensity" :min="1" :max="32" fluid />
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2">
                <Checkbox v-model="dimEnabled" inputId="dialog-dim-enabled" binary />
                <label for="dialog-dim-enabled">Przyciemnienie mapy</label>
            </div>
            <template v-if="dimEnabled">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <label for="dialog-dim-duration">Czas przejścia (ms)</label>
                        <InputNumber v-model="dimDuration" inputId="dialog-dim-duration" :min="100" :max="5000" :useGrouping="false" fluid />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="dialog-dim-opacity">Krycie (%)</label>
                        <InputNumber v-model="dimOpacity" inputId="dialog-dim-opacity" :min="1" :max="100" fluid />
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox v-model="dimUntilNextNode" inputId="dialog-dim-until-next" binary />
                    <label for="dialog-dim-until-next">Utrzymaj do następnego węzła lub zamknięcia dialogu</label>
                </div>
                <div v-if="!dimUntilNextNode" class="flex flex-col gap-1">
                    <label for="dialog-dim-hold">Czas utrzymania po przyciemnieniu (ms)</label>
                    <InputNumber v-model="dimHold" inputId="dialog-dim-hold" :min="0" :max="10000" :useGrouping="false" fluid />
                </div>
            </template>
        </div>

        <div class="flex flex-col gap-2">
            <label for="dialog-effect-audio">Dźwięk</label>
            <div class="flex flex-wrap items-center gap-2">
                <AutoComplete
                    v-model="audio"
                    inputId="dialog-effect-audio"
                    :suggestions="audioOptions"
                    :optionLabel="(option: AudioOption) => `[${option.id}] ${option.name}`"
                    placeholder="Wybierz plik z zakładki Audio"
                    dropdown
                    fluid
                    class="min-w-0 flex-1"
                    @complete="searchAudio"
                />
                <Button v-if="audio" type="button" label="Usuń dźwięk" severity="secondary" text @click="audio = null" />
            </div>
            <Message v-if="audioSearchError" severity="error" :closable="false">{{ audioSearchError }}</Message>
        </div>
        <Message v-if="validationError" severity="error" :closable="false">{{ validationError }}</Message>
    </fieldset>
</template>
