<script setup>
import { ref } from 'vue'
defineProps({ text:String })
const show = ref(false)
</script>
<template>
  <span class="relative inline-flex" @mouseenter="show=true" @mouseleave="show=false">
    <slot />
    <span v-if="show" class="absolute z-20 -top-2 left-1/2 -translate-x-1/2 -translate-y-full whitespace-nowrap
      bg-black/80 text-white text-xs px-2 py-1 rounded-md border border-glass">
      {{ text }}
    </span>
  </span>
</template>