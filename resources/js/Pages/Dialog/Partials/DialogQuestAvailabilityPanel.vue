<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import type {
    DialogQuestAvailability,
    QuestMarkerCondition,
    QuestMarkerRuleCheck,
} from '@/Resources/DialogQuestAvailability.resource';

const props = defineProps<{
    availability: DialogQuestAvailability;
    rules: { label: string; value: string }[];
}>();

const describedQuests = computed(() => props.availability.quests.map(quest => ({
    ...quest,
    paths: quest.paths.map(path => path.flatMap(describeCondition)),
})));

const ruleLabels = computed(() => Object.fromEntries(props.rules.map(rule => [rule.value, rule.label])));
const professionLabels: Record<string, string> = {
    w: 'Wojownik',
    p: 'Paladyn',
    m: 'Mag',
    b: 'Tancerz ostrzy',
    t: 'Tropiciel',
    h: 'Łowca',
};
const compilerErrors: Record<string, string> = {
    graph_size: 'Drzewo dialogu przekracza limit analizy.',
    path_count: 'Dialog ma za dużo ścieżek do rozpoczęcia questów.',
    external_node: 'Ścieżka odsyła do węzła spoza tego dialogu.',
    invalid_rules: 'Co najmniej jedna reguła ma nieprawidłowy format.',
};

function formatValue(value: unknown): string {
    if (Array.isArray(value)) {
        return value.map(formatValue).join(', ');
    }
    if (typeof value === 'boolean') {
        return value ? 'tak' : 'nie';
    }
    if (value === null || value === undefined) {
        return '';
    }
    if (typeof value === 'object') {
        return JSON.stringify(value);
    }
    return String(value);
}

function describeCheck(check: QuestMarkerRuleCheck): string[] {
    return Object.entries(check.rules)
        .filter(([key]) => key !== 'messageContent' || check.optionEdge)
        .map(([key, rule]) => {
            if (key === 'percentageChance') {
                return typeof rule.value === 'number' && Math.trunc(rule.value) > 0
                    ? `Możliwa gałąź losowa (${rule.value}%)`
                    : 'Przejście niemożliwe (brak dodatniej szansy)';
            }

            const label = ruleLabels.value[key] ?? key;
            const value = formatValue(rule.value);
            const detail = formatValue(rule.value2);
            return [label, value, detail ? `(${detail})` : ''].filter(Boolean).join(' ');
        });
}

function describeCondition(condition: QuestMarkerCondition): string[] {
    const labels = describeCheck(condition.check);
    if (condition.profession) {
        labels.unshift(`Profesja: ${professionLabels[condition.profession] ?? condition.profession}`);
    }
    if (condition.optionId !== undefined) {
        labels.push(`Opcja dialogu #${condition.optionId} jest poza okresem odnowienia`);
    }
    for (const check of condition.unless ?? []) {
        const rules = describeCheck(check);
        labels.push(rules.length
            ? `Inna gałąź nie może spełniać wszystkich warunków: ${rules.join(' i ')}`
            : 'Ta ścieżka jest zasłonięta przez inną dostępną gałąź');
    }
    return labels;
}
</script>

<template>
    <section class="flex min-w-0 flex-col gap-4" aria-labelledby="dialog-quest-marker-heading">
        <div>
            <h2 id="dialog-quest-marker-heading" class="m-0 text-xl font-semibold">Warunki znacznika questa</h2>
            <p class="mt-2 text-sm text-surface-500 dark:text-surface-400">
                Warunki z ostatniego przeliczenia dla tego dialogu. Widoczny NPC pokaże znacznik, jeśli gracz
                jeszcze nie rozpoczął danego questa i spełnia wszystkie warunki jednej z jego ścieżek.
            </p>
        </div>

        <Message v-if="availability.status === 'pending'" severity="info">
            Warunki pojawią się po przeliczeniu dialogu.
        </Message>
        <Message v-else-if="availability.status === 'uncompiled'" severity="warn">
            Nie udało się wygenerować warunków.
            {{ compilerErrors[availability.reason ?? ''] ?? availability.reason }}
        </Message>
        <Message v-else-if="availability.quests.length === 0" severity="secondary">
            Ten dialog nie ma ścieżki, która wyświetla znacznik rozpoczęcia questa.
        </Message>

        <article
            v-for="quest in describedQuests"
            :key="quest.id"
            class="flex min-w-0 flex-col gap-3 rounded-lg border border-surface-200 p-4 dark:border-surface-700"
        >
            <h3 class="m-0 break-words text-base font-semibold">
                <Link :href="route('quests.show', { quest: quest.id })" class="underline underline-offset-2">
                    {{ quest.name ?? `Quest #${quest.id}` }}
                </Link>
            </h3>
            <div v-for="(path, index) in quest.paths" :key="index" class="rounded-md bg-surface-50 p-3 dark:bg-surface-900">
                <h4 v-if="quest.paths.length > 1" class="m-0 mb-2 text-sm font-semibold">Ścieżka {{ index + 1 }}</h4>
                <p v-if="path.length === 0" class="m-0 text-sm">Bez dodatkowych warunków.</p>
                <ul v-else class="m-0 list-disc space-y-1 break-words pl-5 text-sm">
                    <li v-for="(label, labelIndex) in path" :key="labelIndex">{{ label }}</li>
                </ul>
            </div>
        </article>
    </section>
</template>
