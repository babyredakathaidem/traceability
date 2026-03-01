<script setup>
import { computed } from 'vue'
const props = defineProps({ modelValue:[String,Number,null], disabled:Boolean })
const emit = defineEmits(['update:modelValue'])
const cls = computed(() =>
  'w-full rounded-lg border border-glass bg-black/25 px-4 py-2.5 text-white/90 ' +
  'focus:border-brand-500/60 focus:ring-brand-500/20 hover:border-brand-500/30 transition cursor-pointer ' +
  (props.disabled ? 'opacity-60 cursor-not-allowed' : '')
)
</script>
<template>
  <select :disabled="props.disabled" :value="props.modelValue" @change="emit('update:modelValue',$event.target.value)" :class="cls">
    <slot />
  </select>
</template>