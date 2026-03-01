<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  batches: Array,  // [{id, code, product_id, product_name, status, product:{category_id}}]
  events:  Object, // paginator
  filters: Object,
})

const flash = usePage().props.flash ?? {}

// ── Filter ────────────────────────────────────────────────
const batchId = ref(props.filters?.batch_id ?? '')
watch(batchId, (val) => {
  router.get(route('events.index'), { batch_id: val || undefined }, { preserveState: true, replace: true })
})

const selectedBatch = computed(() => props.batches.find(b => b.id == batchId.value))

// ── CTE Templates ─────────────────────────────────────────
const cteTemplates  = ref([])
const loadingCte    = ref(false)

async function loadTemplates(catId, bId) {
  if (!catId) { cteTemplates.value = []; return }
  loadingCte.value = true
  try {
    const { data } = await axios.get('/api/cte-templates', {
      params: { category_id: catId, batch_id: bId || undefined }
    })
    cteTemplates.value = data.templates
  } finally {
    loadingCte.value = false
  }
}

watch(selectedBatch, (b) => {
  if (b) loadTemplates(b.product?.category_id, b.id)
}, { immediate: true })

// ── Add Event Form ─────────────────────────────────────────
const showAddForm  = ref(false)
const selectedCte  = ref(null)  // CteTemplate object
const kdeValues    = ref({})    // { fieldKey: value }

const form = useForm({
  batch_id:      '',
  cte_code:      '',
  event_time:    new Date().toISOString().slice(0, 16),
  kde_data:      {},
  who_name:      '',
  where_address: '',
  where_lat:     null,
  where_lng:     null,
  why_reason:    '',
  note:          '',
})

function selectCte(tpl) {
  selectedCte.value = tpl
  showAddForm.value  = true
  form.batch_id      = batchId.value
  form.cte_code      = tpl.code
  kdeValues.value    = {}
  // Pre-fill where_gps nếu đã có GPS
  if (currentGps.value) {
    kdeValues.value['where_lat'] = currentGps.value.lat
    kdeValues.value['where_lng'] = currentGps.value.lng
  }
}

function buildKdeData() {
  // Gom tất cả field values thành object kde_data
  const kde = {}
  if (selectedCte.value) {
    for (const field of selectedCte.value.kde_schema) {
      kde[field.key] = kdeValues.value[field.key] ?? null
    }
  }
  return kde
}

function extractIndexFields() {
  // Lấy các field index nhanh từ kdeValues
  const whoField   = selectedCte.value?.kde_schema?.find(f => f.w === 'WHO' && f.required)
  const whereField = selectedCte.value?.kde_schema?.find(f => f.key === 'where_address')
  const whyField   = selectedCte.value?.kde_schema?.find(f => f.w === 'WHY' && f.key !== 'why_note')

  return {
    who_name:      kdeValues.value[whoField?.key] || '',
    where_address: kdeValues.value['where_address'] || kdeValues.value['where_from'] || '',
    where_lat:     kdeValues.value['where_lat'] || null,
    where_lng:     kdeValues.value['where_lng'] || null,
    why_reason:    kdeValues.value[whyField?.key] || '',
  }
}

function submitForm() {
  const kde    = buildKdeData()
  const fields = extractIndexFields()

  form.kde_data      = kde
  form.who_name      = fields.who_name
  form.where_address = fields.where_address
  form.where_lat     = fields.where_lat
  form.where_lng     = fields.where_lng
  form.why_reason    = fields.why_reason

  form.post(route('events.store'), {
    onSuccess: () => {
      showAddForm.value = false
      selectedCte.value = null
      kdeValues.value   = {}
      form.reset()
    },
  })
}

// ── GPS ───────────────────────────────────────────────────
const currentGps  = ref(null)
const gpsLoading  = ref(false)
const gpsError    = ref('')

function getGps() {
  if (!navigator.geolocation) {
    gpsError.value = 'Trình duyệt không hỗ trợ GPS.'; return
  }
  gpsLoading.value = true
  gpsError.value   = ''
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      currentGps.value           = { lat: pos.coords.latitude, lng: pos.coords.longitude }
      kdeValues.value['where_lat'] = pos.coords.latitude
      kdeValues.value['where_lng'] = pos.coords.longitude
      gpsLoading.value             = false
    },
    (err) => {
      gpsError.value   = 'Không lấy được GPS: ' + err.message
      gpsLoading.value = false
    },
    { enableHighAccuracy: true, timeout: 10000 }
  )
}

