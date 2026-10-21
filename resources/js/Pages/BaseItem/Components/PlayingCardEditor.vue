<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { PlayingCardIcon } from 'virtigia-tips';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import axios from 'axios';
import { route } from 'ziggy-js';

const props = defineProps<{
    name: string;
    catalog: Array<{ label: string; card: Record<string, any> }>;
}>();
const attributes = defineModel<Record<string, any>>('attributes', { required: true });
const reference = ref<Record<string, any> | null>(null);
const artUrls = ref<Record<string, string>>({});
const previewError = ref('');
const generating = ref(false);
const uploading = ref(false);
async function uploadImage(event: Event, field: 'illustrationSrc' | 'backgroundSrc') {
    const input = event.target as HTMLInputElement;
    const image = input.files?.[0];
    if (!image || !card.value) return;
    uploading.value = true;
    previewError.value = '';
    try {
        const form = new FormData();
        form.append('image', image);
        const {data} = await axios.post(route('assets.playing-card-image'), form);
        card.value.artwork ??= {frameX: 0, frameY: 0};
        card.value.artwork[field] = data.path;
    } catch {
        previewError.value = 'Nie udało się przesłać grafiki. Użyj PNG, GIF, JPG lub WebP do 5 MB i 4096 × 4096 pikseli.';
    } finally {
        uploading.value = false;
        input.value = '';
    }
}
async function generateArtwork() {
    if (!card.value) return;
    generating.value = true;
    previewError.value = '';
    try {
        const { data } = await axios.post(route('assets.playing-card-artwork'), {card: card.value});
        card.value.artwork = data.artwork;
    } catch (error: any) {
        previewError.value = error?.response?.data?.errors?.card?.[0] ?? 'Nie udało się dobrać grafik z S3.';
    } finally {
        generating.value = false;
    }
}
const card = computed(() => attributes.value.playingCard);
const kinds = [
    { label: 'Jednostka', value: 'unit' }, { label: 'Specjalna', value: 'special' },
    { label: 'Pogoda', value: 'weather' }, { label: 'Dowódca', value: 'leader' },
];
const rows = [
    { label: 'Zwarcie', value: 'close' }, { label: 'Dystans', value: 'ranged' }, { label: 'Oblężenie', value: 'siege' },
];
const abilities = [
    ['spy', 'Szpieg'], ['medic', 'Medyk'], ['bond', 'Więź'], ['morale', 'Morale'],
    ['horn', 'Róg'], ['muster', 'Braterstwo'], ['scorch', 'Pożoga'], ['scorch_c', 'Pożoga zwarcia'],
    ['avenger', 'Przemiana po usunięciu'], ['frost', 'Mróz'], ['fog', 'Mgła'], ['rain', 'Deszcz'],
    ['storm', 'Sztorm'], ['clear', 'Czyste niebo'], ['decoy', 'Manekin'],
    ['foltest_king', 'Dowódca: mgła z talii'], ['foltest_lord', 'Dowódca: usuń pogodę'],
    ['foltest_siegemaster', 'Dowódca: róg oblężenia'], ['foltest_son', 'Dowódca: pożoga zwarcia'],
    ['foltest_steelforged', 'Dowódca: pożoga oblężenia'],
].map(([value, label]) => ({ value, label }));
const eligibleAbilities = computed(() => {
    const allowed: Record<string, string[]> = {
        unit: ['spy', 'medic', 'bond', 'morale', 'horn', 'muster', 'scorch', 'scorch_c', 'avenger'],
        special: ['decoy', 'horn', 'scorch'],
        weather: ['frost', 'fog', 'rain', 'storm', 'clear'],
        leader: ['foltest_king', 'foltest_lord', 'foltest_siegemaster', 'foltest_son', 'foltest_steelforged'],
    };
    return abilities.filter(ability => (allowed[card.value?.kind] ?? []).includes(ability.value));
});

function initialize() {
    attributes.value = { ...attributes.value, playingCard: {
        definitionId: '', deck: 'retro', kind: 'unit', strength: 0, hero: false,
        rows: ['close'], abilities: [], bondGroup: null, musterTargets: [], avengerTarget: null,
        generatedOnly: false, artwork: { outfitSrc: null, backgroundSrc: null, illustrationSrc: null, frameX: 0, frameY: 0 },
    } };
}
function applyReference() {
    if (!reference.value) return;
    attributes.value = { ...attributes.value, playingCard: {
        ...JSON.parse(JSON.stringify(reference.value)),
        artwork: card.value?.artwork ?? reference.value.artwork,
    } };
}
const selectedAbilities = computed(() => (card.value?.abilities ?? []).map((key: string) =>
    abilities.find(ability => ability.value === key)?.label ?? key));
