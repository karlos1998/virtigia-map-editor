<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import { computed, ref } from "vue";
import { route } from "ziggy-js";
import { Link, router } from "@inertiajs/vue3";
import { useToast } from "primevue";
import type {
    WalkthroughItemReference,
    WalkthroughLocation,
    WalkthroughNpc,
    WalkthroughResource,
    WalkthroughSection,
    WalkthroughStepRef,
    WalkthroughTimelineEntry,
} from "@/Resources/QuestWalkthrough.resource";

const props = defineProps<{
    quest: { id: number; name: string; updated_at: string };
    walkthrough: WalkthroughResource | null;
    meta: {
        available: boolean;
        live_preview: boolean;
        is_public: boolean;
        visibility_available: boolean;
        generated_at: string | null;
        checked_at: string | null;
        outdated_generator: boolean;
        world_status: { last_checked_at?: string; last_generated_at?: string };
    };
}>();

const toast = useToast();
const regenerating = ref(false);
const showNpcLines = ref(false);
const isPublic = ref(props.meta.is_public);

const updateVisibility = () => {
    router.patch(route("quests.walkthrough.visibility", { quest: props.quest.id }), { is_public: isPublic.value }, {
        preserveScroll: true,
        onSuccess: () => toast.add({
            severity: "success",
            summary: "Zapisano",
            detail: isPublic.value ? "Solucja będzie widoczna na wiki." : "Solucja jest ukryta przed wiki.",
            life: 3000,
        }),
        onError: () => {
            isPublic.value = !isPublic.value;
            toast.add({ severity: "error", summary: "Błąd", detail: "Nie udało się zmienić widoczności solucji.", life: 6000 });
        },
    });
};

const regenerate = () => {
    regenerating.value = true;
    router.post(route("quests.walkthrough.regenerate", { quest: props.quest.id }), {}, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: "success", summary: "Gotowe", detail: "Solucja została wygenerowana od nowa.", life: 3000 }),
        onError: () => toast.add({ severity: "error", summary: "Błąd", detail: "Nie udało się wygenerować solucji.", life: 6000 }),
        onFinish: () => (regenerating.value = false),
    });
};

const items = computed<Record<string, WalkthroughItemReference>>(() => (props.walkthrough?.references?.items as Record<string, WalkthroughItemReference>) ?? {});
const item = (id: number): WalkthroughItemReference => items.value[id] ?? { id, name: `Przedmiot #${id}`, src: null, sources: [], sources_total: 0 };

const summary = computed(() => props.walkthrough?.summary);

const statusTag = computed(() => {
    switch (props.walkthrough?.status) {
        case "complete":
            return { severity: "success", label: "Kompletna" };
        case "partial":
            return { severity: "warn", label: "Niepełna" };
        default:
            return { severity: "secondary", label: "Brak ścieżki" };
    }
});

const numberFormat = new Intl.NumberFormat("pl-PL");
const fmt = (value: number | null | undefined) => numberFormat.format(value ?? 0);
const date = (value: string | null | undefined) => (value ? new Date(value).toLocaleString("pl-PL") : "—");

const duration = (seconds: number) => {
    if (seconds < 60) return `${seconds} s`;
    const minutes = Math.round(seconds / 60);
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;
    return rest ? `${hours} h ${rest} min` : `${hours} h`;
};

