<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: [String, Number],
  label: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  type: { type: String, default: 'text' },
})
const emit = defineEmits(['update:modelValue'])

const cls = computed(() =>
  'mt-2 w-full rounded-lg border border-glass bg-black/25 px-4 py-2.5 text-white/90 placeholder:text-white/40 ' +
  'focus:border-brand-500/60 focus:ring-brand-500/20 hover:border-brand-500/30 transition'
)
</script>

<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-white/70">{{ label }}</label>
    <input
      :type="type"
      :placeholder="placeholder"
      :value="modelValue"
      @input="emit('update:modelValue', $event.target.value)"
      :class="cls"
    />
  </div>
</template>