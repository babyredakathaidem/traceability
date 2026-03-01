<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import UiButton from '@/Components/ui/UiButton.vue'
import UiBadge from '@/Components/ui/UiBadge.vue'

const page = usePage()
const user = computed(() => page.props?.auth?.user || null)
const isSuperAdmin = computed(() => !!user.value?.is_super_admin)

const logout = () => router.post('/logout')
</script>

<template>
  <header class="h-16 border-b border-glass bg-black/15 backdrop-blur flex items-center justify-between px-6">
    <div class="flex items-center gap-3">
      <div class="text-white/80 text-sm">
        Xin chào <span class="text-brand-200 font-semibold">{{ user?.name ?? 'User' }}</span>
      </div>
      <UiBadge :variant="isSuperAdmin ? 'brand' : 'default'">
        {{ isSuperAdmin ? 'SUPER ADMIN' : 'ENTERPRISE' }}
      </UiBadge>
    </div>

    <UiButton variant="outline" size="sm" @click="logout">Đăng xuất</UiButton>
  </header>
</template>