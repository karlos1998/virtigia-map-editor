<template>
    <div style="display: flex; flex-direction: column; gap: 50px;">
        <div ref="chartAttackSpeed" style="width: 1200px; height: 600px;"></div>
        <div ref="chartFullHp" style="width: 1200px; height: 600px;"></div>
        <div ref="chartAttack" style="width: 1200px; height: 600px;"></div>
        <div ref="chartStrength" style="width: 1200px; height: 600px;"></div>
        <div ref="chartDexterity" style="width: 1200px; height: 600px;"></div>
        <div ref="chartEvadePoints" style="width: 1200px; height: 600px;"></div>
        <div ref="chartEvadeChance" style="width: 1200px; height: 600px;"></div>
        <div ref="chartIntelligence" style="width: 1200px; height: 600px;"></div>
        <div ref="chartCriticChance" style="width: 1200px; height: 600px;"></div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import * as echarts from 'echarts'

const chartAttackSpeed = ref()
const chartFullHp = ref()
const chartAttack = ref()
const chartStrength = ref()
const chartDexterity = ref()
const chartEvadePoints = ref()
const chartEvadeChance = ref()
const chartIntelligence = ref()
const chartCriticChance = ref()

onMounted(async () => {
    const professions = ['w', 'm', 'b', 'p', 'h', 't']
    const requests = professions.map(prof => axios.get(`/debug-stats/api/characters?profession=${prof}`))
    const responses = await Promise.all(requests)
    const dataMap = {}
    professions.forEach((prof, i) => {
        dataMap[prof] = responses[i].data
    })

    const initChart = (dom, title, dataField, yAxisName) => {
        const instance = echarts.init(dom)
        instance.setOption({
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
            series: professions.map(prof => ({
                name: prof,
                type: 'line',
                symbolSize: 8,
                data: dataMap[prof].map(item => [item.lvl, item[dataField]])
            }))
        })
    }

    initChart(chartAttackSpeed.value, 'Szybkość ataku', 'attackSpeed', 'Szybkość ataku')
    initChart(chartFullHp.value, 'Punkty życia', 'fullHp', 'Punkty życia')
    initChart(chartAttack.value, 'Atak', 'attack', 'Atak')
    initChart(chartStrength.value, 'Siła', 'strength', 'Siła')
    initChart(chartDexterity.value, 'Zręczność', 'dexterity', 'Zręczność')
    initChart(chartEvadePoints.value, 'Punkty uniku', 'evadePoints', 'Punkty uniku')
    initChart(chartEvadeChance.value, 'Szansa na unik', 'evadeChance', 'Szansa na unik')
    initChart(chartIntelligence.value, 'Inteligencja', 'intelligence', 'Inteligencja')
    initChart(chartCriticChance.value, 'Szansa na krytyk', 'criticChance', 'Szansa na krytyk')
})
</script>
