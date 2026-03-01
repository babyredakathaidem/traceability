<script setup>
import { ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import UiButton from '@/Components/ui/UiButton.vue'

const page = usePage()
const open = ref(false)
const logout = () => router.post('/logout')
</script>

<template>
  <div class="min-h-screen bg-cosmic text-white/90">
    <header class="border-b border-glass bg-black/15 backdrop-blur">
      <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
        <Link href="/dashboard" class="flex items-center gap-3">
          <div class="h-9 w-9 rounded-xl bg-brand-500 text-cosmic-950 flex items-center justify-center font-extrabold">✓</div>
          <div class="font-extrabold">AGU</div>
        </Link>

        <div class="flex items-center gap-3">
          <div class="hidden md:block text-sm text-white/70">
            {{ page.props?.auth?.user?.name }} • {{ page.props?.auth?.user?.email }}
          </div>
          <UiButton variant="outline" size="sm" @click="logout">Đăng xuất</UiButton>
          <button class="md:hidden p-2 rounded-lg hover:bg-white/5" @click="open = !open">☰</button>
        </div>
      </div>

      <div v-if="open" class="md:hidden border-t border-glass bg-black/20">
        <div class="px-6 py-3 space-y-2">
          <Link href="/dashboard" class="block text-white/80 hover:text-brand-200">Dashboard</Link>
          <Link href="/profile" class="block text-white/80 hover:text-brand-200">Profile</Link>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8">
      <slot />
    </main>
  </div>
</template>