<script setup>
defineProps({ open:Boolean, title:String })
const emit = defineEmits(['close'])
</script>
<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/70" @click="emit('close')"></div>
    <div class="absolute right-0 top-0 h-full w-full max-w-md bg-cosmic-900 border-l border-glass p-5">
      <div class="flex items-center justify-between">
        <div class="text-brand-200 font-extrabold">{{ title }}</div>
        <button class="text-white/70 hover:text-white" @click="emit('close')">✕</button>
      </div>
      <div class="mt-4"><slot /></div>
    </div>
  </div>
</template>