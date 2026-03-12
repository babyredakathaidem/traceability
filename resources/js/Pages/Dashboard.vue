<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props?.auth?.user ?? null)

const props = defineProps({
  stats:         { type: Object, default: () => ({}) },
  scanTrend:     { type: Array,  default: () => [] },
  batchProgress: { type: Array,  default: () => [] },
  recentRecalls: { type: Array,  default: () => [] },
  recentScans:   { type: Array,  default: () => [] },
  topBatches:    { type: Array,  default: () => [] },
})

// ── Biểu đồ scan trend ────────────────────────────────────────
const maxScan = computed(() => {
  const m = Math.max(...props.scanTrend.map(d => d.count), 1)
  return m
})

function barHeight(count) {
  if (maxScan.value === 0) return 0
  return Math.max((count / maxScan.value) * 100, count > 0 ? 4 : 0)
}

// ── Màu theo decision ────────────────────────────────────────
function decisionClass(d) {
  return {
    allowed: 'text-green-400 bg-green-400/10 border-green-400/20',
    blocked: 'text-red-400 bg-red-400/10 border-red-400/20',
    expired: 'text-orange-400 bg-orange-400/10 border-orange-400/20',
    invalid: 'text-white/40 bg-white/5 border-white/10',
  }[d] ?? 'text-white/40 bg-white/5 border-white/10'
}

function decisionLabel(d) {
  return { allowed: 'Cho phép', blocked: 'Chặn', expired: 'Hết hạn', invalid: 'Không hợp lệ' }[d] ?? d
}

function statusClass(s) {
  return s === 'active'
    ? 'text-green-400 bg-green-400/10 border-green-400/20'
    : s === 'recalled'
    ? 'text-red-400 bg-red-400/10 border-red-400/20'
    : 'text-white/40 bg-white/5 border-white/10'
}

// ── Top batches bar ───────────────────────────────────────────
const maxTopScan = computed(() => Math.max(...props.topBatches.map(b => b.scan_count), 1))
</script>

