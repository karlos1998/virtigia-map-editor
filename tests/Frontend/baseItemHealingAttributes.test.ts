import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { compileScript, parse } from '@vue/compiler-sfc';
import ts from 'typescript';
import * as vue from 'vue';
import * as options from '../../resources/js/Pages/BaseItem/AttributeOptions.ts';
import * as rules from '../../resources/js/Pages/BaseItem/AttributeCategoryRules.ts';

function setupEditor(attributes: Record<string, any> = {}) {
    const filename = new URL('../../resources/js/Pages/BaseItem/Components/AttributePointsEditor.vue', import.meta.url).pathname;
    const { descriptor } = parse(readFileSync(filename, 'utf8'), { filename });
    const script = compileScript(descriptor, { id: 'base-item-healing' });
    const { outputText } = ts.transpileModule(script.content, {
        compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    });
    const form = vue.reactive({ attributes, attribute_points: {}, manual_attribute_points: {} });
    const exports: any = {};
    const modules = {
        vue: { ...vue, useModel: () => vue.ref(form), onMounted() {} },
        axios: { get: async () => ({ data: {} }) },
        primevue: { useToast: () => ({ add() {} }) },
        '../AttributeOptions': options,
        '../AttributeCategoryRules': rules,
    };
    runInNewContext(outputText, { exports, require: (name: string) => modules[name] ?? {} });
    const state = exports.default.setup({ baseItem: { category: 'talismans', attributes } }, { expose() {}, emit() {} });

    return { form, state };
}

test('reading unset healing shows empty inputs without adding an attribute', () => {
    const { form, state } = setupEditor();

    assert.equal(JSON.stringify(state.getAdditionalAttributeValueAsArray('healChanceAfterFight')), '[null,null]');
    assert.equal('healChanceAfterFight' in form.attributes, false);
});

test('clearing both healing inputs removes the attribute and preserves other values', () => {
    const { form, state } = setupEditor({ healChanceAfterFight: [20, 100], fasterRevivalRecovery: 0 });

    state.updateAdditionalAttribute('healChanceAfterFight', [null, 100]);
    assert.deepEqual(form.attributes.healChanceAfterFight, [null, 100]);
    state.updateAdditionalAttribute('healChanceAfterFight', [null, null]);
    assert.equal('healChanceAfterFight' in form.attributes, false);
    assert.equal(form.attributes.fasterRevivalRecovery, 0);
});

test('preserves complete healing and keeps incomplete input available for validation', () => {
    const { form, state } = setupEditor();

    state.updateAdditionalAttribute('healChanceAfterFight', [25, 500]);
    assert.deepEqual(form.attributes.healChanceAfterFight, [25, 500]);
    state.updateAdditionalAttribute('healChanceAfterFight', [25, null]);
    assert.deepEqual(form.attributes.healChanceAfterFight, [25, null]);
    assert.ok(state.visibleAdditionalAttributes.value.some(attribute => attribute.key === 'healChanceAfterFight'));
});
