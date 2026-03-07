<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
  batch: { type: Object, required: true },
  nodes: { type: Array,  default: () => [] },
  edges: { type: Array,  default: () => [] },
})

// ── Layout tự động: xếp nodes theo cột ───────────────────
// Dùng thuật toán topological sort đơn giản để xác định "depth"
const nodeDepths = computed(() => {
  const depths = {}
  const edgeMap = {} // from → [to]

  for (const e of props.edges) {
    if (!edgeMap[e.from]) edgeMap[e.from] = []
    edgeMap[e.from].push(e.to)
  }

  // BFS từ roots (nodes không có incoming edge)
  const incomingCount = {}
  for (const n of props.nodes) incomingCount[n.id] = 0
  for (const e of props.edges) {
    incomingCount[e.to] = (incomingCount[e.to] ?? 0) + 1
  }

  const queue = props.nodes.filter(n => (incomingCount[n.id] ?? 0) === 0).map(n => n.id)
  queue.forEach(id => depths[id] = 0)

  while (queue.length) {
    const cur = queue.shift()
    const children = edgeMap[cur] ?? []
    for (const child of children) {
      depths[child] = Math.max(depths[child] ?? 0, (depths[cur] ?? 0) + 1)
      queue.push(child)
    }
  }

  return depths
})

// Group nodes theo depth
const columns = computed(() => {
  const cols = {}
  for (const node of props.nodes) {
    const d = nodeDepths.value[node.id] ?? 0
    if (!cols[d]) cols[d] = []
    cols[d].push(node)
  }
  return Object.values(cols)
})

// Node được chọn để xem chi tiết
const selectedNode = ref(null)
function selectNode(node) {
  selectedNode.value = selectedNode.value?.id === node.id ? null : node
}

// ── Màu / style theo type ─────────────────────────────────
function nodeStyle(node) {
  const isCurrent = node.batch_id === props.batch.id
  if (isCurrent) return {
    border: 'border-brand-500/60',
    bg:     'bg-brand-500/10',
    label:  'text-brand-300',
    ring:   'ring-2 ring-brand-500/40',
  }
  const map = {
    parent:       { border: 'border-white/20', bg: 'bg-white/5',         label: 'text-white/70',   ring: '' },
    split_child:  { border: 'border-amber-500/40', bg: 'bg-amber-500/5',  label: 'text-amber-300', ring: '' },
    merge_input:  { border: 'border-purple-500/40', bg: 'bg-purple-500/5',label: 'text-purple-300',ring: '' },
    merge_output: { border: 'border-purple-500/40', bg: 'bg-purple-500/5',label: 'text-purple-300',ring: '' },
    external:     { border: 'border-white/10', bg: 'bg-white/3',          label: 'text-white/40',   ring: '' },
    enterprise:   { border: 'border-sky-500/40', bg: 'bg-sky-500/5',      label: 'text-sky-300',    ring: '' },
    current:      { border: 'border-brand-500/60', bg: 'bg-brand-500/10', label: 'text-brand-300', ring: 'ring-2 ring-brand-500/40' },
  }
  return map[node.type] ?? map['external']
}

function edgeStyle(edge) {
  return {
    split:    { color: 'text-amber-400', line: 'border-amber-400/40',  icon: '✂️', label: edge.label },
    merge:    { color: 'text-purple-400', line: 'border-purple-400/40', icon: '🔀', label: edge.label },
    transfer: { color: 'text-sky-400',   line: 'border-sky-400/40',    icon: '🚚', label: edge.label },
  }[edge.type] ?? { color: 'text-white/30', line: 'border-white/10', icon: '→', label: edge.label }
}

function batchTypeLabel(t) {
  return ({ original: 'Gốc', split: 'Đã tách', merged: 'Đã gộp', received: 'Đã nhận' })[t] ?? t
}

function statusLabel(s) {
  return ({ active: 'Hoạt động', recalled: 'Thu hồi', split: 'Đã tách', consumed: 'Đã dùng', received: 'Đã nhận', inactive: 'Không hoạt động' })[s] ?? s
}

