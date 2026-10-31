import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { runInNewContext } from 'node:vm';
import { compileScript, parse } from '@vue/compiler-sfc';
import ts from 'typescript';
import * as vue from 'vue';
import * as calendarTypes from '../../resources/js/Pages/RewardCalendar/types.ts';

const require = createRequire(import.meta.url);

function setupPage(page: string, props: object) {
    const filename = new URL(`../../resources/js/Pages/RewardCalendar/${page}.vue`, import.meta.url).pathname;
    const { descriptor } = parse(readFileSync(filename, 'utf8'), { filename });
    const script = compileScript(descriptor, { id: `reward-calendar-${page}` });
    const { outputText } = ts.transpileModule(script.content, {
        compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    });
    const confirmations: any[] = [];
    const requests: any[] = [];
    const exports: any = {};
    const modules = {
        vue,
        './types': calendarTypes,
        'ziggy-js': { route: (name: string, params: object) => ({ name, params }) },
        primevue: {
            useConfirm: () => ({ require: (options: object) => confirmations.push(options) }),
            useToast: () => ({ add() {} }),
        },
        '@inertiajs/vue3': {
            useForm: (data: object) => vue.reactive({ ...data, clearErrors() {} }),
            router: { delete: (...args: any[]) => requests.push(args) },
        },
    };
    runInNewContext(outputText, {
        exports,
        structuredClone,
        require: (name: string) => modules[name] ?? (name.endsWith('.vue') || name.startsWith('primevue/') ? {} : require(name)),
    }, { filename });

    return {
        state: exports.default.setup(vue.reactive(props), { expose() {} }),
        confirmations,
        requests,
        template: descriptor.template!.ast,
    };
}

const rewards = () => ({
    mode: 'random', rewardBinding: 'none', minRewards: 1, maxRewards: 2,
    items: [{ baseItemId: 42, chancePercent: 50, minQuantity: 1, maxQuantity: 3 }],
});

const day = (overrides = {}) => ({
    id: 7, month: 12, day: 1, type: 'normal', rewards: rewards(), tiers: null, ...overrides,
});

function showPage(days: object[]) {
    return setupPage('Show', { calendar: { id: 1, name: 'Grudzień' }, days, baseItems: {}, styles: [] });
}

test('opens normal day editor with independent copies of reactive rewards', () => {
    const original = vue.reactive(day());
    const { state } = showPage([original]);

    state.openEditDay(original);

    assert.equal(state.dayDialogVisible.value, true);
    assert.equal(state.dayForm.month, 12);
    assert.equal(state.dayForm.day, 1);
    assert.equal(state.dayForm.type, 'normal');
    assert.deepEqual(vue.toRaw(state.dayRewardsAttributes.value.lootChest), vue.toRaw(original.rewards));
    state.dayRewardsAttributes.value.lootChest.items[0].minQuantity = 99;
    assert.equal(original.rewards.items[0].minQuantity, 1);
});

test('opens special day editor and isolates rewards of every tier', () => {
    const original = vue.reactive(day({ type: 'special', rewards: null, tiers: [
        { minOpenedDays: 0, rewards: rewards() },
        { minOpenedDays: 10, rewards: { ...rewards(), mode: 'guaranteed' } },
    ] }));
    const { state } = showPage([original]);

    state.openEditDay(original);

    assert.equal(state.dayDialogVisible.value, true);
    assert.equal(state.dayForm.type, 'special');
    assert.equal(state.dayTiers.value.length, 2);
    for (const [index, tier] of state.dayTiers.value.entries()) {
        assert.equal(tier.minOpenedDays, original.tiers[index].minOpenedDays);
        assert.deepEqual(vue.toRaw(tier.attributes.lootChest), vue.toRaw(original.tiers[index].rewards));
        tier.attributes.lootChest.items[0].maxQuantity = 99;
        assert.equal(original.tiers[index].rewards.items[0].maxQuantity, 3);
    }
});

test('clears rewards and tiers when switching to an empty day', () => {
    const { state } = showPage([]);
    state.openEditDay(vue.reactive(day()));
    state.openEditDay(vue.reactive(day({ id: 8, rewards: null })));

    assert.equal(state.dayDialogVisible.value, true);
    assert.equal(Object.keys(state.dayRewardsAttributes.value).length, 0);
    assert.equal(state.dayTiers.value.length, 0);
});

function hasConfirmationDialog(node: any): boolean {
    return node.tag === 'ConfirmDialog' || (node.children ?? []).some(hasConfirmationDialog);
}

test('renders day deletion confirmation and deletes only after accepting', () => {
    const original = vue.reactive(day());
    const { state, confirmations, requests, template } = showPage([original]);
    assert.ok(hasConfirmationDialog(template));

    state.removeDay(original);

    assert.equal(confirmations.length, 1);
    assert.equal(confirmations[0].message, 'Usunąć dzień 1 grudzień?');
    assert.equal(requests.length, 0);
    confirmations[0].accept();
    assert.equal(requests.length, 1);
    assert.equal(requests[0][0].name, 'reward-calendars.days.destroy');
    assert.equal(requests[0][0].params.rewardCalendar, 1);
    assert.equal(requests[0][0].params.rewardCalendarDay, 7);
});

test('renders calendar deletion confirmation and deletes only after accepting', () => {
    const calendar = vue.reactive({ id: 1, name: 'Grudzień' });
    const { state, confirmations, requests, template } = setupPage('Index', { calendars: [calendar], styles: [] });
    assert.ok(hasConfirmationDialog(template));

    state.removeCalendar(calendar);

    assert.equal(confirmations.length, 1);
    assert.equal(requests.length, 0);
    confirmations[0].accept();
    assert.equal(requests[0][0].name, 'reward-calendars.destroy');
    assert.equal(requests[0][0].params.rewardCalendar, 1);
});