// ── Attachment upload ─────────────────────────────────────
const uploadingEvent  = ref(null)
const uploadProgress  = ref(0)

async function uploadFile(event, file) {
  if (!file) return
  uploadingEvent.value = event.id

  const fd = new FormData()
  fd.append('file', file)

  try {
    const res = await axios.post(route('events.attachments.store', event.id), fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (e) => {
        uploadProgress.value = Math.round((e.loaded * 100) / e.total)
      },
    })
    // Reload page để thấy attachment mới
    router.reload({ only: ['events'] })
  } catch (e) {
    alert('Upload thất bại: ' + (e.response?.data?.error || e.message))
  } finally {
    uploadingEvent.value = null
    uploadProgress.value  = 0
  }
}

// ── Edit + Delete + Publish ───────────────────────────────
const editingEvent = ref(null)
const editKdeValues = ref({})
const editForm = useForm({
  cte_code: '', event_time: '', kde_data: {},
  who_name: '', where_address: '', where_lat: null, where_lng: null, why_reason: '', note: ''
})

function openEdit(ev) {
  editingEvent.value = ev
  editKdeValues.value = { ...ev.kde_data }
  editForm.cte_code    = ev.cte_code
  editForm.event_time  = ev.event_time?.slice(0, 16) ?? ''
  editForm.note        = ev.note ?? ''
}

function submitEdit() {
  const kde = {}
  if (editingEvent.value?.cte_template?.kde_schema) {
    for (const f of editingEvent.value.cte_template.kde_schema) {
      kde[f.key] = editKdeValues.value[f.key] ?? null
    }
  } else {
    Object.assign(kde, editKdeValues.value)
  }
  editForm.kde_data = kde

  editForm.put(route('events.update', editingEvent.value.id), {
    onSuccess: () => { editingEvent.value = null }
  })
}

function deleteEvent(ev) {
  if (ev.status === 'published') return
  if (!confirm(`Xóa sự kiện "${getCteName(ev)}"?`)) return
  router.delete(route('events.destroy', ev.id))
}

function publishEvent(ev) {
  if (ev.status === 'published') return
  if (!confirm('Publish lên IPFS sẽ KHÓA VĨNH VIỄN sự kiện này. Tiếp tục?')) return
  router.post(route('events.publish', ev.id))
}

// ── Helpers ───────────────────────────────────────────────
const wColors = { WHO: 'blue', WHAT: 'orange', WHERE: 'green', WHEN: 'purple', WHY: 'rose' }
const wLabels = { WHO: '👤 WHO', WHAT: '📦 WHAT', WHERE: '📍 WHERE', WHEN: '🕐 WHEN', WHY: '📋 WHY' }

function getCteName(ev) {
  const tpl = cteTemplates.value.find(t => t.code === ev.cte_code)
  return tpl?.name_vi || ev.event_type || 'Sự kiện'
}

function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('vi-VN')
}

function shortCid(cid) {
  if (!cid) return ''
  return cid.length > 16 ? cid.slice(0, 8) + '...' + cid.slice(-6) : cid
}

const requiredDone = computed(() => cteTemplates.value.filter(t => t.is_required && t.is_done).length)
const requiredTotal = computed(() => cteTemplates.value.filter(t => t.is_required).length)
const completenessScore = computed(() => requiredTotal.value
  ? Math.round((requiredDone.value / requiredTotal.value) * 100) : 0)
</script>