function statusColor(s) {
  return ({ active: 'text-green-400', recalled: 'text-red-400', split: 'text-amber-400', consumed: 'text-white/30', received: 'text-sky-400' })[s] ?? 'text-white/40'
}

// Tìm edges liên quan đến 1 node
function edgesFor(nodeId) {
  return props.edges.filter(e => e.from === nodeId || e.to === nodeId)
}
</script>

<template>
  <Head :title="`Phả hệ lô ${batch.code}`" />

  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6 flex flex-wrap items-start justify-between gap-4">
      <div>
        <div class="text-xs text-white/40 mb-1">
          <Link :href="route('batches.index')" class="text-brand-300 hover:underline">Lô hàng</Link>
          <span class="mx-2">/</span>
          <span class="text-white/60">{{ batch.code }}</span>
          <span class="mx-2">/</span>
          <span class="text-white/40">Phả hệ</span>
        </div>
        <h1 class="text-2xl font-bold text-white/90">
          {{ batch.category?.icon }} Sơ đồ phả hệ lô hàng
        </h1>
        <p class="text-white/40 text-sm mt-1">
          Truy vết toàn bộ nguồn gốc: tách lô, gộp lô, chuyển giao giữa doanh nghiệp.
        </p>
      </div>

      <!-- Completeness badge -->
      <div class="rounded-xl border border-glass bg-white/5 px-4 py-3 text-center min-w-32">
        <div class="text-2xl font-extrabold"
          :class="batch.completeness?.score === 100 ? 'text-green-400' : 'text-brand-400'">
          {{ batch.completeness?.score ?? 0 }}%
        </div>
        <div class="text-xs text-white/40 mt-0.5">Hoàn thiện CTE</div>
      </div>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap gap-3 text-xs">
      <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-brand-500/10 border border-brand-500/30 text-brand-300">
        <div class="w-2 h-2 rounded-full bg-brand-400"></div> Lô hiện tại
      </div>
      <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-white/50">
        <div class="w-2 h-2 rounded-full bg-white/30"></div> Lô tổ tiên
      </div>
      <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500/5 border border-amber-500/30 text-amber-300">
        <div class="w-2 h-2 rounded-full bg-amber-400"></div> ✂️ Tách lô
      </div>
      <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-500/5 border border-purple-500/30 text-purple-300">
        <div class="w-2 h-2 rounded-full bg-purple-400"></div> 🔀 Gộp lô
      </div>
      <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-sky-500/5 border border-sky-500/30 text-sky-300">
        <div class="w-2 h-2 rounded-full bg-sky-400"></div> 🚚 Chuyển giao
      </div>
    </div>

    <!-- Flow diagram -->
    <div class="rounded-2xl border border-glass bg-black/20 overflow-x-auto p-6">

      <!-- Empty state -->
      <div v-if="nodes.length === 0" class="flex flex-col items-center justify-center py-16 text-white/30">
        <div class="text-4xl mb-3">🌱</div>
        <div class="text-sm">Lô này chưa có liên kết phả hệ nào.</div>
        <div class="text-xs mt-1">Thực hiện tách lô, gộp lô hoặc chuyển giao để tạo phả hệ.</div>
      </div>

      <!-- Columns layout -->
      <div v-else class="flex gap-0 items-start min-w-max">
        <template v-for="(col, colIdx) in columns" :key="colIdx">

          <!-- Column of nodes -->
          <div class="flex flex-col gap-4 items-center" style="min-width: 220px">
            <div
              v-for="node in col"
              :key="node.id"
              @click="selectNode(node)"
              class="w-52 rounded-xl border p-3.5 cursor-pointer transition-all hover:scale-105"
              :class="[nodeStyle(node).border, nodeStyle(node).bg, nodeStyle(node).ring,
                selectedNode?.id === node.id ? 'shadow-lg shadow-brand-500/20' : '']"
            >
              <!-- Node type badge -->
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] px-1.5 py-0.5 rounded border" :class="nodeStyle(node).label + ' border-current opacity-70'">
                  {{ node.type === 'enterprise' ? '🏢 DN' : batchTypeLabel(node.batch_type) }}
                </span>
                <span v-if="node.event_count" class="text-[10px] text-green-400/70">
                  {{ node.event_count }} events
                </span>
              </div>

              <!-- Node content -->
              <div v-if="node.type === 'enterprise'">
                <div class="text-sm font-bold text-white/80 truncate">{{ node.label }}</div>
                <div v-if="node.code" class="text-[10px] text-white/30 font-mono mt-0.5">{{ node.code }}</div>
              </div>
              <div v-else>
                <div class="font-mono text-sm font-bold truncate" :class="nodeStyle(node).label">
                  {{ node.code }}
                  <span v-if="node.batch_id === batch.id" class="ml-1 text-[10px] text-brand-400">← lô này</span>
                </div>
                <div class="text-xs text-white/50 mt-0.5 truncate">{{ node.product_name }}</div>
                <div class="flex items-center justify-between mt-2">
                  <span class="text-[10px]" :class="statusColor(node.status)">
                    {{ statusLabel(node.status) }}
                  </span>
                  <span v-if="node.quantity" class="text-[10px] text-white/30">
                    {{ node.quantity }} {{ node.unit }}
                  </span>
                </div>
              </div>

              <!-- Highlight nếu đang chọn -->
              <div v-if="selectedNode?.id === node.id" class="mt-2 pt-2 border-t border-white/8 text-[10px] text-white/40">
                Click để xem chi tiết →
              </div>
            </div>
          </div>

          <!-- Arrow connectors giữa 2 cột -->
          <div v-if="colIdx < columns.length - 1"
            class="flex flex-col justify-center items-center gap-2 px-2 self-stretch min-w-24">
            <template v-for="edge in edges.filter(e =>
              col.some(n => n.id === e.from) &&
              (columns[colIdx + 1] ?? []).some(n => n.id === e.to)
            )" :key="edge.from + edge.to">
              <div class="flex flex-col items-center gap-1">
                <div class="text-base" :title="edge.label">
                  {{ edgeStyle(edge).icon }}
                </div>
                <div class="w-12 h-px border-t border-dashed" :class="edgeStyle(edge).line"></div>
                <div class="text-[9px] text-center max-w-20 leading-tight" :class="edgeStyle(edge).color">
                  {{ edge.label }}
                </div>
              </div>
            </template>
            <!-- Fallback arrow nếu không có edge nào match cột -->
            <div class="text-white/10 text-xl">→</div>
          </div>

        </template>
      </div>
    </div>

    <!-- Panel chi tiết node được chọn -->
    <Transition name="slide-up">
      <div v-if="selectedNode && selectedNode.type !== 'enterprise'"
        class="rounded-2xl border border-glass bg-black/30 p-5">

        <div class="flex items-start justify-between gap-4 mb-4">
          <div>
            <div class="text-xs text-white/30 uppercase tracking-widest mb-1">Chi tiết lô</div>
            <h3 class="text-lg font-bold font-mono text-white/90">{{ selectedNode.code }}</h3>
            <div class="text-sm text-white/50 mt-0.5">{{ selectedNode.product_name }}</div>
          </div>
          <button @click="selectedNode = null" class="text-white/30 hover:text-white/60 transition text-xl">✕</button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm mb-4">
          <div class="rounded-xl bg-white/5 border border-white/8 px-3 py-2.5">
            <div class="text-[10px] text-white/30 uppercase mb-1">Loại lô</div>
            <div class="font-medium text-white/80">{{ batchTypeLabel(selectedNode.batch_type) }}</div>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/8 px-3 py-2.5">
            <div class="text-[10px] text-white/30 uppercase mb-1">Trạng thái</div>
            <div class="font-medium" :class="statusColor(selectedNode.status)">{{ statusLabel(selectedNode.status) }}</div>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/8 px-3 py-2.5">
            <div class="text-[10px] text-white/30 uppercase mb-1">Số lượng</div>
            <div class="font-medium text-white/80">{{ selectedNode.quantity }} {{ selectedNode.unit }}</div>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/8 px-3 py-2.5">
            <div class="text-[10px] text-white/30 uppercase mb-1">Sự kiện đã publish</div>
            <div class="font-medium" :class="selectedNode.event_count > 0 ? 'text-green-400' : 'text-white/30'">
              {{ selectedNode.event_count }}
            </div>
          </div>
        </div>

        <!-- Edges liên quan -->
        <div class="space-y-1.5">
          <div class="text-xs text-white/30 uppercase tracking-widest mb-2">Kết nối</div>
          <div v-for="e in edgesFor(selectedNode.id)" :key="e.from + e.to"
            class="flex items-center gap-3 text-xs px-3 py-2 rounded-xl bg-white/3 border border-white/5">
            <span>{{ edgeStyle(e).icon }}</span>
            <span :class="edgeStyle(e).color">{{ e.label }}</span>
            <span class="text-white/20">
              {{ e.from === selectedNode.id ? '→ ' + e.to : e.from + ' →' }}
            </span>
          </div>
        </div>

        <!-- Link đến trang events nếu là lô của tenant mình -->
        <div v-if="selectedNode.batch_id" class="mt-4 flex gap-2">
          <Link
            :href="route('events.index', { batch_id: selectedNode.batch_id })"
            class="text-xs px-3 py-2 rounded-xl border border-brand-500/30 bg-brand-500/10 hover:bg-brand-500/20 text-brand-300 transition">
            📋 Xem sự kiện lô này
          </Link>
          <Link
            :href="route('batches.lineage', selectedNode.batch_id)"
            class="text-xs px-3 py-2 rounded-xl border border-glass hover:bg-white/5 text-white/40 transition">
            🌳 Xem phả hệ lô này
          </Link>
        </div>
      </div>
    </Transition>

    <!-- Batch hiện tại summary -->
    <div class="rounded-2xl border border-glass bg-black/20 p-5">
      <div class="text-xs text-white/30 uppercase tracking-widest mb-3">Thông tin lô hiện tại</div>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
        <div>
          <div class="text-[10px] text-white/30 uppercase mb-1">Mã lô</div>
          <div class="font-mono font-bold text-white/80">{{ batch.code }}</div>
        </div>
        <div>
          <div class="text-[10px] text-white/30 uppercase mb-1">Loại</div>
          <div class="text-white/70">{{ batchTypeLabel(batch.batch_type) }}</div>
        </div>
        <div>
          <div class="text-[10px] text-white/30 uppercase mb-1">Trạng thái</div>
          <div :class="statusColor(batch.status)">{{ statusLabel(batch.status) }}</div>
        </div>
        <div>
          <div class="text-[10px] text-white/30 uppercase mb-1">Doanh nghiệp</div>
          <div class="text-white/60 truncate">{{ batch.enterprise }}</div>
        </div>
      </div>

      <!-- CTE completeness -->
      <div v-if="batch.completeness?.required_total > 0" class="mt-4 pt-4 border-t border-white/5">
        <div class="text-xs text-white/30 mb-2">
          Tiến độ CTE: {{ batch.completeness.required_done }}/{{ batch.completeness.required_total }} bắt buộc
        </div>
        <div class="h-2 bg-white/10 rounded-full overflow-hidden">
          <div class="h-full rounded-full transition-all"
            :class="batch.completeness.score === 100 ? 'bg-green-500' : 'bg-brand-500'"
            :style="{ width: batch.completeness.score + '%' }"></div>
        </div>
        <div v-if="batch.completeness.missing?.length" class="mt-2 flex flex-wrap gap-1.5">
          <span v-for="m in batch.completeness.missing" :key="m.code"
            class="text-[10px] px-2 py-0.5 rounded border border-red-500/20 bg-red-500/5 text-red-400">
            Thiếu: {{ m.name_vi }}
          </span>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.2s ease; }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateY(8px); }
</style>