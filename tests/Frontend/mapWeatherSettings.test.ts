import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {createRequire} from 'node:module';
import {runInNewContext} from 'node:vm';
import {compileScript, parse} from '@vue/compiler-sfc';
import ts from 'typescript';
import * as vue from 'vue';

const require = createRequire(import.meta.url);

function setupWeatherForm(weather_settings: object | null) {
    const filename = new URL('../../resources/js/Pages/Map/Components/MapWeatherSettings.vue', import.meta.url).pathname;
    const {descriptor} = parse(readFileSync(filename, 'utf8'), {filename});
    const script = compileScript(descriptor, {id: 'map-weather-settings'});
    const {outputText} = ts.transpileModule(script.content, {
        compilerOptions: {module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022},
    });
    const requests: any[] = [];
    const exports: any = {};
    const modules: Record<string, unknown> = {
        vue,
        'ziggy-js': {route: (name: string, id: number) => ({name, id})},
        primevue: {useToast: () => ({add() {}})},
        '@inertiajs/vue3': {
            useForm: (data: {weather_settings: object}) => {
                let transform = (payload: object) => payload;
                return vue.reactive({...data, defaults() {}, reset() {}, errors: {}, processing: false,
                    transform(mapper: (payload: object) => object) {transform = mapper; return this;},
                    patch(url: object) {
                        requests.push({url, ...JSON.parse(JSON.stringify(transform({weather_settings: this.weather_settings})))});
                    },
                });
            },
        },
    };
    runInNewContext(outputText, {exports, require: (name: string) => modules[name] ?? require(name)}, {filename});
    return {state: exports.default.setup({map: {id: 42, weather_settings}}, {expose() {}}), requests};
}

test('saving every effect disabled keeps explicit snow disabled to override legacy snow', () => {
    const {state, requests} = setupWeatherForm(null);
    state.save();
    assert.equal(requests[0].weather_settings.snow.enabled, false);
    assert.equal(Object.values(requests[0].weather_settings).every((effect: any) => effect.enabled === false), true);
});

test('enabling a new effect preserves saved rain and fog parameters', () => {
    const rain = {enabled: true, density: .8, speed: 2.1, wind: -.7};
    const fog = {enabled: true, opacity: .72, speedX: -.3, speedY: .2, color: '#123456'};
    const {state, requests} = setupWeatherForm({rain, fog});
    state.form.weather_settings.halloweenBats.enabled = true;
    state.save();
    assert.deepEqual(requests[0].weather_settings.rain, rain);
    assert.deepEqual(requests[0].weather_settings.fog, fog);
    assert.equal(requests[0].weather_settings.halloweenBats.enabled, true);
    assert.equal(requests[0].weather_settings.lanterns.enabled, false);
});

test('saving cloud controls sends the independent cloud configuration', () => {
    const {state, requests} = setupWeatherForm(null);
    state.form.weather_settings.clouds.enabled = true;
    state.form.weather_settings.clouds.opacity = .8;
    state.save();
    assert.deepEqual(requests[0].weather_settings.clouds, {
        enabled: true, opacity: .8, speedX: .35, speedY: .06, color: '#ebf2ff',
    });
});
