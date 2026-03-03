<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const STAFF_MODE = 'hide'
const collapsed  = ref(false)

const page = usePage()
const user = computed(() => page.props?.auth?.user ?? null)

const isSuperAdmin      = computed(() => !!user.value?.is_super_admin)
const role              = computed(() => user.value?.role ?? null)
const isEnterpriseAdmin = computed(() => role.value === 'enterprise_admin')
const isStaff           = computed(() => role.value === 'enterprise_staff')
const hasEnterprise     = computed(() => !!user.value?.enterprise_id)
const perms             = computed(() => new Set(user.value?.permissions ?? []))

function canAny(required = []) {
  if (!required?.length) return true
  for (const p of required) if (perms.value.has(p)) return true
  return false
}
function canAll(required = []) {
  if (!required?.length) return true
  for (const p of required) if (!perms.value.has(p)) return false
  return true
}

const sections = computed(() => {
  if (isSuperAdmin.value) return [{
    title: 'Hệ thống',
    items: [
      { label: 'Duyệt doanh nghiệp', href: '/sys/enterprises' },
      { label: 'Cấu hình hệ thống',  href: '/sys/settings' },
    ],
  }]

  if (hasEnterprise.value) return [
    {
      title: 'Doanh nghiệp',
      items: [
        { label: 'Dashboard', href: '/dashboard' },
        { label: 'Sản phẩm', href: '/products', requireAnyPerm: ['enterprise.products.view','enterprise.products.manage'] },
        { label: 'Lô hàng',  href: '/batches',  requireAnyPerm: ['enterprise.batches.view','enterprise.batches.manage'] },
        { label: 'Sự kiện truy xuất', href: '/events', requireAnyPerm: ['enterprise.trace_events.view','enterprise.trace_events.create','enterprise.trace_events.manage'] },
        { label: 'QR Codes', href: '/qrcodes', requireAnyPerm: ['enterprise.qrcodes.view','enterprise.qrcodes.manage'] },
      ],
    },
    {
      title: 'Quản trị',
      items: [
        { label: 'Tài khoản nhân sự',    href: '/enterprise/users',    adminOnly: true },
        { label: 'Cài đặt doanh nghiệp', href: '/enterprise/settings', adminOnly: true },
      ],
    },
  ]

  return [{ title: 'Bắt đầu', items: [{ label: 'Đăng ký doanh nghiệp', href: '/onboarding/enterprise' }] }]
})

function shouldShow(item) {
  if (item.adminOnly) return isEnterpriseAdmin.value
  if (!item.requireAnyPerm && !item.requireAllPerm) return true
  if (isStaff.value) {
    const ok = canAny(item.requireAnyPerm) && canAll(item.requireAllPerm)
    return STAFF_MODE === 'hide' ? ok : true
  }
  return true
}
function isEnabled(item) {
  if (item.adminOnly) return isEnterpriseAdmin.value
  return canAny(item.requireAnyPerm) && canAll(item.requireAllPerm)
}
function sectionHasVisibleItems(s) {
  return s.items.some(i => shouldShow(i))
}

const currentPath = computed(() => page.url)
function isActive(href) {
  return currentPath.value === href || currentPath.value.startsWith(href + '?')
}
function itemClass(href, enabled) {
  if (!enabled) return 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-white/20 cursor-not-allowed select-none'
  return ['flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition',
    isActive(href)
      ? 'bg-brand-500/15 text-brand-300 border border-brand-500/20'
      : 'text-white/55 hover:text-white/90 hover:bg-white/5',
  ].join(' ')
}

const roleLabel = computed(() => {
  if (isSuperAdmin.value)      return 'System Admin'
  if (isEnterpriseAdmin.value) return 'Admin DN'
  if (isStaff.value)           return 'Nhân viên DN'
  return 'Người dùng'
})

// Ký hiệu hiển thị khi thu gọn
const icons = {
  'Dashboard':             '⊟',
  'Sản phẩm':              '⊞',
  'Lô hàng':               '⊠',
  'Sự kiện truy xuất':     '⊕',
  'QR Codes':              '⊟',
  'Tài khoản nhân sự':     '⊛',
  'Cài đặt doanh nghiệp':  '⊙',
  'Duyệt doanh nghiệp':    '⊘',
  'Cấu hình hệ thống':     '⊗',
  'Đăng ký doanh nghiệp':  '▶',
}
</script>

<template>
  <aside
    class="hidden md:flex flex-col border-r border-glass bg-black/20 backdrop-blur shrink-0 transition-all duration-200"
    :class="collapsed ? 'w-14' : 'w-56'"
  >
    <!-- Header -->
    <div class="h-14 flex items-center gap-2.5 border-b border-white/5 px-3">
      <div class="w-8 h-8 rounded-lg bg-brand-500 text-cosmic-950 flex items-center justify-center font-extrabold text-[11px] shrink-0">
        {{ isSuperAdmin ? 'SYS' : 'DN' }}
      </div>
      <div v-if="!collapsed" class="flex-1 min-w-0">
        <div class="text-sm font-bold text-white/90 truncate">AGU Truy Xuất</div>
        <div class="text-[11px] text-white/40 truncate">{{ roleLabel }}</div>
      </div>
      <!-- Toggle button -->
      <button @click="collapsed = !collapsed"
        class="ml-auto w-6 h-6 flex items-center justify-center text-white/25 hover:text-white/60 transition shrink-0"
        :title="collapsed ? 'Mở rộng' : 'Thu gọn'">
        <svg class="w-3.5 h-3.5 transition-transform" :class="collapsed ? 'rotate-180' : ''"
          fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
    </div>

    <!-- Nav -->
    <nav class="flex-1 overflow-y-auto py-3 space-y-3" :class="collapsed ? 'px-1.5' : 'px-2'">
      <template v-for="sec in sections" :key="sec.title">
        <div v-if="sectionHasVisibleItems(sec)">
          <div v-if="!collapsed"
            class="px-3 mb-1 text-[10px] tracking-widest text-white/25 font-semibold uppercase">
            {{ sec.title }}
          </div>
          <div v-else class="h-px bg-white/8 mx-2 mb-2" />

          <div class="space-y-0.5">
            <template v-for="it in sec.items" :key="it.href">
              <Link v-if="shouldShow(it)"
                :href="isEnabled(it) ? it.href : '#'"
                :class="itemClass(it.href, isEnabled(it))"
                :title="collapsed ? it.label : undefined">
                <span class="shrink-0 w-5 text-center text-sm leading-none opacity-70">
                  {{ icons[it.label] ?? '·' }}
                </span>
                <span v-if="!collapsed" class="truncate">{{ it.label }}</span>
              </Link>
            </template>
          </div>
        </div>
      </template>
    </nav>

    <!-- Footer -->
    <div class="border-t border-white/5 p-3">
      <div v-if="!collapsed" class="text-[11px] text-white/25 truncate">{{ user?.email }}</div>
      <div v-else
        class="w-7 h-7 rounded-full bg-white/8 flex items-center justify-center text-xs text-white/35 mx-auto font-medium">
        {{ user?.name?.[0]?.toUpperCase() ?? '?' }}
      </div>
    </div>
  </aside>
</template>