const cleanText = (value: string | null | undefined) =>
    (value ?? "")
        .replace(/\[(\/?)(b|i|u|br|img|href[^\]]*)\]/gi, "")
        .replace(/#nick/gi, "[twój nick]")
        .replace(/#lvl/gi, "[twój poziom]")
        .replace(/#difflvl\(\d+\)/gi, "[różnica poziomów]")
        .trim();

const loc = (location: WalkthroughLocation) => `${location.map_name} (${location.x},${location.y})`;

const chooseEntries = (timeline: WalkthroughTimelineEntry[]) => timeline.filter((entry) => entry.type === "choose");

const sectionTitle = (section: WalkthroughSection): string => {
    if (section.type === "wait") return "Oczekiwanie";
    if (section.type === "next_day") return "Następny dzień";
    if (section.type === "kill") return section.step?.name ?? "Walka";
    if (section.purpose === "item" && section.item_id) return `Zdobycie przedmiotu: ${item(section.item_id).name}`;
    const npc = section.npcs[0]?.name;
    return npc ?? section.dialog_name ?? "Rozmowa";
};

const reachedStep = (section: WalkthroughSection): WalkthroughStepRef | null =>
    section.type === "dialog" ? section.reached_steps?.[section.reached_steps.length - 1] ?? null : null;

const actionLabels: Record<string, string> = {
    BATTLE: "Rozpocznie się walka.",
    KILL_AND_LOOT: "Rozpocznie się walka (z łupem).",
    KILL: "NPC zostanie zabity.",
    SELF_KILL: "Twoja postać zginie.",
    HEAL: "Postać zostanie uleczona.",
    SUBTRACT_EXP: "Stracisz część doświadczenia.",
    SHOW_MAIL: "Otworzy się poczta.",
    SHOW_DEPOSIT: "Otworzy się depozyt.",
    SHOW_CLAN_DEPOSIT: "Otworzy się depozyt klanowy.",
    SHOW_AUCTIONS: "Otworzy się dom aukcyjny.",
};

const eventEntries = (entry: WalkthroughTimelineEntry) => !["choose", "condition"].includes(entry.type);

const npcLocationsText = (npc: WalkthroughNpc) => {
    if (!npc.locations.length) return "brak rozmieszczenia na mapach";
    const shown = npc.locations.map(loc).join(", ");
    return npc.locations_total > npc.locations.length ? `${shown} i ${npc.locations_total - npc.locations.length} innych` : shown;
};

const sourceLabel = (source: any) => {
    const where = source.location ? ` — ${source.location.map_name} (${source.location.x},${source.location.y})` : "";
    switch (source.type) {
        case "shop":
            return `Sklep${source.npc ? `: ${source.npc.name}` : source.shop ? `: ${source.shop.name}` : ""}${where}`;
        case "loot":
            return `Łup z: ${source.npc?.name ?? source.base_npc?.name ?? "potwora"}${where}`;
        case "dialog":
            return `Dialog${source.npc ? `: ${source.npc.name}` : ""}${where}`;
        default:
            return `${source.type}${source.npc ? `: ${source.npc.name}` : ""}${where}`;
    }
};

const referencedItems = computed(() => Object.values(items.value));
</script>

<template>
    <AppLayout>
        <div class="card">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="min-w-0">
                    <Link :href="route('quests.show', { quest: quest.id })" class="text-sm text-primary-600 hover:underline">
                        <i class="pi pi-arrow-left mr-1 text-xs"></i>Wróć do questa
                    </Link>
                    <h1 class="mt-2 text-2xl font-bold">{{ quest.name }} — solucja</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-surface-600 dark:text-surface-300">
                        <Tag v-if="walkthrough" :severity="statusTag.severity" :value="statusTag.label" />
                        <Tag v-if="meta.visibility_available && !isPublic" severity="contrast" value="Ukryta przed wiki" />
                        <span>Wygenerowano: {{ date(meta.generated_at) }}</span>
                        <span class="text-surface-400">•</span>
                        <span>Sprawdzono: {{ date(meta.checked_at) }}</span>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-3">
                    <div v-if="meta.visibility_available" class="flex items-center gap-2" title="Publiczne solucje może pokazywać wiki dla graczy">
                        <ToggleSwitch input-id="walkthrough-public" v-model="isPublic" @update:model-value="updateVisibility" />
                        <label for="walkthrough-public" class="text-sm">Jest publiczna</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <ToggleSwitch input-id="show-npc-lines" v-model="showNpcLines" />
                        <label for="show-npc-lines" class="text-sm">Kwestie NPC</label>
                    </div>
                    <Button
                        label="Generuj teraz"
                        icon="pi pi-refresh"
                        :loading="regenerating"
                        :disabled="!meta.available"
                        @click="regenerate"
                    />
                </div>
            </div>

            <Message v-if="!meta.available" severity="warn" :closable="false" class="mt-4">
                Podgląd na żywo — ten świat nie ma jeszcze tabeli <code>quest_walkthroughs</code>, więc solucja nie jest zapisywana. Uruchom migracje remote dla świata.
            </Message>
            <Message v-else-if="meta.live_preview" severity="info" :closable="false" class="mt-4">
                Podgląd na żywo — solucja nie została jeszcze zapisana. Worker sprawdza zmiany co godzinę; „Generuj teraz” zapisze ją od razu.
            </Message>
            <Message v-else-if="meta.outdated_generator" severity="info" :closable="false" class="mt-4">
                Solucja pochodzi ze starszej wersji generatora i zostanie odświeżona przy najbliższym przebiegu.
            </Message>
        </div>

        <template v-if="walkthrough && summary">
            <div class="card">
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div class="rounded-lg border border-surface-200 p-4 dark:border-surface-700">
                        <div class="text-xs font-semibold uppercase text-surface-500">Wymagany poziom</div>
                        <div class="mt-1 text-2xl font-bold">{{ summary.required_level ?? "—" }}</div>
                    </div>
                    <div class="rounded-lg border border-surface-200 p-4 dark:border-surface-700">
                        <div class="text-xs font-semibold uppercase text-surface-500">Potrzebne złoto</div>
                        <div class="mt-1 text-2xl font-bold">{{ fmt(summary.gold_cost) }}</div>
                    </div>
                    <div class="rounded-lg border border-surface-200 p-4 dark:border-surface-700">
                        <div class="text-xs font-semibold uppercase text-surface-500">Łączny czas oczekiwania</div>
                        <div class="mt-1 text-2xl font-bold">
                            {{ summary.wait_seconds ? `ok. ${duration(summary.wait_seconds)}` : "—" }}
                        </div>
                        <div v-if="summary.next_day_waits" class="text-xs text-surface-500">+ {{ summary.next_day_waits }}× do następnego dnia</div>
                    </div>
                    <div class="rounded-lg border border-surface-200 p-4 dark:border-surface-700">
                        <div class="text-xs font-semibold uppercase text-surface-500">Rozmowy</div>
                        <div class="mt-1 text-2xl font-bold">{{ summary.conversations }}</div>
                    </div>
                </div>

                <div class="mt-4 rounded-lg bg-green-50 p-4 dark:bg-green-950/30">
                    <div class="mb-2 text-sm font-semibold text-green-800 dark:text-green-300">Nagrody łącznie</div>
                    <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                        <span v-if="summary.rewards.exp"><strong>{{ fmt(summary.rewards.exp) }}</strong> doświadczenia</span>
                        <span v-if="summary.rewards.exp_percent"><strong>{{ summary.rewards.exp_percent }}%</strong> poziomu doświadczenia</span>
                        <span v-if="summary.rewards.gold"><strong>{{ fmt(summary.rewards.gold) }}</strong> złota</span>
                        <span v-if="summary.rewards.honor_points"><strong>{{ fmt(summary.rewards.honor_points) }}</strong> punktów honoru</span>
                        <span v-for="reward in summary.rewards.items" :key="reward.item_id" class="inline-flex items-center gap-1">
                            <img v-if="item(reward.item_id).src" :src="item(reward.item_id).src!" class="h-6 w-6" :alt="item(reward.item_id).name" />
                            <strong v-if="reward.quantity > 1">{{ reward.quantity }}×</strong> {{ item(reward.item_id).name }}
                        </span>
                    </div>
                </div>

                <Message v-for="warning in walkthrough.warnings" :key="warning" severity="warn" :closable="false" class="mt-4">
                    {{ warning }}
                </Message>
            </div>

            <div class="card">
                <ol class="flex flex-col gap-6">
                    <li v-for="(section, index) in walkthrough.sections" :key="index" class="flex gap-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 font-bold text-primary-700 dark:bg-primary-900/50 dark:text-primary-200">
                            {{ index + 1 }}
                        </div>

                        <div class="min-w-0 flex-1 border-b border-surface-200 pb-6 dark:border-surface-700">
                            <h2 class="text-lg font-semibold">{{ sectionTitle(section) }}</h2>

                            <!-- Dialog -->
                            <template v-if="section.type === 'dialog'">
                                <p v-if="reachedStep(section)?.description" class="mt-1 text-sm italic text-surface-500">
                                    {{ reachedStep(section)?.description }}
                                </p>

                                <div class="mt-2 flex flex-col gap-1">
                                    <div v-for="npc in section.npcs" :key="npc.name" class="flex items-center gap-2 text-sm">
                                        <img v-if="npc.src" :src="npc.src" :alt="npc.name" class="max-h-10 max-w-10 object-contain" />
                                        <span>
                                            Rozmawiamy z <strong>{{ npc.name }}</strong>: {{ npcLocationsText(npc) }}.
                                            <span v-if="npc.auto_start_dialog" class="text-surface-500">Rozmowa zaczyna się sama po podejściu.</span>
                                        </span>
                                    </div>
                                    <div v-if="!section.npcs.length" class="text-sm text-orange-600">
                                        Dialog „{{ section.dialog_name }}” nie jest przypisany do żadnego NPC na mapie.
                                    </div>
                                </div>

                                <div v-if="chooseEntries(section.timeline).length" class="mt-3 text-sm font-medium">Wybieramy kolejno:</div>
                                <div v-else class="mt-3 text-sm">Wystarczy rozpocząć rozmowę.</div>

                                <ol class="mt-2 flex flex-col gap-2 text-sm">
                                    <template v-for="(entry, entryIndex) in section.timeline" :key="entryIndex">
                                        <li v-if="entry.type === 'choose'" class="rounded-md bg-surface-50 px-3 py-2 dark:bg-surface-800">
                                            <div v-if="showNpcLines && entry.npc_text" class="mb-1 text-xs italic text-surface-500">
                                                NPC: „{{ cleanText(entry.npc_text) }}”
                                            </div>
                                            <div>
                                                <span class="mr-2 font-mono text-xs text-surface-400">{{ chooseEntries(section.timeline).indexOf(entry) + 1 }}.</span>
                                                „{{ cleanText(entry.label) || "…" }}”
                                            </div>
                                            <div v-if="entry.requirements?.length" class="mt-1 flex flex-wrap gap-1">
                                                <Tag v-for="requirement in entry.requirements" :key="requirement.text" severity="warn" :value="requirement.text" class="!text-xs" />
                                            </div>
                                        </li>
                                        <li v-else-if="entry.type === 'condition'" class="flex flex-wrap gap-1 pl-3">
                                            <Tag v-for="requirement in entry.requirements" :key="requirement.text" severity="secondary" :value="requirement.text" class="!text-xs" />
                                        </li>
                                        <li v-else-if="eventEntries(entry)" class="pl-3 text-sm">
                                            <span v-if="entry.type === 'step'" class="text-primary-600 dark:text-primary-300">
                                                <i class="pi pi-flag mr-1"></i>
                                                {{ entry.started ? "Rozpoczynamy zadanie" : "Postęp zadania" }}: {{ entry.step?.name }}
                                            </span>
                                            <span v-else-if="entry.type === 'reward_items'" class="text-green-700 dark:text-green-400">
                                                <i class="pi pi-gift mr-1"></i>Otrzymujemy:
                                                <span v-for="given in entry.items" :key="given.item_id" class="ml-1 inline-flex items-center gap-1">
                                                    <img v-if="item(given.item_id).src" :src="item(given.item_id).src!" class="h-5 w-5" alt="" />
                                                    {{ given.quantity > 1 ? `${given.quantity}× ` : "" }}{{ item(given.item_id).name }}
                                                </span>
                                            </span>
                                            <span v-else-if="entry.type === 'cost_items'" class="text-orange-700 dark:text-orange-400">
                                                <i class="pi pi-arrow-right-arrow-left mr-1"></i>Oddajemy:
                                                {{ entry.items.map((given) => `${given.quantity > 1 ? `${given.quantity}× ` : ""}${item(given.item_id).name}`).join(", ") }}
                                            </span>
                                            <span v-else-if="entry.type === 'reward_exp'" class="text-green-700 dark:text-green-400"><i class="pi pi-star mr-1"></i>+{{ fmt(entry.value) }} doświadczenia</span>
                                            <span v-else-if="entry.type === 'reward_exp_percent'" class="text-green-700 dark:text-green-400"><i class="pi pi-star mr-1"></i>+{{ entry.value }}% doświadczenia</span>
                                            <span v-else-if="entry.type === 'reward_gold'" class="text-green-700 dark:text-green-400"><i class="pi pi-wallet mr-1"></i>+{{ fmt(entry.value) }} złota</span>
                                            <span v-else-if="entry.type === 'reward_honor_points'" class="text-green-700 dark:text-green-400"><i class="pi pi-shield mr-1"></i>+{{ fmt(entry.value) }} punktów honoru</span>
                                            <span v-else-if="entry.type === 'reward_blessing'" class="text-green-700 dark:text-green-400"><i class="pi pi-sparkles mr-1"></i>Błogosławieństwo: {{ item(entry.item_id).name }}</span>
                                            <span v-else-if="entry.type === 'reward_outfit'" class="text-green-700 dark:text-green-400"><i class="pi pi-user mr-1"></i>Postać otrzymuje strój</span>
                                            <span v-else-if="entry.type === 'cost_gold'" class="text-orange-700 dark:text-orange-400"><i class="pi pi-wallet mr-1"></i>Płacimy {{ fmt(entry.value) }} złota</span>
                                            <span v-else-if="entry.type === 'cost_honor_points'" class="text-orange-700 dark:text-orange-400"><i class="pi pi-shield mr-1"></i>Wydajemy {{ fmt(entry.value) }} punktów honoru</span>
                                            <span v-else-if="entry.type === 'cost_dragon_tears'" class="text-orange-700 dark:text-orange-400">Wydajemy {{ fmt(entry.value) }} smoczych łez</span>
                                            <span v-else-if="entry.type === 'teleport'" class="text-blue-700 dark:text-blue-300">
                                                <i class="pi pi-map-marker mr-1"></i>Zostaniemy przeniesieni: {{ entry.map_name }} ({{ entry.x }},{{ entry.y }})<template v-if="entry.instance"> — prywatna instancja</template>
                                            </span>
                                            <span v-else-if="entry.type === 'option_action'" class="text-red-700 dark:text-red-400"><i class="pi pi-bolt mr-1"></i>{{ actionLabels[entry.action] ?? entry.action }}</span>
                                            <span v-else-if="entry.type === 'shop'" class="text-surface-600"><i class="pi pi-shopping-cart mr-1"></i>Otworzy się sklep.</span>
                                            <span v-else-if="entry.type === 'hotel'" class="text-surface-600"><i class="pi pi-home mr-1"></i>Otworzy się hotel.</span>
                                        </li>
                                    </template>
                                </ol>

                                <Message v-if="section.assumes_other_quests && section.purpose !== 'item'" severity="secondary" :closable="false" class="mt-3" size="small">
                                    Ta ścieżka wymaga postępu w innym zadaniu (szczegóły przy opcjach).
                                </Message>
                            </template>

                            <!-- Wait -->
                            <template v-else-if="section.type === 'wait'">
                                <p class="mt-1 text-sm">
                                    Musimy odczekać około <strong>{{ duration(section.seconds) }}</strong>.
                                    <span v-if="section.step?.name" class="text-surface-500">(krok „{{ section.step.name }}”)</span>
                                </p>
                                <p class="text-sm text-surface-500">Jeżeli wrócimy za wcześnie, NPC poprosi o cierpliwość.</p>
                            </template>

                            <!-- Next day -->
                            <template v-else-if="section.type === 'next_day'">
                                <p class="mt-1 text-sm">
                                    Musimy poczekać do <strong>następnego dnia</strong>.
                                    <span v-if="section.resets_quest">Zadanie zostanie wtedy zresetowane i można je wykonać ponownie.</span>
                                </p>
                            </template>

                            <!-- Kill -->
                            <template v-else-if="section.type === 'kill'">
                                <p v-if="section.step?.description" class="mt-1 text-sm italic text-surface-500">{{ section.step.description }}</p>
                                <div class="mt-2 text-sm font-medium">Do pokonania:</div>
                                <ul class="mt-2 flex flex-col gap-3 text-sm">
                                    <li v-for="target in section.targets" :key="`${target.base_npc_id}-${target.mob_species_id}`" class="rounded-md bg-surface-50 px-3 py-2 dark:bg-surface-800">
                                        <div class="font-semibold">{{ target.quantity }}× {{ target.name }}</div>
                                        <div v-for="npc in target.npcs" :key="npc.base_npc_id" class="mt-1 flex items-start gap-2">
                                            <img v-if="npc.src" :src="npc.src" :alt="npc.name" class="max-h-10 max-w-10 object-contain" />
                                            <div>
                                                <div>{{ npc.name }}<span v-if="npc.lvl"> ({{ npc.lvl }} lvl)</span>: {{ npcLocationsText(npc) }}</div>
                                                <div v-for="battle in npc.battle_dialogs" :key="battle.dialog_id" class="text-surface-600 dark:text-surface-300">
                                                    Walka zaczyna się z dialogu{{ battle.auto_start ? " (otwiera się sam po podejściu)" : "" }}:
                                                    <template v-for="(choice, choiceIndex) in chooseEntries(battle.timeline)" :key="choiceIndex">
                                                        „{{ cleanText(choice.label) }}”<template v-if="choiceIndex < chooseEntries(battle.timeline).length - 1"> → </template>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </template>
                        </div>
                    </li>

                    <li v-if="walkthrough.finished" class="flex gap-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-200">
                            <i class="pi pi-check"></i>
                        </div>
                        <div class="pt-1 text-lg font-semibold">Zadanie ukończone</div>
                    </li>
                </ol>
            </div>

            <div v-if="referencedItems.length" class="card">
                <h2 class="mb-3 text-xl font-bold">Przedmioty w zadaniu</h2>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div v-for="reference in referencedItems" :key="reference.id" class="flex gap-3 rounded-md border border-surface-200 p-3 dark:border-surface-700">
                        <img v-if="reference.src" :src="reference.src" :alt="reference.name" class="h-8 w-8 shrink-0" />
                        <div class="min-w-0 text-sm">
                            <div class="font-semibold">{{ reference.name }} <span class="font-normal text-surface-400">#{{ reference.id }}</span></div>
                            <ul v-if="reference.sources.length" class="mt-1 text-surface-600 dark:text-surface-300">
                                <li v-for="(source, sourceIndex) in reference.sources" :key="sourceIndex">{{ sourceLabel(source) }}</li>
                                <li v-if="reference.sources_total > reference.sources.length" class="text-surface-400">i {{ reference.sources_total - reference.sources.length }} innych źródeł</li>
                            </ul>
                            <div v-else class="mt-1 text-surface-500">Dostępny tylko w ramach zadania.</div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>
