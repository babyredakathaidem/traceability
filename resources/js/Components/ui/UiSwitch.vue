<script setup>
const props = defineProps({ modelValue:Boolean, disabled:Boolean })
const emit = defineEmits(['update:modelValue'])
</script>
<template>
  <button type="button" :disabled="props.disabled"
    @click="emit('update:modelValue', !props.modelValue)"
    class="relative inline-flex h-6 w-11 items-center rounded-full border border-glass transition"
    :class="props.modelValue ? 'bg-brand-500' : 'bg-black/30'"
  >
    <span class="inline-block h-5 w-5 transform rounded-full bg-cosmic-950 transition"
          :class="props.modelValue ? 'translate-x-5' : 'translate-x-1'"/>
  </button>
</template>