<script setup>
import { reactive } from 'vue'

const state = reactive({ list: [] })

const ICONS = {
  success: '✅',
  danger:  '❌',
  brand:   '🔔',
  default: 'ℹ️',
}

const push = (message, variant = 'brand') => {
  const id = Date.now() + Math.random()
  state.list.push({ id, message, variant })
  setTimeout(() => {
    state.list = state.list.filter(x => x.id !== id)
  }, 4000)
}

const dismiss = (id) => {
  state.list = state.list.filter(x => x.id !== id)
}

defineExpose({ push })
</script>

<template>
  <Teleport to="body">
    <div class="fixed right-4 top-4 z-[9999] flex flex-col gap-2 pointer-events-none" style="min-width:280px;max-width:380px">
      <TransitionGroup
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 translate-x-8"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 translate-x-8"
      >
        <div
          v-for="t in state.list"
          :key="t.id"
          class="pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-xl border backdrop-blur-md shadow-lg cursor-pointer"
          :class="{
            'border-green-500/30 bg-green-500/10 text-green-200':  t.variant === 'success',
            'border-red-400/30   bg-red-400/10   text-red-200':    t.variant === 'danger',
            'border-brand-500/30 bg-brand-500/10 text-brand-200':  t.variant === 'brand',
            'border-glass        bg-white/5      text-white/80':    !['success','danger','brand'].includes(t.variant),
          }"
          @click="dismiss(t.id)"
        >
          <span class="text-base mt-0.5 shrink-0">{{ ICONS[t.variant] ?? ICONS.default }}</span>
          <span class="text-sm leading-snug flex-1">{{ t.message }}</span>
          <button class="opacity-40 hover:opacity-80 transition text-xs shrink-0 mt-0.5">✕</button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>