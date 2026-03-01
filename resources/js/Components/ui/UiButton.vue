<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: { type: String, default: 'solid' }, // solid | outline | danger
  size: { type: String, default: 'md' },       // sm | md
  type: { type: String, default: 'button' },
  disabled: { type: Boolean, default: false },
})

const cls = computed(() => {
  const base =
    'inline-flex items-center justify-center rounded-lg font-extrabold transition focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:opacity-60 disabled:cursor-not-allowed'
  const sizes = props.size === 'sm' ? 'h-9 px-3 text-sm' : 'h-10 px-4 text-sm'

  const variants = {
    solid: 'bg-brand-500 text-cosmic-950 hover:bg-brand-600',
    outline: 'border border-glass text-white/85 hover:border-brand-500/60 hover:text-brand-200 bg-black/10',
    danger: 'bg-red-500/85 text-white hover:bg-red-500',
  }[props.variant] || 'bg-brand-500 text-cosmic-950 hover:bg-brand-600'

  return `${base} ${sizes} ${variants}`
})
</script>

<template>
  <button :type="props.type" :disabled="props.disabled" :class="cls">
    <slot />
  </button>
</template>