const rowLabel = computed(() => (card.value?.rows ?? []).map((key: string) =>
    rows.find(row => row.value === key)?.label ?? key).join(' / '));
const musterTargets = computed({
    get: () => (card.value?.musterTargets ?? []).join(', '),
    set: (value: string) => { card.value.musterTargets = value.split(',').map(id => id.trim()).filter(Boolean); },
});
let previewRevision = 0;
watch(() => card.value?.artwork, async artwork => {
    const revision = ++previewRevision;
    previewError.value = '';
    const urls: Record<string, string> = {};
    try {
        for (const key of ['outfitSrc', 'backgroundSrc', 'illustrationSrc']) {
            const path = artwork?.[key];
            if (!path) continue;
            if (/^https?:\/\//i.test(path)) { urls[key] = path; continue; }
            const { data } = await axios.get(route('assets.sign-url'), { params: { path } });
            urls[key] = data.url;
        }
        if (revision === previewRevision) artUrls.value = urls;
    } catch {
        if (revision === previewRevision) previewError.value = 'Nie udało się wczytać grafiki. Podaj ścieżkę rozpoczynającą się od img/.';
    }
}, { deep: true, immediate: true });
watch(() => card.value?.kind, kind => {
    if (card.value) card.value.abilities = (card.value.abilities ?? []).filter((ability: string) => eligibleAbilities.value.some(option => option.value === ability));
    if (card.value && kind !== 'unit') {
        card.value.strength = null;
        card.value.hero = false;
        card.value.rows = [];
    }
});
</script>

<template>
    <section class="flex flex-col gap-5">
        <h3>Karta do gry</h3>
        <div class="flex flex-wrap items-center gap-3">
            <Dropdown v-model="reference" :options="catalog" option-label="label" option-value="card" filter placeholder="Mechanika karty z archiwum" />
            <Button label="Zastosuj mechanikę" :disabled="!reference" @click="applyReference" />
            <Button v-if="!card" label="Utwórz własną kartę" severity="secondary" @click="initialize" />
        </div>
        <div v-if="card" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="flex flex-col gap-4">
                <label class="flex flex-col gap-2">Identyfikator definicji<InputText v-model="card.definitionId" /></label>
                <label class="flex flex-col gap-2">Talia<InputText v-model="card.deck" /></label>
                <label class="flex flex-col gap-2">Typ<Dropdown v-model="card.kind" :options="kinds" option-label="label" option-value="value" /></label>
                <label v-if="card.kind === 'unit'" class="flex flex-col gap-2">Siła karty<InputNumber v-model="card.strength" :min="0" :max="100" /></label>
                <label v-if="card.kind === 'unit'" class="flex items-center gap-2"><Checkbox v-model="card.hero" binary />Bohater</label>
                <label v-if="card.kind === 'unit'" class="flex flex-col gap-2">Dozwolone rzędy<MultiSelect v-model="card.rows" :options="rows" option-label="label" option-value="value" /></label>
                <label class="flex flex-col gap-2">Zdolności<MultiSelect v-model="card.abilities" :options="eligibleAbilities" option-label="label" option-value="value" /></label>
                <label v-if="card.abilities.includes('bond')" class="flex flex-col gap-2">Grupa więzi<InputText v-model="card.bondGroup" /></label>
                <label v-if="card.abilities.includes('muster')" class="flex flex-col gap-2">Przywoływane definicje (po przecinku)<InputText v-model="musterTargets" /></label>
                <label v-if="card.abilities.includes('avenger')" class="flex flex-col gap-2">Definicja karty po przemianie<InputText v-model="card.avengerTarget" /></label>
                <label class="flex items-center gap-2"><Checkbox v-model="card.generatedOnly" binary />Powstaje wyłącznie podczas gry</label>
                <template v-if="card.artwork">
                    <Button label="Dobierz outfit i tło automatycznie" :loading="generating" @click="generateArtwork" />
                    <label class="flex flex-col gap-2">Arkusz outfitu<InputText v-model="card.artwork.outfitSrc" placeholder="img/outfits/..." /></label>
                    <label class="flex flex-col gap-2">Tło<InputText v-model="card.artwork.backgroundSrc" placeholder="img/..." /></label>
                    <label class="flex flex-col gap-2">Własna ilustracja zamiast outfitu<InputText v-model="card.artwork.illustrationSrc" placeholder="img/..." /></label>
                    <label class="flex flex-col gap-2">Prześlij własną ilustrację<input type="file" accept="image/png,image/gif,image/jpeg,image/webp" :disabled="uploading" @change="uploadImage($event, 'illustrationSrc')" /></label>
                    <label class="flex flex-col gap-2">Prześlij własne tło<input type="file" accept="image/png,image/gif,image/jpeg,image/webp" :disabled="uploading" @change="uploadImage($event, 'backgroundSrc')" /></label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex flex-col gap-2">Kolumna klatki<InputNumber v-model="card.artwork.frameX" :min="0" :max="3" /></label>
                        <label class="flex flex-col gap-2">Wiersz klatki<InputNumber v-model="card.artwork.frameY" :min="0" :max="3" /></label>
                    </div>
                </template>
                <p v-if="previewError" role="alert" class="text-red-600">{{ previewError }}</p>
            </div>
            <div class="flex flex-col items-center gap-4">
                <article class="playing-card" :class="{ 'playing-card--hero': card.hero }">
                    <div class="playing-card__art" :style="{ backgroundImage: artUrls.backgroundSrc ? `url('${artUrls.backgroundSrc}')` : undefined }">
                        <img v-if="artUrls.illustrationSrc" :src="artUrls.illustrationSrc" alt="Ilustracja karty" class="playing-card__illustration" />
                        <div v-else-if="artUrls.outfitSrc" class="playing-card__sprite" :style="{ backgroundImage: `url('${artUrls.outfitSrc}')`, backgroundPosition: `${-(card.artwork?.frameX ?? 0) * 32}px ${-(card.artwork?.frameY ?? 0) * 48}px` }" />
                        <span class="playing-card__strength">{{ card.kind === 'unit' ? card.strength : '◆' }}</span>
                        <div class="playing-card__badges">
                            <PlayingCardIcon v-for="row in card.rows" :key="row" :type="row" class="playing-card__badge playing-card__badge--row" />
                            <PlayingCardIcon v-for="ability in card.abilities" :key="ability" :type="ability" class="playing-card__badge playing-card__badge--ability" />
                        </div>
                        <span v-if="card.hero" class="playing-card__hero">★</span>
                        <span class="playing-card__deck">{{ card.deck }}</span>
                    </div>
                    <div class="playing-card__footer">
                        <strong>{{ name }}</strong>
                        <span>{{ rowLabel || kinds.find(kind => kind.value === card.kind)?.label }}</span>
                        <span v-for="ability in selectedAbilities" :key="ability">{{ ability }}</span>
                        <p>{{ attributes.description }}</p>
                    </div>
                </article>
                <p class="text-sm">Nazwa i opis pochodzą z przedmiotu. Własna ilustracja zachowuje oznaczenia szablonu.</p>
            </div>
        </div>
    </section>
</template>

<style scoped>
.playing-card{width:240px;max-width:100%;overflow:hidden;border:5px solid #536a86;border-radius:14px;background:#e9d9b7;color:#2d251c;box-shadow:0 8px 22px #0005}
.playing-card--hero{border-color:#d5a53c;box-shadow:0 0 0 2px #815c1b,0 8px 22px #0005}
.playing-card__art{position:relative;height:260px;background-color:#343b38;background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center}
.playing-card__sprite{width:32px;height:48px;transform:scale(3);image-rendering:pixelated;background-repeat:no-repeat}
.playing-card__illustration{width:100%;height:100%;object-fit:cover}
.playing-card__strength{position:absolute;left:8px;top:8px;background:#18212c;border:2px solid #b8a787;border-radius:50%;width:46px;height:46px;display:grid;place-items:center;color:#fff;font:bold 24px Georgia,serif}
.playing-card--hero .playing-card__strength{border:3px solid #edc35e}
.playing-card__hero{position:absolute;right:12px;top:12px;color:#edc35e;font-size:26px}
.playing-card__deck{position:absolute;bottom:8px;left:8px;background:#233957;color:#e9d9b7;padding:3px 9px;border-radius:4px;text-transform:uppercase}
.playing-card__footer{padding:12px;display:flex;flex-direction:column;gap:6px;text-align:center;min-height:140px;overflow-wrap:anywhere}
.playing-card__footer strong{font:bold 20px Georgia,serif}.playing-card__footer span{font-size:12px}.playing-card__footer p{font:italic 13px Georgia,serif;white-space:pre-wrap}
.playing-card__badges{position:absolute;top:64px;left:12px;display:flex;flex-direction:column;gap:7px;max-height:166px;flex-wrap:wrap;align-content:flex-start}
.playing-card__badge{box-sizing:border-box;width:36px;height:36px;padding:6px;border:2px solid #dbc89c;border-radius:50%;box-shadow:0 2px 4px #0008}
.playing-card__badge--row{background:#e9b64d;color:#18212c}
.playing-card__badge--ability{background:#18212c;color:#fff5d8}
</style>
