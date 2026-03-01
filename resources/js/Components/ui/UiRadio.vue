<script setup>
const props = defineProps({ modelValue:[String,Number], value:[String,Number], name:String, disabled:Boolean })
const emit = defineEmits(['update:modelValue'])
</script>
<template>
  <input type="radio" :name="props.name" :value="props.value" :checked="props.modelValue===props.value"
         :disabled="props.disabled" @change="emit('update:modelValue',props.value)"
         class="border-glass bg-black/30 text-brand-500 focus:ring-brand-500/20" />
</template>