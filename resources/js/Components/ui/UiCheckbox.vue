<script setup>
const props = defineProps({ modelValue:Boolean, disabled:Boolean })
const emit = defineEmits(['update:modelValue'])
</script>
<template>
  <input type="checkbox" :checked="props.modelValue" :disabled="props.disabled"
         @change="emit('update:modelValue',$event.target.checked)"
         class="rounded border-glass bg-black/30 text-brand-500 focus:ring-brand-500/20" />
</template>