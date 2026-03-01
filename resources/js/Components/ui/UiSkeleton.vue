<script setup>
defineProps({ w:{type:String,default:'w-full'}, h:{type:String,default:'h-4'} })
</script>
<template>
  <div :class="`${w} ${h} rounded-lg bg-white/10 animate-pulse`"></div>
</template>