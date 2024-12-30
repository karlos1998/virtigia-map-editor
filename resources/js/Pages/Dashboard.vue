<script setup lang="ts">

import AppLayout from "../layout/AppLayout.vue";
import {MapResource} from "@/Resources/Map.resource";

</script>

<template>
    <AppLayout>
        <Message severity="success">
            <div>Witaj w Edytorze Map, przybyszu ! :D</div>
            <div>Bardzo się cieszę, że tu jesteś by pomoć nam odbudowywać świat Margatronu :)</div>
            <div>Skorzystaj z bocznego menu by przejść do konkretnych funkcjonalności</div>
        </Message>

        <Message severity="error">
            Uwaga! Nie dotykaj dialogów! Nie są jeszcze gotowe!
        </Message>
    </AppLayout>

</template>

<style scoped>
</style>