<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/30 p-6">
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
          <div class="text-xs text-white/40 uppercase tracking-wider">TCVN 12850:2019 — 5W</div>
          <h1 class="text-2xl font-extrabold text-white/90 mt-1">Sự kiện truy xuất</h1>
          <p class="text-white/50 text-sm mt-1">Draft → Kiểm tra → Publish IPFS (khóa vĩnh viễn)</p>
        </div>
        <div v-if="flash.success" class="px-4 py-2 bg-green-500/15 border border-green-500/30 text-green-400 text-sm rounded-xl">
          ✓ {{ flash.success }}
        </div>
      </div>
    </div>

    <!-- Chọn lô + CTE progress -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

      <!-- Chọn lô -->
      <div class="bg-white/5 border border-glass rounded-2xl p-4">
        <div class="text-xs text-white/40 uppercase tracking-wider mb-2">Lô đang xem</div>
        <select v-model="batchId"
          class="w-full bg-black/20 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none focus:border-brand-500">
          <option value="">-- Tất cả lô --</option>
          <option v-for="b in batches" :key="b.id" :value="b.id">
            {{ b.code }} — {{ b.product?.name || b.product_name }}
            <template v-if="b.status === 'recalled'"> ⚠️ THU HỒI</template>
          </option>
        </select>

        <!-- Recall banner -->
        <div v-if="selectedBatch?.status === 'recalled'"
          class="mt-3 px-3 py-2 bg-red-500/15 border border-red-500/40 rounded-xl">
          <div class="text-red-400 text-xs font-bold">⚠️ LÔ NÀY ĐANG BỊ THU HỒI</div>
        </div>
      </div>

      <!-- Completeness -->
      <div v-if="selectedBatch" class="lg:col-span-2 bg-white/5 border border-glass rounded-2xl p-4">
        <div class="flex items-center justify-between mb-2">
          <div class="text-xs text-white/40 uppercase tracking-wider">Tiến độ chuỗi TXNG (CTE bắt buộc)</div>
          <div class="text-sm font-bold" :class="completenessScore === 100 ? 'text-green-400' : 'text-orange-400'">
            {{ requiredDone }}/{{ requiredTotal }} — {{ completenessScore }}%
          </div>
        </div>

        <!-- Progress bar -->
        <div class="h-2 bg-white/10 rounded-full mb-3">
          <div class="h-2 rounded-full transition-all"
            :class="completenessScore === 100 ? 'bg-green-500' : 'bg-orange-500'"
            :style="{ width: completenessScore + '%' }" />
        </div>

        <!-- CTE chips -->
        <div v-if="loadingCte" class="text-white/40 text-xs">Đang tải...</div>
        <div v-else class="flex flex-wrap gap-2">
          <button v-for="tpl in cteTemplates" :key="tpl.code"
            @click="selectCte(tpl)"
            :class="[
              'px-3 py-1 rounded-lg text-xs font-medium transition border',
              tpl.is_done
                ? 'bg-green-500/15 border-green-500/40 text-green-400 cursor-default'
                : tpl.is_required
                  ? 'bg-orange-500/10 border-orange-500/40 text-orange-300 hover:bg-orange-500/20 cursor-pointer'
                  : 'bg-white/5 border-white/10 text-white/50 hover:bg-white/10 cursor-pointer'
            ]">
            <span v-if="tpl.is_done">✓</span>
            <span v-else-if="tpl.is_required">○</span>
            <span v-else>·</span>
            {{ tpl.step_order }}. {{ tpl.name_vi }}
            <span v-if="tpl.is_required && !tpl.is_done" class="text-red-400">*</span>
          </button>

          <!-- Thêm tùy chỉnh -->
          <button @click="selectCte({ code: 'custom', name_vi: 'Sự kiện tùy chỉnh', kde_schema: [
            { key: 'who_performer', label: 'Người thực hiện', w: 'WHO', type: 'text', required: true },
            { key: 'what_activity', label: 'Nội dung hoạt động', w: 'WHAT', type: 'textarea', required: true },
            { key: 'where_address', label: 'Địa điểm', w: 'WHERE', type: 'text', required: false },
            { key: 'why_note', label: 'Ghi chú / Lý do', w: 'WHY', type: 'textarea', required: false },
          ], is_required: false, is_done: false })"
            class="px-3 py-1 rounded-lg text-xs border border-dashed border-white/20 text-white/40 hover:text-white/70 hover:border-white/40 transition">
            + Tùy chỉnh
          </button>
        </div>
      </div>
    </div>

    <!-- Add Event Form (5W Dynamic) -->
    <div v-if="showAddForm && selectedCte" class="bg-white/5 border border-glass rounded-2xl p-5">
      <div class="flex items-center justify-between mb-4">
        <div>
          <div class="text-xs text-white/40 uppercase tracking-wider">Ghi sự kiện mới</div>
          <div class="text-lg font-bold text-white/90">{{ selectedCte.name_vi }}</div>
        </div>
        <button @click="showAddForm = false" class="text-white/40 hover:text-white/80 text-xl leading-none">✕</button>
      </div>

      <!-- WHEN field (luôn có) -->
      <div class="mb-4 p-3 bg-purple-500/10 border border-purple-500/30 rounded-xl">
        <div class="text-xs text-purple-300 font-semibold mb-1">🕐 WHEN — Thời gian sự kiện</div>
        <input v-model="form.event_time" type="datetime-local"
          class="w-full bg-black/20 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none focus:border-purple-400" />
      </div>

      <!-- 5W Dynamic Fields — group by W -->
      <template v-for="w in ['WHO', 'WHAT', 'WHERE', 'WHY']" :key="w">
        <div v-if="selectedCte.kde_schema?.filter(f => f.w === w).length"
          class="mb-4 p-3 rounded-xl border"
          :class="{
            'bg-blue-500/10 border-blue-500/30': w === 'WHO',
            'bg-orange-500/10 border-orange-500/30': w === 'WHAT',
            'bg-green-500/10 border-green-500/30': w === 'WHERE',
            'bg-rose-500/10 border-rose-500/30': w === 'WHY',
          }">
          <div class="text-xs font-semibold mb-2"
            :class="{
              'text-blue-300': w === 'WHO',
              'text-orange-300': w === 'WHAT',
              'text-green-300': w === 'WHERE',
              'text-rose-300': w === 'WHY',
            }">
            {{ { WHO: '👤 WHO — Ai thực hiện', WHAT: '📦 WHAT — Cái gì / Số lượng', WHERE: '📍 WHERE — Ở đâu', WHY: '📋 WHY — Tại sao / Tiêu chuẩn' }[w] }}
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <template v-for="field in selectedCte.kde_schema.filter(f => f.w === w)" :key="field.key">

              <!-- GPS field đặc biệt -->
              <div v-if="field.type === 'gps'" class="col-span-2">
                <label class="text-xs text-white/50 mb-1 block">
                  {{ field.label }}
                  <span v-if="field.required" class="text-red-400">*</span>
                </label>
                <div class="flex gap-2 items-center">
                  <input v-model="kdeValues['where_lat']" type="number" step="0.0000001"
                    placeholder="Vĩ độ (lat)"
                    class="flex-1 bg-black/20 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 font-mono outline-none focus:border-green-400" />
                  <input v-model="kdeValues['where_lng']" type="number" step="0.0000001"
                    placeholder="Kinh độ (lng)"
                    class="flex-1 bg-black/20 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 font-mono outline-none focus:border-green-400" />
                  <button @click="getGps" :disabled="gpsLoading" type="button"
                    class="px-3 py-2 bg-green-500/20 border border-green-500/40 text-green-400 rounded-xl text-xs hover:bg-green-500/30 transition disabled:opacity-50 whitespace-nowrap">
                    {{ gpsLoading ? '...' : '📍 GPS' }}
                  </button>
                </div>
                <div v-if="gpsError" class="text-red-400 text-xs mt-1">{{ gpsError }}</div>
                <div v-if="currentGps" class="text-green-400 text-xs mt-1">
                  ✓ {{ currentGps.lat.toFixed(6) }}, {{ currentGps.lng.toFixed(6) }}
                </div>
              </div>

              <!-- Textarea -->
              <div v-else-if="field.type === 'textarea'" :class="field.w === 'WHY' ? 'col-span-2' : ''">
                <label class="text-xs text-white/50 mb-1 block">
                  {{ field.label }}
                  <span v-if="field.required" class="text-red-400">*</span>
                </label>
                <textarea v-model="kdeValues[field.key]" rows="2"
                  :placeholder="field.placeholder || ''"
                  class="w-full bg-black/20 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none resize-none"
                  :class="{ 'border-red-500/50': field.required && !kdeValues[field.key] }" />
              </div>

              <!-- Select -->
              <div v-else-if="field.type === 'select'">
                <label class="text-xs text-white/50 mb-1 block">
                  {{ field.label }}
                  <span v-if="field.required" class="text-red-400">*</span>
                </label>
                <select v-model="kdeValues[field.key]"
                  class="w-full bg-black/20 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none">
                  <option value="">-- Chọn --</option>
                  <option v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>

              <!-- Date -->
              <div v-else-if="field.type === 'date'">
                <label class="text-xs text-white/50 mb-1 block">
                  {{ field.label }}
                  <span v-if="field.required" class="text-red-400">*</span>
                </label>
                <input v-model="kdeValues[field.key]" type="date"
                  class="w-full bg-black/20 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none" />
              </div>

              <!-- Number -->
              <div v-else-if="field.type === 'number'">
                <label class="text-xs text-white/50 mb-1 block">
                  {{ field.label }}
                  <span v-if="field.required" class="text-red-400">*</span>
                </label>
                <input v-model="kdeValues[field.key]" type="number" step="any"
                  :placeholder="field.placeholder || ''"
                  class="w-full bg-black/20 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none" />
              </div>

              <!-- Text (default) -->
              <div v-else>
                <label class="text-xs text-white/50 mb-1 block">
                  {{ field.label }}
                  <span v-if="field.required" class="text-red-400">*</span>
                </label>
                <input v-model="kdeValues[field.key]" type="text"
                  :placeholder="field.placeholder || ''"
                  class="w-full bg-black/20 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none" />
              </div>

            </template>
          </div>
        </div>
      </template>

      <!-- Ghi chú chung -->
      <div class="mb-4">
        <label class="text-xs text-white/50 mb-1 block">Ghi chú thêm</label>
        <textarea v-model="form.note" rows="2"
          class="w-full bg-black/20 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none resize-none" />
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button @click="submitForm" :disabled="form.processing"
          class="px-5 py-2 bg-brand-500 hover:bg-brand-400 text-black font-bold rounded-xl text-sm transition disabled:opacity-50">
          Lưu sự kiện (Draft)
        </button>
        <button @click="showAddForm = false"
          class="px-4 py-2 border border-glass hover:bg-white/5 text-white/60 rounded-xl text-sm transition">
          Hủy
        </button>
      </div>
    </div>

    <!-- Events Table -->
    <div class="bg-white/5 border border-glass rounded-2xl overflow-hidden">
      <div class="px-5 py-4 border-b border-glass flex items-center justify-between">
        <div class="font-bold text-white/80">Danh sách sự kiện</div>
        <div class="text-xs text-white/40">
          Trang {{ events.current_page }}/{{ events.last_page }}
        </div>
      </div>

      <div v-if="!events.data.length" class="py-12 text-center text-white/30">
        Chưa có sự kiện nào. Chọn lô và thêm sự kiện đầu tiên!
      </div>

      <div v-for="ev in events.data" :key="ev.id"
        class="border-b border-glass/50 hover:bg-white/5 transition p-5">

        <div class="flex items-start justify-between gap-4 flex-wrap">
          <!-- Left: CTE name + batch + status -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1 flex-wrap">
              <!-- CTE badge -->
              <span class="px-2 py-0.5 bg-brand-500/20 border border-brand-500/40 text-brand-300 text-xs rounded-md font-semibold">
                {{ getCteName(ev) }}
              </span>
              <!-- Status badge -->
              <span :class="ev.status === 'published'
                ? 'bg-green-500/15 border-green-500/30 text-green-400'
                : 'bg-orange-500/15 border-orange-500/30 text-orange-400'"
                class="px-2 py-0.5 text-xs rounded-md border">
                {{ ev.status === 'published' ? '🔒 Published' : '📝 Draft' }}
              </span>
              <!-- Batch code -->
              <span class="text-xs text-white/40 font-mono">
                Lô: {{ ev.batch?.code }}
              </span>
            </div>

            <!-- 5W summary -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2 text-xs">
              <div v-if="ev.who_name" class="flex items-start gap-1">
                <span class="text-blue-400 shrink-0">👤</span>
                <span class="text-white/70 truncate">{{ ev.who_name }}</span>
              </div>
              <div class="flex items-start gap-1">
                <span class="text-purple-400 shrink-0">🕐</span>
                <span class="text-white/70">{{ fmtDate(ev.event_time) }}</span>
              </div>
              <div v-if="ev.where_address" class="flex items-start gap-1">
                <span class="text-green-400 shrink-0">📍</span>
                <span class="text-white/70 truncate">{{ ev.where_address }}</span>
              </div>
              <div v-if="ev.why_reason" class="flex items-start gap-1">
                <span class="text-rose-400 shrink-0">📋</span>
                <span class="text-white/70 truncate">{{ ev.why_reason }}</span>
              </div>
            </div>

            <!-- GPS indicator -->
            <div v-if="ev.where_lat" class="mt-1 text-xs text-green-400/70">
              📡 GPS: {{ ev.where_lat }}, {{ ev.where_lng }}
            </div>

            <!-- IPFS info -->
            <div v-if="ev.ipfs_cid" class="mt-2 flex items-center gap-2 flex-wrap">
              <span class="text-xs text-white/30">IPFS:</span>
              <a :href="ev.ipfs_url" target="_blank"
                class="font-mono text-xs text-brand-400 hover:text-brand-300 hover:underline">
                {{ shortCid(ev.ipfs_cid) }}
              </a>
              <a :href="`/verify/ipfs/${ev.ipfs_cid}?hash=${ev.content_hash}`" target="_blank"
                class="text-xs text-green-400/70 hover:text-green-400">
                [Xác minh]
              </a>
            </div>

            <!-- Attachments -->
            <div v-if="ev.attachments?.length" class="mt-2 flex gap-2 flex-wrap">
              <a v-for="att in ev.attachments" :key="att.cid"
                :href="att.url" target="_blank"
                class="flex items-center gap-1 px-2 py-0.5 bg-white/5 border border-glass rounded-lg text-xs text-white/60 hover:text-white/90 transition">
                <span>📎</span> {{ att.name }}
              </a>
            </div>
          </div>

          <!-- Right: Actions -->
          <div class="flex items-center gap-2 flex-wrap shrink-0">

            <!-- Upload đính kèm (chỉ draft) -->
            <label v-if="ev.status === 'draft'"
              class="cursor-pointer px-3 py-1.5 border border-glass hover:bg-white/5 text-white/50 rounded-lg text-xs transition">
              📎 Đính kèm
              <input type="file" class="hidden" accept="image/*,.pdf"
                @change="e => uploadFile(ev, e.target.files[0])" />
            </label>

            <!-- Sửa -->
            <button v-if="ev.status === 'draft'" @click="openEdit(ev)"
              class="px-3 py-1.5 border border-glass hover:bg-white/5 text-white/60 rounded-lg text-xs transition">
              Sửa
            </button>

            <!-- Publish -->
            <button v-if="ev.status === 'draft'" @click="publishEvent(ev)"
              class="px-3 py-1.5 bg-brand-500/20 border border-brand-500/40 text-brand-300 hover:bg-brand-500/30 rounded-lg text-xs transition font-semibold">
              ↑ Publish IPFS
            </button>

            <!-- Xóa -->
            <button v-if="ev.status === 'draft'" @click="deleteEvent(ev)"
              class="px-3 py-1.5 border border-red-500/30 hover:bg-red-500/10 text-red-400 rounded-lg text-xs transition">
              Xóa
            </button>
          </div>
        </div>

        <!-- Upload progress -->
        <div v-if="uploadingEvent === ev.id" class="mt-2">
          <div class="h-1 bg-white/10 rounded-full">
            <div class="h-1 bg-brand-500 rounded-full transition-all" :style="{ width: uploadProgress + '%' }" />
          </div>
          <div class="text-xs text-white/40 mt-1">Đang upload... {{ uploadProgress }}%</div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="events.last_page > 1" class="flex justify-center gap-2">
      <button v-for="page in events.last_page" :key="page"
        @click="router.get(route('events.index'), { page, batch_id: batchId || undefined })"
        :class="page === events.current_page ? 'bg-brand-500 text-black' : 'bg-white/5 text-white/60 hover:bg-white/10'"
        class="w-8 h-8 rounded-lg text-xs font-bold transition">
        {{ page }}
      </button>
    </div>

    <!-- Edit Modal -->
    <div v-if="editingEvent"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
      @click.self="editingEvent = null">
      <div class="bg-cosmic-900 border border-glass rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-5 border-b border-glass flex items-center justify-between">
          <div class="font-bold text-white/90">Sửa: {{ getCteName(editingEvent) }}</div>
          <button @click="editingEvent = null" class="text-white/40 hover:text-white/80 text-xl">✕</button>
        </div>

        <div class="p-5 space-y-4">
          <!-- WHEN -->
          <div>
            <label class="text-xs text-white/50 mb-1 block">🕐 Thời gian</label>
            <input v-model="editForm.event_time" type="datetime-local"
              class="w-full bg-white/5 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none" />
          </div>

          <!-- KDE fields (flat edit) -->
          <div v-for="(val, key) in editKdeValues" :key="key">
            <label class="text-xs text-white/40 mb-1 block capitalize">{{ key.replace(/_/g,' ') }}</label>
            <textarea v-model="editKdeValues[key]" rows="1"
              class="w-full bg-white/5 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none resize-y" />
          </div>

          <!-- Note -->
          <div>
            <label class="text-xs text-white/50 mb-1 block">Ghi chú</label>
            <textarea v-model="editForm.note" rows="2"
              class="w-full bg-white/5 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none resize-none" />
          </div>
        </div>

        <div class="px-5 py-4 border-t border-glass flex gap-2">
          <button @click="submitEdit" :disabled="editForm.processing"
            class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-black font-bold rounded-xl text-sm disabled:opacity-50">
            Lưu thay đổi
          </button>
          <button @click="editingEvent = null"
            class="px-4 py-2 border border-glass hover:bg-white/5 text-white/60 rounded-xl text-sm">
            Hủy
          </button>
        </div>
      </div>
    </div>

  </div>
</template>