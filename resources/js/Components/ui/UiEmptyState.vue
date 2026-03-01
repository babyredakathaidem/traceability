<script setup>
defineProps({ title:String, desc:String })
</script>
<template>
  <div class="text-center py-10">
    <div class="text-brand-200 font-extrabold text-xl">{{ title }}</div>
    <div class="text-white/60 mt-2">{{ desc }}</div>
    <div class="mt-5"><slot /></div>
  </div>
</template>