<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <div class="text-xs text-white/40 uppercase tracking-widest">Dashboard</div>
        <div class="text-2xl font-extrabold text-white/90 mt-1">Xin chào, {{ user?.name }}</div>
        <div class="text-sm text-white/40 mt-0.5">
          {{ user?.role === 'enterprise_admin' ? 'Admin Doanh nghiệp' : 'Nhân viên Doanh nghiệp' }}
        </div>
      </div>
    </div>

    <!-- Quick Actions — Lối tắt ghi sự kiện nhanh -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" data-aos="fade-down">
      <Link :href="route('transfer.in.create')" 
        class="group p-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/5 hover:bg-emerald-500/10 hover:border-emerald-500/50 transition-all duration-300 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400 group-hover:scale-110 transition-transform">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
          </svg>
        </div>
        <div>
          <div class="text-sm font-bold text-emerald-400">Nhập hàng</div>
          <div class="text-[10px] text-white/40 uppercase font-bold tracking-tighter">Tạo lô nguyên liệu mới</div>
        </div>
      </Link>

      <Link :href="route('transformation-events.create')" 
        class="group p-4 rounded-2xl border border-brand-500/30 bg-brand-500/5 hover:bg-brand-500/10 hover:border-brand-500/50 transition-all duration-300 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-brand-500/20 flex items-center justify-center text-brand-400 group-hover:scale-110 transition-transform">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
          </svg>
        </div>
        <div>
          <div class="text-sm font-bold text-brand-400">Chế biến / Thu hoạch</div>
          <div class="text-[10px] text-white/40 uppercase font-bold tracking-tighter">Từ lô cũ → đẻ lô mới</div>
        </div>
      </Link>

      <Link :href="route('transfer.out.create')" 
        class="group p-4 rounded-2xl border border-purple-500/30 bg-purple-500/5 hover:bg-purple-500/10 hover:border-purple-500/50 transition-all duration-300 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center text-purple-400 group-hover:scale-110 transition-transform">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
          </svg>
        </div>
        <div>
          <div class="text-sm font-bold text-purple-400">Chuyển giao đi</div>
          <div class="text-[10px] text-white/40 uppercase font-bold tracking-tighter">Gửi hàng cho đối tác</div>
        </div>
      </Link>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">

      <Link href="/products" class="bg-white/5 border border-glass rounded-2xl p-5 hover:bg-white/8 hover:-translate-y-1 transition-all duration-300 block shadow-lg" data-aos="fade-up" data-aos-delay="100">
        <div class="text-[10px] text-white/40 uppercase tracking-widest font-black">Sản phẩm</div>
        <div class="text-3xl font-black text-white mt-2">{{ stats.products ?? 0 }}</div>
        <div class="text-[10px] text-white/20 mt-1 uppercase font-bold">Danh mục</div>
      </Link>

      <Link href="/trace-locations" class="bg-white/5 border border-glass rounded-2xl p-5 hover:bg-white/8 hover:-translate-y-1 transition-all duration-300 block shadow-lg border-l-brand-500/30" data-aos="fade-up" data-aos-delay="150">
        <div class="text-[10px] text-brand-400 uppercase tracking-widest font-black">Địa điểm</div>
        <div class="text-3xl font-black text-white mt-2">{{ stats.locations_count ?? 0 }}</div>
        <div class="text-[10px] text-white/20 mt-1 uppercase font-bold">Ruộng & Kho</div>
      </Link>

      <Link href="/batches" class="bg-white/5 border border-glass rounded-2xl p-5 hover:bg-white/8 hover:-translate-y-1 transition-all duration-300 block shadow-lg" data-aos="fade-up" data-aos-delay="200">
        <div class="text-[10px] text-white/40 uppercase tracking-widest font-black">Lô hàng</div>
        <div class="text-3xl font-black text-white mt-2">{{ stats.batches?.total ?? 0 }}</div>
        <div class="flex gap-2 mt-2 flex-wrap">
          <span class="text-[9px] font-bold text-green-400 uppercase bg-green-400/10 px-1 rounded">{{ stats.batches?.active ?? 0 }} ACT</span>
        </div>
      </Link>

      <Link href="/events" class="bg-white/5 border border-glass rounded-2xl p-5 hover:bg-white/8 hover:-translate-y-1 transition-all duration-300 block" data-aos="fade-up" data-aos-delay="300">
        <div class="text-xs text-white/40 uppercase tracking-wider">Sự kiện</div>
        <div class="text-3xl font-extrabold text-white/90 mt-2">{{ stats.events?.total ?? 0 }}</div>
        <div class="flex gap-2 mt-2 flex-wrap">
          <span class="text-xs text-brand-400">{{ stats.events?.published ?? 0 }} published</span>
          <span class="text-xs text-white/40">{{ stats.events?.draft ?? 0 }} draft</span>
        </div>
      </Link>

      <div class="bg-white/5 border border-glass rounded-2xl p-5 hover:-translate-y-1 transition-all duration-300 block" data-aos="fade-up" data-aos-delay="400">
        <div class="text-xs text-white/40 uppercase tracking-wider">Lượt quét QR</div>
        <div class="text-3xl font-extrabold text-white/90 mt-2">{{ stats.scans?.total ?? 0 }}</div>
        <div class="flex gap-2 mt-2 flex-wrap">
          <span class="text-xs text-green-400">{{ stats.scans?.allowed ?? 0 }} hợp lệ</span>
          <span class="text-xs text-red-400">{{ stats.scans?.blocked ?? 0 }} chặn</span>
        </div>
      </div>

    </div>

    <!-- Scan trend + Top batches -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

      <!-- Biểu đồ scan 14 ngày -->
      <div class="lg:col-span-2 bg-white/5 border border-glass rounded-2xl p-5" data-aos="fade-right" data-aos-delay="500">
        <div class="text-xs text-white/40 uppercase tracking-wider mb-4">Lượt quét — 14 ngày gần nhất</div>

        <div class="flex items-end gap-1 h-32">
          <template v-for="d in scanTrend" :key="d.day">
            <div class="flex-1 flex flex-col items-center gap-1 group relative">
              <!-- Tooltip -->
              <div class="absolute bottom-full mb-1 hidden group-hover:flex flex-col items-center z-10">
                <div class="bg-black/80 border border-glass rounded-lg px-2 py-1 text-xs text-white/80 whitespace-nowrap">
                  {{ d.label }}: {{ d.count }} lượt ({{ d.allowed }} hợp lệ)
                </div>
              </div>
              <!-- Bar tổng -->
              <div
                class="w-full rounded-t-sm bg-brand-500/30 transition-all"
                :style="{ height: barHeight(d.count) + '%' }"
              ></div>
            </div>
          </template>
        </div>

        <!-- Labels -->
        <div class="flex gap-1 mt-1">
          <template v-for="d in scanTrend" :key="d.day + '-label'">
            <div class="flex-1 text-center text-[9px] text-white/30 leading-none">
              {{ d.label }}
            </div>
          </template>
        </div>

        <!-- Tổng 14 ngày -->
        <div class="mt-3 flex gap-4 text-xs text-white/50">
          <span>Tổng: <span class="text-white/80 font-semibold">{{ scanTrend.reduce((s, d) => s + d.count, 0) }}</span></span>
          <span>Hợp lệ: <span class="text-green-400 font-semibold">{{ scanTrend.reduce((s, d) => s + d.allowed, 0) }}</span></span>
        </div>
      </div>

      <!-- Top lô được quét -->
      <div class="bg-white/5 border border-glass rounded-2xl p-5" data-aos="fade-left" data-aos-delay="600">
        <div class="text-xs text-white/40 uppercase tracking-wider mb-4">Top lô được quét nhiều nhất</div>

        <div v-if="topBatches.length === 0" class="text-sm text-white/30 text-center py-4">
          Chưa có dữ liệu
        </div>

        <div v-else class="space-y-3">
          <div v-for="b in topBatches" :key="b.batch_code" class="space-y-1">
            <div class="flex justify-between text-xs">
              <span class="text-white/70 font-mono">{{ b.batch_code }}</span>
              <span class="text-brand-400 font-semibold">{{ b.scan_count }}</span>
            </div>
            <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
              <div
                class="h-full bg-brand-500/70 rounded-full transition-all"
                :style="{ width: (b.scan_count / maxTopScan * 100) + '%' }"
              ></div>
            </div>
            <div class="text-[10px] text-white/30 truncate">{{ b.product_name }}</div>
          </div>
        </div>
      </div>

    </div>

    <!-- Batch progress + Scan log -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

      <!-- Tiến độ lô gần nhất -->
      <div class="bg-white/5 border border-glass rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
          <div class="text-xs text-white/40 uppercase tracking-wider">Tiến độ lô gần nhất</div>
          <Link href="/batches" class="text-xs text-brand-400 hover:underline">Xem tất cả</Link>
        </div>

        <div v-if="batchProgress.length === 0" class="text-sm text-white/30 text-center py-4">
          Chưa có lô hàng
        </div>

        <div v-else class="space-y-3">
          <div v-for="b in batchProgress" :key="b.id"
            class="flex items-center gap-3 py-2 border-b border-white/5 last:border-0">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="font-mono text-sm text-white/80">{{ b.code }}</span>
                <span :class="statusClass(b.status)"
                  class="text-[10px] px-1.5 py-0.5 rounded border">
                  {{ b.status === 'active' ? 'Hoạt động' : b.status === 'recalled' ? 'Thu hồi' : b.status }}
                </span>
              </div>
              <div class="text-xs text-white/40 mt-0.5 truncate">{{ b.product_name }}</div>
            </div>

            <div class="text-right shrink-0">
              <div class="text-xs text-white/60">
                <span :class="b.published === b.total && b.total > 0 ? 'text-green-400' : 'text-brand-400'">
                  {{ b.published }}
                </span>/{{ b.total }} sự kiện
              </div>
              <!-- Progress mini bar -->
              <div class="mt-1 w-20 h-1 bg-white/10 rounded-full overflow-hidden">
                <div
                  class="h-full rounded-full transition-all"
                  :class="b.total === 0 ? 'bg-white/20' : b.published === b.total ? 'bg-green-500/70' : 'bg-brand-500/70'"
                  :style="{ width: b.total > 0 ? (b.published / b.total * 100) + '%' : '0%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Scan log gần nhất -->
      <div class="bg-white/5 border border-glass rounded-2xl p-5">
        <div class="text-xs text-white/40 uppercase tracking-wider mb-4">Scan log gần nhất</div>

        <div v-if="recentScans.length === 0" class="text-sm text-white/30 text-center py-4">
          Chưa có lượt quét
        </div>

        <div v-else class="space-y-2">
          <div v-for="s in recentScans" :key="s.id"
            class="flex items-center gap-3 py-1.5 border-b border-white/5 last:border-0">
            <!-- Decision badge -->
            <span :class="decisionClass(s.decision)"
              class="text-[10px] px-1.5 py-0.5 rounded border shrink-0 w-16 text-center">
              {{ decisionLabel(s.decision) }}
            </span>

            <div class="flex-1 min-w-0">
              <div class="text-xs text-white/60 truncate">
                {{ s.qr_type === 'public' ? 'Công khai' : 'Riêng tư' }}
                <template v-if="s.place"> · {{ s.place }}</template>
                <template v-if="s.distance_m != null"> · {{ s.distance_m }}m</template>
              </div>
              <div class="text-[10px] text-white/30">{{ s.ip }}</div>
            </div>

            <div class="text-[10px] text-white/30 shrink-0">{{ s.scanned_at }}</div>
          </div>
        </div>
      </div>

    </div>

    <!-- Thu hồi gần nhất -->
    <div v-if="recentRecalls.length > 0" class="bg-red-500/5 border border-red-500/20 rounded-2xl p-5">
      <div class="text-xs text-red-400/70 uppercase tracking-wider mb-4">Lô bị thu hồi gần nhất</div>

      <div class="space-y-2">
        <div v-for="r in recentRecalls" :key="r.id"
          class="flex items-start gap-3 py-2 border-b border-red-500/10 last:border-0">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span class="font-mono text-sm text-red-300">{{ r.batch_code }}</span>
              <span :class="r.status === 'resolved' ? 'text-green-400 border-green-400/20 bg-green-400/10' : 'text-red-400 border-red-400/20 bg-red-400/10'"
                class="text-[10px] px-1.5 py-0.5 rounded border">
                {{ r.status === 'resolved' ? 'Đã xử lý' : 'Đang thu hồi' }}
              </span>
            </div>
            <div class="text-xs text-white/50 mt-0.5 truncate">{{ r.reason }}</div>
            <div class="text-[10px] text-white/30 mt-0.5">{{ r.recalled_by }} · {{ r.recalled_at }}</div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>