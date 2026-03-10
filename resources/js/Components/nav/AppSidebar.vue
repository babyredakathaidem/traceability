<script setup>
import { computed, ref, markRaw } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { 
  ChartBarIcon, 
  Squares2X2Icon, 
  CubeIcon, 
  ArchiveBoxIcon, 
  ClipboardDocumentListIcon, 
  QrCodeIcon, 
  UsersIcon, 
  Cog6ToothIcon,
  AcademicCapIcon,
  ShieldCheckIcon,
  WrenchScrewdriverIcon,
  ArrowRightCircleIcon,
  ChevronLeftIcon
} from '@heroicons/vue/24/outline'

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
        { 
            label: 'Duyệt doanh nghiệp', 
            href: '/sys/enterprises', 
            icon: ShieldCheckIcon 
        },
        { 
            label: 'Cấu hình hệ thống',  
            href: '/sys/settings',    
            icon: WrenchScrewdriverIcon 
        },
        // ── Thêm các thẻ mới vào đây ──────────────────
        { 
            label: 'Thống kê tổng quát', 
            href: '/sys/stats',       
            icon: ChartBarIcon          // import thêm từ heroicons
        },
        { 
            label: 'Cấu hình CTE/TCVN', 
            href: '/sys/config',      
            icon: Cog6ToothIcon 
        },
        { 
            label: 'Quản lý tài khoản', 
            href: '/sys/users',       
            icon: UsersIcon 
        },
    ],
}]

  if (hasEnterprise.value) return [
    {
      title: 'Doanh nghiệp',
      items: [
        { label: 'Dashboard', href: '/dashboard', icon: Squares2X2Icon },
        { label: 'Sản phẩm', href: '/products', icon: CubeIcon, requireAnyPerm: ['enterprise.products.view','enterprise.products.manage'] },
        { label: 'Lô hàng',  href: '/batches',  icon: ArchiveBoxIcon, requireAnyPerm: ['enterprise.batches.view','enterprise.batches.manage'] },
        { label: 'Sự kiện truy xuất', href: '/events', icon: ClipboardDocumentListIcon, requireAnyPerm: ['enterprise.trace_events.view','enterprise.trace_events.create','enterprise.trace_events.manage'] },
        { label: 'Chứng chỉ', href: route('certificates.index'), icon: AcademicCapIcon, requireAnyPerm: ['enterprise.certificates.view','enterprise.certificates.manage'] },
        { label: 'QR Codes', href: '/qrcodes', icon: QrCodeIcon, requireAnyPerm: ['enterprise.qrcodes.view','enterprise.qrcodes.manage'] },
      ],
    },
    {
      title: 'Quản trị',
      items: [
        { label: 'Tài khoản nhân sự',    href: '/enterprise/users',    icon: UsersIcon, adminOnly: true },
        { label: 'Cài đặt doanh nghiệp', href: '/enterprise/settings', icon: Cog6ToothIcon, adminOnly: true },
      ],
    },
  ]

  return [{ title: 'Bắt đầu', items: [{ label: 'Đăng ký doanh nghiệp', href: '/onboarding/enterprise', icon: ArrowRightCircleIcon }] }]
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
  if (!item.requireAnyPerm && !item.requireAllPerm) return true
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
  return ['flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition group',
    isActive(href)
      ? 'bg-brand-500/15 text-brand-300 border border-brand-500/20 shadow-lg shadow-brand-500/5'
      : 'text-white/55 hover:text-white/90 hover:bg-white/5',
  ].join(' ')
}

const roleLabel = computed(() => {
  if (isSuperAdmin.value)      return 'System Admin'
  if (isEnterpriseAdmin.value) return 'Admin DN'
  if (isStaff.value)           return 'Nhân viên DN'
  return 'Người dùng'
})
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
        <div class="text-sm font-bold text-white/90 truncate">AGU Traceability</div>
        <div class="text-[11px] text-white/40 truncate">{{ roleLabel }}</div>
      </div>
      <!-- Toggle button -->
      <button @click="collapsed = !collapsed"
        class="ml-auto w-6 h-6 flex items-center justify-center text-white/25 hover:text-white/60 transition shrink-0"
        :title="collapsed ? 'Mở rộng' : 'Thu gọn'">
        <ChevronLeftIcon class="w-4 h-4 transition-transform" :class="collapsed ? 'rotate-180' : ''" stroke-width="2.5" />
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
                <component :is="it.icon" 
                  class="shrink-0 w-5 h-5 transition-colors" 
                  :class="isActive(it.href) ? 'text-brand-300' : 'text-white/35 group-hover:text-white/80'" 
                />
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