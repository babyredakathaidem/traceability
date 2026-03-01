<script setup>
defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: '' },
})
const emit = defineEmits(['close'])
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-6">
    <div class="absolute inset-0 bg-black/70" @click="emit('close')" />
    <div class="relative w-full max-w-lg bg-white/5 border border-glass rounded-2xl backdrop-blur-md shadow-glow-orange p-5">
      <div class="flex items-start justify-between gap-3">
        <div class="text-brand-200 font-extrabold text-lg">{{ title }}</div>
        <button class="text-white/70 hover:text-white" @click="emit('close')">✕</button>
      </div>

      <div class="mt-4">
        <slot />
      </div>

      <div class="mt-5 flex justify-end gap-2">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>