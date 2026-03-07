<script setup>
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import UiButton from '@/Components/ui/UiButton.vue'
import UiBadge from '@/Components/ui/UiBadge.vue'

const page = usePage()
const user = computed(() => page.props?.auth?.user || null)
const isSuperAdmin = computed(() => !!user.value?.is_super_admin)

const enterpriseName = computed(() => page.props?.auth?.enterprise?.name ?? null)

const logout = () => router.post('/logout')
</script>

<template>
  <header class="h-16 border-b border-glass bg-black/15 backdrop-blur flex items-center justify-between px-6">
    <div class="flex items-center gap-3">
      <div class="text-white/80 text-sm">
        Xin chào <span class="text-brand-200 font-semibold">{{ user?.name ?? 'User' }}</span>
      </div>
      <!-- Super admin badge -->
      <UiBadge v-if="isSuperAdmin" variant="brand">SUPER ADMIN</UiBadge>
      <!-- Tên DN thay vì "ENTERPRISE" -->
      <UiBadge v-else-if="enterpriseName" variant="default">{{ enterpriseName }}</UiBadge>
      <UiBadge v-else variant="default">ENTERPRISE</UiBadge>
    </div>

    <div class="flex items-center gap-2">
      <!-- Profile link -->
      <Link :href="route('profile.edit')"
        class="text-xs text-white/40 hover:text-white/70 transition px-2 py-1 rounded-lg hover:bg-white/5">
        👤 Hồ sơ
      </Link>
      <UiButton variant="outline" size="sm" @click="logout">Đăng xuất</UiButton>
    </div>
  </header>
</template>