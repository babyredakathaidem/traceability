<script setup>
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const user = page.props?.auth?.user
</script>

<template>
  <div>
    <div class="mb-6">
      <div class="text-sm text-white/50 font-semibold uppercase tracking-wider">Dashboard</div>
      <div class="text-2xl font-extrabold text-white/90 mt-1">
        Xin chào, {{ user?.name }} 👋
      </div>
      <div class="text-sm text-white/50 mt-1">
        {{ user?.role === 'enterprise_admin' ? 'Admin Doanh nghiệp' : 'Nhân viên Doanh nghiệp' }}
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white/5 border border-glass rounded-2xl p-5">
        <div class="text-xs text-white/40 uppercase tracking-wider">Hệ thống</div>
        <div class="text-2xl font-extrabold text-brand-200 mt-2">AGU Traceability</div>
        <div class="text-sm text-white/50 mt-1">Truy xuất nguồn gốc hàng hóa</div>
      </div>
    </div>
  </div>
</template>