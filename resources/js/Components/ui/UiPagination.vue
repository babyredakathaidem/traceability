<script setup>
defineProps({ page:Number, pageCount:Number })
const emit = defineEmits(['update:page'])
</script>
<template>
  <div class="flex items-center gap-2">
    <button class="px-3 py-2 rounded-lg border border-glass hover:border-brand-500/60 text-white/80"
      :disabled="page<=1" @click="emit('update:page', page-1)">Prev</button>
    <div class="text-sm text-white/60">Page <span class="text-white/90 font-bold">{{ page }}</span> / {{ pageCount }}</div>
    <button class="px-3 py-2 rounded-lg border border-glass hover:border-brand-500/60 text-white/80"
      :disabled="page>=pageCount" @click="emit('update:page', page+1)">Next</button>
  </div>
</template>