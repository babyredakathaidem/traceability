<script setup>
import AppSidebar from '@/Components/nav/AppSidebar.vue'
import AppTopbar from '@/Components/nav/AppTopbar.vue'
</script>

<template>
  <div class="min-h-screen bg-cosmic text-white/90">
    <div class="flex">
      <AppSidebar />
      <div class="flex-1 min-w-0">
        <AppTopbar />
        <main class="p-6">
          <div class="bg-white/5 border border-glass rounded-2xl backdrop-blur-md shadow-glow-orange p-5">
            <slot />
          </div>
        </main>
      </div>
    </div>
  </div>
</template>