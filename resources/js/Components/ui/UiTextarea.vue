<script setup>
import { computed } from 'vue'
const props = defineProps({ modelValue:String, rows:{type:Number,default:4}, placeholder:String, disabled:Boolean })
const emit = defineEmits(['update:modelValue'])
const cls = computed(() =>
  'w-full rounded-lg border border-glass bg-black/25 px-4 py-2.5 text-white/90 placeholder:text-white/40 '+
  'focus:border-brand-500/60 focus:ring-brand-500/20 hover:border-brand-500/30 transition '+
  (props.disabled ? 'opacity-60 cursor-not-allowed' : '')
)
</script>
<template>
  <textarea :rows="props.rows" :placeholder="props.placeholder" :disabled="props.disabled"
            :value="props.modelValue" @input="emit('update:modelValue',$event.target.value)" :class="cls"></textarea>
</template>