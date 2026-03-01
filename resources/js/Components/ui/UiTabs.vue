<script setup>
defineProps({ tabs:Array, modelValue:String })
const emit = defineEmits(['update:modelValue'])
</script>
<template>
  <div class="flex gap-2 border-b border-glass pb-2">
    <button v-for="t in tabs" :key="t.value"
      @click="emit('update:modelValue', t.value)"
      class="px-3 py-2 rounded-lg text-sm font-bold transition"
      :class="modelValue===t.value ? 'bg-brand-500/15 text-brand-200 border border-brand-500/30' : 'text-white/70 hover:bg-white/5 hover:text-white'"
    >
      {{ t.label }}
    </button>
  </div>
  <div class="pt-4"><slot /></div>
</template>