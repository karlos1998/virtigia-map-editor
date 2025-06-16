<template>
    <div style="display: flex; flex-direction: column; gap: 50px;">
        <div v-if="loading" class="loading" style="text-align: center; font-size: 24px; margin: 50px 0; padding: 20px; background-color: #f5f5f5; border-radius: 8px;">
            Ładowanie wykresów...
        </div>
        <div
            v-for="field in validFields"
            :key="field.key"
            :ref="el => chartRefs[field.key] = el"
            class="chart-container"
            :class="{ 'chart-hidden': loading }"
            style="width: 1200px; height: 600px; position: relative; margin: 0 auto;"
        ></div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive, nextTick } from 'vue'
import axios from 'axios'
import * as echarts from 'echarts'

// Loading state
const loading = ref(true)
// Store valid fields
const validFields = ref([])

// Define all numeric fields to chart with their display names
const chartFields = [
    { key: 'attackSpeed', name: 'Szybkość ataku' },
    { key: 'fullHp', name: 'Punkty życia' },
    { key: 'attack', name: 'Atak' },
    { key: 'armor', name: 'Pancerz' },
    { key: 'dexterity', name: 'Zręczność' },
    { key: 'strength', name: 'Siła' },
    { key: 'intelligence', name: 'Inteligencja' },
    { key: 'criticChance', name: 'Szansa na krytyczne trafienie' },
    { key: 'criticStrength', name: 'Siła krytycznego trafienia' },
    { key: 'magicCriticStrength', name: 'Siła magicznego krytycznego trafienia' },
    { key: 'physicalDamageMin', name: 'Min. obrażenia fizyczne' },
    { key: 'physicalDamageMax', name: 'Max. obrażenia fizyczne' },
    { key: 'fireDamageMin', name: 'Min. obrażenia od ognia' },
    { key: 'fireDamageMax', name: 'Max. obrażenia od ognia' },
    { key: 'frostDamageMin', name: 'Min. obrażenia od mrozu' },
    { key: 'frostDamageMax', name: 'Max. obrażenia od mrozu' },
    { key: 'lightDamageMin', name: 'Min. obrażenia od błyskawic' },
    { key: 'lightDamageMax', name: 'Max. obrażenia od błyskawic' },
    { key: 'poisonDamage', name: 'Obrażenia od trucizny' },
    { key: 'auxiliaryDamageMin', name: 'Min. obrażenia pomocnicze' },
    { key: 'auxiliaryDamageMax', name: 'Max. obrażenia pomocnicze' },
    { key: 'evadeChance', name: 'Szansa na unik' },
    { key: 'evadePoints', name: 'Punkty uniku' },
    { key: 'physicalDamageAbsorption', name: 'Absorpcja obrażeń fizycznych' },
    { key: 'magicalDamageAbsorption', name: 'Absorpcja obrażeń magicznych' },
    { key: 'poisonResistance', name: 'Odporność na truciznę' },
    { key: 'frostResistance', name: 'Odporność na mróz' },
    { key: 'fireResistance', name: 'Odporność na ogień' },
    { key: 'lightResistance', name: 'Odporność na błyskawice' },
    { key: 'enemyEvasionReduction', name: 'Redukcja uniku przeciwnika' },
    { key: 'blockChance', name: 'Szansa na blok' },
    { key: 'veryCriticChance', name: 'Szansa na bardzo krytyczne trafienie' },
    { key: 'respawnTime', name: 'Czas odrodzenia' },
    { key: 'minRespawnTime', name: 'Min. czas odrodzenia' },
    { key: 'maxRespawnTime', name: 'Max. czas odrodzenia' }
]

// Create a reactive object to store chart refs
const chartRefs = reactive({})

