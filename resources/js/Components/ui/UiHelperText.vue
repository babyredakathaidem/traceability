<script setup>
defineProps({ type:{type:String,default:'hint'} }) // hint|error|success
</script>
<template>
  <p class="text-xs mt-1"
     :class="type==='error'?'text-red-400':type==='success'?'text-emerald-300':'text-white/50'">
    <slot />
  </p>
</template>