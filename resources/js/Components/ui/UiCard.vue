<script setup>
defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
})
</script>

<template>
  <div class="bg-white/5 border border-glass rounded-2xl backdrop-blur-md shadow-glow-orange">
    <div class="p-5">
      <div v-if="title" class="flex items-start justify-between gap-4 mb-4">
        <div>
          <div class="text-brand-200 font-extrabold text-lg">{{ title }}</div>
          <div v-if="subtitle" class="text-white/60 text-sm mt-1">{{ subtitle }}</div>
        </div>
        <slot name="headerRight" />
      </div>

      <slot />
    </div>
  </div>
</template>