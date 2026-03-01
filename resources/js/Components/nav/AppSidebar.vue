<!-- resources/js/Components/nav/AppSidebar.vue -->
<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

// 'hide'    → ẩn hẳn item nếu không có quyền
// 'disable' → vẫn show nhưng grey out
const STAFF_MODE = 'hide'

const page = usePage()
const user = computed(() => page.props?.auth?.user ?? null)

const isSuperAdmin     = computed(() => !!user.value?.is_super_admin)
const role             = computed(() => user.value?.role ?? null)
const isEnterpriseAdmin = computed(() => role.value === 'enterprise_admin')
const isStaff          = computed(() => role.value === 'enterprise_staff')
const hasEnterprise    = computed(() => !!user.value?.enterprise_id)
const perms            = computed(() => new Set(user.value?.permissions ?? []))

// ── permission helpers ────────────────────────────────────────
function canAny(required = []) {
  if (!required || required.length === 0) return true
  for (const p of required) if (perms.value.has(p)) return true
  return false
}

function canAll(required = []) {
  if (!required || required.length === 0) return true
  for (const p of required) if (!perms.value.has(p)) return false
  return true
}

// ── menu definitions ─────────────────────────────────────────
// item schema:
// {
//   label: string
//   href: string
//   icon?: string
//   adminOnly?: boolean          → ẩn hẳn với enterprise_staff
//   requireAnyPerm?: string[]   → cần ít nhất 1 permission
//   requireAllPerm?: string[]   → cần tất cả permissions
// }

const sections = computed(() => {
  // ── SUPER ADMIN ──────────────────────────────────────────
  if (isSuperAdmin.value) {
    return [
      {
        title: 'Hệ thống',
        items: [
          {
            label: 'Duyệt doanh nghiệp',
            href: '/sys/enterprises',
          },
          {
            label: 'Cấu hình hệ thống',
            href: '/sys/settings',
          },
        ],
      },
    ]
  }

  // ── ENTERPRISE (admin DN + nhân viên) ────────────────────
  if (hasEnterprise.value) {
    return [
      {
        title: 'Doanh nghiệp',
        items: [
          {
            label: 'Dashboard',
            href: '/dashboard',
            // không cần permission — ai cũng xem được
          },
          {
            label: 'Sản phẩm',
            href: '/products',
            requireAnyPerm: ['enterprise.products.view', 'enterprise.products.manage'],
          },
          {
            label: 'Lô hàng',
            href: '/batches',
            requireAnyPerm: ['enterprise.batches.view', 'enterprise.batches.manage'],
          },
          {
            label: 'Sự kiện truy xuất',
            href: '/trace-events',
            requireAnyPerm: [
              'enterprise.trace_events.view',
              'enterprise.trace_events.create',
              'enterprise.trace_events.manage',
            ],
          },
          {
            label: 'QR Codes',
            href: '/qrcodes',
            requireAnyPerm: ['enterprise.qrcodes.view', 'enterprise.qrcodes.manage'],
          },
        ],
      },
      {
        title: 'Quản trị',
        items: [
          {
            label: 'Tài khoản nhân sự',
            href: '/enterprise/users',
            adminOnly: true, // ← chỉ admin DN thấy, nhân viên ẩn hẳn
          },
          {
            label: 'Cài đặt doanh nghiệp',
            href: '/enterprise/settings',
            adminOnly: true, // ← chỉ admin DN thấy
          },
        ],
      },
    ]
  }

  // ── CHƯA CÓ DN ──────────────────────────────────────────
  return [
    {
      title: 'Bắt đầu',
      items: [
        { label: 'Đăng ký doanh nghiệp', href: '/onboarding/enterprise' },
      ],
    },
  ]
})

// ── visibility logic ─────────────────────────────────────────
function shouldShow(item) {
  // item chỉ dành cho admin DN
  if (item.adminOnly) {
    return isEnterpriseAdmin.value
  }

  // không có permission requirement → luôn hiện
  if (!item.requireAnyPerm && !item.requireAllPerm) return true

  // Staff: dựa theo STAFF_MODE
  if (isStaff.value) {
    const enabled = canAny(item.requireAnyPerm) && canAll(item.requireAllPerm)
    if (STAFF_MODE === 'hide') return enabled
    return true // 'disable' mode: vẫn show
  }

  // Admin DN đã có full permissions trong perms set → luôn true
  return true
}

function isEnabled(item) {
  if (item.adminOnly) return isEnterpriseAdmin.value
  return canAny(item.requireAnyPerm) && canAll(item.requireAllPerm)
}

function itemClass(enabled) {
  return [
    'block px-3 py-2 rounded-xl text-sm transition font-medium',
    enabled
      ? 'text-white/80 hover:bg-white/8 hover:text-brand-200 cursor-pointer'
      : 'text-white/30 bg-black/10 cursor-not-allowed pointer-events-none select-none opacity-50',
  ].join(' ')
}

// ── section visibility ─────────────────────────────────────
// Ẩn hẳn section nếu tất cả items đều bị ẩn
function sectionHasVisibleItems(section) {
  return section.items.some(item => shouldShow(item))
}

// ── role label ────────────────────────────────────────────
const roleLabel = computed(() => {
  if (isSuperAdmin.value) return 'System Admin'
  if (isEnterpriseAdmin.value) return 'Admin DN'
  if (isStaff.value) return 'Nhân viên DN'
  return 'Người dùng'
})
</script>

<template>
  <aside class="w-64 hidden md:flex flex-col border-r border-glass bg-black/20 backdrop-blur">
    <!-- Header -->
    <div class="p-5 flex items-center gap-3 border-b border-white/5">
      <div class="h-9 w-9 rounded-xl bg-brand-500 text-cosmic-950 flex items-center justify-center font-extrabold text-xs shrink-0">
        {{ isSuperAdmin ? 'SYS' : 'DN' }}
      </div>
      <div class="min-w-0">
        <div class="font-extrabold text-white/90 truncate">AGU Truy Xuất</div>
        <div class="text-xs text-white/50 truncate">{{ roleLabel }}</div>
      </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-5">
      <template v-for="sec in sections" :key="sec.title">
        <div v-if="sectionHasVisibleItems(sec)">
          <div class="px-3 mb-2 text-[10px] tracking-widest text-white/40 font-semibold uppercase">
            {{ sec.title }}
          </div>

          <div class="space-y-0.5">
            <template v-for="it in sec.items" :key="it.href">
              <Link
                v-if="shouldShow(it)"
                :href="isEnabled(it) ? it.href : '#'"
                :class="itemClass(isEnabled(it))"
              >
                {{ it.label }}
              </Link>
            </template>
          </div>
        </div>
      </template>
    </nav>

    <!-- Footer: user info -->
    <div class="p-4 border-t border-white/5">
      <div class="text-xs text-white/40 truncate">{{ user?.email }}</div>
    </div>
  </aside>
</template>