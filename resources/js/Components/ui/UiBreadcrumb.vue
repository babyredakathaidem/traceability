<script setup>
defineProps({ items:Array }) // [{label,href}]
</script>
<template>
  <nav class="text-sm text-white/60">
    <ol class="flex flex-wrap items-center gap-2">
      <li v-for="(it,i) in items" :key="it.href||it.label" class="flex items-center gap-2">
        <a v-if="it.href" :href="it.href" class="text-brand-300 hover:underline">{{ it.label }}</a>
        <span v-else class="text-white/80">{{ it.label }}</span>
        <span v-if="i < items.length-1" class="text-white/40">/</span>
      </li>
    </ol>
  </nav>
</template>