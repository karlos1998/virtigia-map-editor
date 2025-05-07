<template>
  <div class="app-logo">
    <div class="logo-container">
      <div class="logo-icon">
        <i class="pi pi-map-marker text-2xl"></i>
      </div>
      <div v-if="showText" class="logo-text">
        <span class="logo-name">Margatron</span>
        <span class="logo-subtitle">Map Editor</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  showText: {
    type: Boolean,
    default: true
  }
});
</script>

<style scoped>
.app-logo {
  @apply flex items-center;
}

.logo-container {
  @apply flex items-center;
}

.logo-icon {
  @apply flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-700 text-white shadow-md;
}

.logo-text {
  @apply flex flex-col ml-3;
}

.logo-name {
  @apply font-bold text-gray-800 dark:text-white text-lg;
}

.logo-subtitle {
  @apply text-xs text-gray-500 dark:text-gray-400;
}
</style>