onMounted(async () => {
    const professions = ['w', 'm', 'b', 'p', 'h', 't']
    // const ranks = ["NORMAL", "ELITE", "HERO"]
    const rank = 'NORMAL';
    const requests = professions.map(profession => axios.get(`/debug-stats/api/base-npcs?rank=${rank}&profession=${profession}`))
    const responses = await Promise.all(requests)
    const dataMap = {}
    professions.forEach((prof, i) => {
        dataMap[prof] = responses[i].data
    })

    // Filter out fields that don't have numeric values
    validFields.value = chartFields.filter(field => {
        // Check if at least one profession has a non-null, non-zero value for this field
        return professions.some(prof => {
            const items = dataMap[prof]
            return items.some(item => {
                const value = item[field.key]
                return value !== null && value !== undefined && value !== 0
            })
        })
    })

    // Helper function to check if DOM element is ready for chart initialization
    const isDomReady = (dom) => {
        // More lenient check - just ensure the DOM element exists
        if (!dom) {
            return false;
        }

        // Log dimensions for debugging
        console.log(`DOM element dimensions: width=${dom.clientWidth}, height=${dom.clientHeight}, offsetParent=${dom.offsetParent !== null}`);

        // Only check if the DOM element exists
        return true;
    };

    const initChart = (dom, title, dataField, yAxisName) => {
        // Check if the DOM element is ready for initialization
        if (!isDomReady(dom)) {
            console.error(`Chart container for ${dataField} is not ready for initialization`);
            return false;
        }

        try {
            console.log(`Initializing chart for ${dataField} with DOM element:`, dom);

            // Check if a chart instance already exists for this DOM
            let instance = echarts.getInstanceByDom(dom);
            console.log(`Existing chart instance for ${dataField}:`, instance ? 'yes' : 'no');

            // If not, create a new one
            if (!instance) {
                console.log(`Creating new chart instance for ${dataField}`);
                try {
                    instance = echarts.init(dom);
                    console.log(`New chart instance created for ${dataField}:`, instance ? 'success' : 'failed');
                } catch (initError) {
                    console.error(`Error creating chart instance for ${dataField}:`, initError);
                    return false;
                }
            }

            // Ensure instance was created successfully
            if (!instance) {
                console.error(`Failed to get or create chart instance for ${dataField}`);
                return false;
            }

            // Create chart options
            const options = {
                title: { text: title },
                tooltip: { trigger: 'axis' },
                legend: { data: professions },
                xAxis: {
                    name: 'Poziom',
                    type: 'value',
                    minorTick: { show: true },
                    splitLine: { show: true }
                },
                yAxis: {
                    name: yAxisName,
                    type: 'value',
                    minorTick: { show: true },
                    splitLine: { show: true }
                },
                series: professions.map(profession => ({
                    name: profession,
                    type: 'line',
                    symbolSize: 8,
                    data: dataMap[profession].map(item => [item.lvl, item[dataField]])
                }))
            };

            // Set chart options
            console.log(`Setting options for chart ${dataField}`);
            instance.setOption(options);

            console.log(`Chart for ${dataField} initialized successfully`);
            return true;
        } catch (error) {
            console.error(`Error initializing chart for ${dataField}:`, error);
            return false;
        }
    }

    // First, wait for the DOM to be updated with the chart divs
    nextTick(async () => {
        console.log('Valid fields:', validFields.value)

        // Add a longer delay to ensure DOM is fully updated and refs are available
        await new Promise(resolve => setTimeout(resolve, 1000));

        console.log('Chart refs after delay:', Object.keys(chartRefs))

        // Check if we have all the refs we need
        let missingRefs = validFields.value.filter(field => !chartRefs[field.key]);
        if (missingRefs.length > 0) {
            console.warn('Missing refs for fields:', missingRefs.map(f => f.key).join(', '));
            // Wait a bit longer if refs are missing
            await new Promise(resolve => setTimeout(resolve, 500));
            console.log('Chart refs after additional delay:', Object.keys(chartRefs));
        }

        // Initialize charts only for valid fields with available refs
        const initializedCharts = [];
        const failedCharts = [];

        // First initialization attempt
        validFields.value.forEach(field => {
            if (chartRefs[field.key]) {
                console.log(`Initializing chart for ${field.key}`);
                const success = initChart(chartRefs[field.key], field.name, field.key, field.name);
                if (success) {
                    initializedCharts.push(field.key);
                } else {
                    console.warn(`Chart initialization for ${field.key} returned false, will retry`);
                    failedCharts.push(field);
                }
            } else {
                console.error(`Chart ref for ${field.key} not found`);
                failedCharts.push(field);
            }
        });

        // If any charts failed to initialize, try again after a longer delay
        if (failedCharts.length > 0) {
            console.log(`Retrying initialization for ${failedCharts.length} charts after delay:`, failedCharts.map(f => f.key).join(', '));
            await new Promise(resolve => setTimeout(resolve, 2000));

            // Force a browser reflow to ensure DOM is updated
            document.body.offsetHeight;

            // Second initialization attempt for failed charts
            failedCharts.forEach(field => {
                if (chartRefs[field.key]) {
                    console.log(`Retrying initialization for chart ${field.key}`);
                    const success = initChart(chartRefs[field.key], field.name, field.key, field.name);
                    if (success) {
                        initializedCharts.push(field.key);
                        console.log(`Successfully initialized chart ${field.key} on retry`);
                    } else {
                        console.error(`Failed to initialize chart for ${field.key} on retry`);
                    }
                } else {
                    console.error(`Chart ref for ${field.key} still not found after retry`);
                }
            });
        }

        console.log('Successfully initialized charts:', initializedCharts.join(', '));

        // Check if we have at least some charts initialized
        if (initializedCharts.length === 0) {
            console.warn('No charts were successfully initialized. Check for DOM or data issues.');
            // We'll still proceed to show the UI, but with a warning
        } else if (initializedCharts.length < validFields.value.length) {
            console.warn(`Only ${initializedCharts.length} out of ${validFields.value.length} charts were initialized.`);
        } else {
            console.log(`All ${validFields.value.length} charts were successfully initialized.`);
        }

        // Add window resize event listener to resize charts
        window.addEventListener('resize', () => {
            validFields.value.forEach(field => {
                if (chartRefs[field.key]) {
                    const chartInstance = echarts.getInstanceByDom(chartRefs[field.key]);
                    if (chartInstance) {
                        chartInstance.resize();
                    }
                }
            });
        });

        // Set loading to false only after we've tried to initialize all charts
        loading.value = false;

        // Final check - if we have no charts initialized but have valid fields,
        // we might need to try one more approach
        if (initializedCharts.length === 0 && validFields.value.length > 0) {
            // Wait a bit longer and try one more time for any chart
            setTimeout(() => {
                const anyField = validFields.value[0];
                if (chartRefs[anyField.key] && isDomReady(chartRefs[anyField.key])) {
                    console.log('Last attempt to initialize at least one chart...');
                    initChart(chartRefs[anyField.key], anyField.name, anyField.key, anyField.name);
                }
            }, 2000);
        }
    });
})
</script>

<style>
.chart-container {
    box-sizing: border-box;
    border: 1px solid #eaeaea;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    background-color: #fff;
    transition: all 0.3s ease;
}

.chart-container:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.chart-hidden {
    visibility: hidden;
    opacity: 0;
}

.loading {
    animation: pulse 1.5s infinite ease-in-out;
}

@keyframes pulse {
    0% { opacity: 0.6; }
    50% { opacity: 1; }
    100% { opacity: 0.6; }
}
</style>
