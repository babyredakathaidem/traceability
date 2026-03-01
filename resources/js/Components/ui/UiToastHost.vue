<script setup>
import { reactive } from 'vue'

const state = reactive({ list: [] })

const push = (message, variant='brand') => {
  const id = Date.now()+Math.random()
  state.list.push({ id, message, variant })
  setTimeout(()=> state.list = state.list.filter(x=>x.id!==id), 3000)
}

defineExpose({ push })
</script>

<template>
  <div class="fixed right-4 top-4 z-50 space-y-2">
    <div v-for="t in state.list" :key="t.id"
      class="px-4 py-3 rounded-xl border backdrop-blur-md shadow-glow-orange"
      :class="t.variant==='brand' ? 'border-brand-500/30 bg-brand-500/10 text-brand-200' :
              t.variant==='danger' ? 'border-red-400/30 bg-red-400/10 text-red-200' :
              'border-glass bg-white/5 text-white/80'">
      {{ t.message }}
    </div>
  </div>
</template>