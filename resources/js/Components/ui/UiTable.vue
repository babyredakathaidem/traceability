<script setup>
defineProps({
  headers: { type: Array, default: () => [] }, // [{key,label}]
})
</script>

<template>
  <div class="overflow-x-auto rounded-2xl border border-glass bg-black/15">
    <table class="min-w-full text-sm">
      <thead class="bg-black/35 border-b border-glass">
        <tr>
          <th
            v-for="h in headers"
            :key="h.key"
            class="text-left px-4 py-3 text-white/70 font-extrabold"
          >
            {{ h.label }}
          </th>
        </tr>
      </thead>

      <tbody class="divide-y divide-white/10">
        <slot />
      </tbody>
    </table>
  </div>
</template>