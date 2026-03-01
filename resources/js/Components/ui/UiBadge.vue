<script setup>
const props = defineProps({
  variant: { type: String, default: 'gray' }, // green | orange | red | gray
})
</script>

<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-extrabold border"
    :class="{
      'border-emerald-400/30 bg-emerald-400/10 text-emerald-200': props.variant === 'green',
      'border-brand-500/35 bg-brand-500/12 text-brand-200': props.variant === 'orange',
      'border-red-400/30 bg-red-400/10 text-red-200': props.variant === 'red',
      'border-glass bg-black/15 text-white/75': props.variant === 'gray',
    }"
  >
    <slot />
  </span>
</template>