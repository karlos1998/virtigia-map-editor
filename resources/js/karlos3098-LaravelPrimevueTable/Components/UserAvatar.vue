<script setup>
import {computed} from "vue";
import {usePage} from "@inertiajs/vue3";

const user = computed(() => usePage().props.auth.user);
</script>
<template>
    <div class="avatar rounded-xl" :style="{
                            'background-image': `url(${user.src})`
                        }" />
</template>

<style scoped>
.avatar {
    width: 32px;
    height:32px;
    background-repeat: no-repeat;
}
</style>
