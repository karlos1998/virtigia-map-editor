<template>
    <div style="display: flex; flex-direction: column; gap: 50px;">
        <div ref="chartAttackSpeed" style="width: 1200px; height: 600px;"></div>
        <div ref="chartFullHp" style="width: 1200px; height: 600px;"></div>
        <div ref="chartAttack" style="width: 1200px; height: 600px;"></div>
        <div ref="chartArmor" style="width: 1200px; height: 600px;"></div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import * as echarts from 'echarts'

const chartAttackSpeed = ref()
const chartFullHp = ref()
const chartAttack = ref()
const chartArmor = ref()
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
            series: professions.map(profession => ({
                name: profession,
                type: 'line',
                symbolSize: 8,
                data: dataMap[profession].map(item => [item.lvl, item[dataField]])
            }))
        })
    }

    initChart(chartAttackSpeed.value, 'Szybkość ataku', 'attackSpeed', 'Szybkość ataku')
    initChart(chartFullHp.value, 'Punkty życia', 'fullHp', 'Punkty życia')
    initChart(chartAttack.value, 'Atak', 'attack', 'Atak')
    initChart(chartArmor.value, 'Pancerz', 'armor', 'Pancerz')
    initChart(chartArmor.value, 'Zręczność', 'dexterity', 'Zręczność')
})
</script>
