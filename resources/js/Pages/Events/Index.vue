<script setup>
import { ref, computed, watch } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  batches: Array,
  events:  Object,
  filters: Object,
})

// ── Batch selector ────────────────────────────────────────
const batchId       = ref(props.filters?.batch_id ? Number(props.filters.batch_id) : null)
const selectedBatch = computed(() => props.batches?.find(b => b.id === batchId.value) ?? null)

watch(batchId, (val) => {
  router.visit(route('events.index', val ? { batch_id: val } : {}), {
    preserveState: true, preserveScroll: true, replace: true,
  })
})

// ── WHY suggestions theo cert x CTE ──────────────────────
const CERT_WHY_MAP = {
  VietGAP:    { harvest: ['Thu hoạch theo tiêu chuẩn VietGAP'], packaging: ['Đóng gói theo VietGAP — đảm bảo vệ sinh ATTP'], '*': ['Thực hiện theo tiêu chuẩn VietGAP'] },
  GlobalGAP:  { harvest: ['Thu hoạch theo quy trình GlobalGAP'], packaging: ['Đóng gói theo GlobalGAP'], '*': ['Tuân thủ tiêu chuẩn GlobalGAP quốc tế'] },
  'ISO 22000':{ packaging: ['Đóng gói theo ISO 22000 — kiểm soát điểm tới hạn CCP'], processing: ['Chế biến theo ISO 22000'], '*': ['Thực hiện theo hệ thống quản lý ATTP ISO 22000'] },
  HACCP:      { processing: ['Chế biến theo HACCP — kiểm soát mối nguy'], packaging: ['Đóng gói theo giới hạn tới hạn HACCP'], '*': ['Áp dụng nguyên tắc HACCP'] },
  'ISO 9001': { '*': ['Thực hiện theo hệ thống quản lý chất lượng ISO 9001'] },
  Organic:    { harvest: ['Thu hoạch theo tiêu chuẩn hữu cơ — không hóa chất'], '*': ['Sản xuất theo tiêu chuẩn hữu cơ'] },
  OCOP:       { '*': ['Sản phẩm đạt chứng nhận OCOP'] },
  FDA:        { '*': ['Đáp ứng tiêu chuẩn FDA Hoa Kỳ'] },
  BRC:        { '*': ['Thực hiện theo tiêu chuẩn BRC Global Standard'] },
  SQF:        { '*': ['Tuân thủ tiêu chuẩn SQF — Safe Quality Food'] },
}

const GENERIC_TEMPLATES = [
  { code: 'transformation', name_vi: 'Chuyển đổi / Sản xuất', step_order: 1, is_required: false, is_done: false,
    kde_schema: [
      { key: 'who_performer', label: 'Người thực hiện',    w: 'WHO',   type: 'text',   required: true,  placeholder: '' },
      { key: 'what_output',   label: 'Kết quả đầu ra',     w: 'WHAT',  type: 'text',   required: false },
      { key: 'where_address', label: 'Địa điểm',           w: 'WHERE', type: 'text',   required: false },
      { key: 'where_lat',     label: 'Latitude',           w: 'WHERE', type: 'number', required: false },
      { key: 'where_lng',     label: 'Longitude',          w: 'WHERE', type: 'number', required: false },
      { key: 'why_reason',    label: 'Lý do',              w: 'WHY',   type: 'textarea', required: false },
    ]},
  { code: 'storage', name_vi: 'Lưu kho', step_order: 2, is_required: false, is_done: false,
    kde_schema: [
      { key: 'who_keeper',    label: 'Người quản lý kho',  w: 'WHO',   type: 'text',   required: true },
      { key: 'what_condition',label: 'Điều kiện bảo quản', w: 'WHAT',  type: 'text',   required: false, placeholder: 'Nhiệt độ, độ ẩm...' },
      { key: 'what_quantity', label: 'Số lượng',           w: 'WHAT',  type: 'number', required: false },
      { key: 'where_address', label: 'Tên/địa chỉ kho',   w: 'WHERE', type: 'text',   required: false },
      { key: 'where_lat',     label: 'Latitude',           w: 'WHERE', type: 'number', required: false },
      { key: 'where_lng',     label: 'Longitude',          w: 'WHERE', type: 'number', required: false },
      { key: 'why_reason',    label: 'Lý do',              w: 'WHY',   type: 'textarea', required: false },
    ]},
  { code: 'transport', name_vi: 'Vận chuyển', step_order: 4, is_required: false, is_done: false,
    kde_schema: [
      { key: 'who_driver',     label: 'Tài xế / Đơn vị vận chuyển', w: 'WHO',   type: 'text',   required: true },
      { key: 'what_vehicle',   label: 'Phương tiện / Biển số',       w: 'WHAT',  type: 'text',   required: false },
      { key: 'where_from',     label: 'Điểm xuất phát',              w: 'WHERE', type: 'text',   required: false },
      { key: 'where_to',       label: 'Điểm đến',                    w: 'WHERE', type: 'text',   required: false },
      { key: 'where_lat',      label: 'Latitude',                    w: 'WHERE', type: 'number', required: false },
      { key: 'where_lng',      label: 'Longitude',                   w: 'WHERE', type: 'number', required: false },
      { key: 'why_conditions', label: 'Điều kiện vận chuyển',        w: 'WHY',   type: 'text',   required: false, placeholder: 'Nhiệt độ, độ ẩm...' },
    ]},
]

// ── WHY suggestions ───────────────────────────────────────
const whySuggestions = computed(() => {
  const certs = selectedBatch.value?.certifications ?? []
  const cte   = selectedCte.value?.code ?? ''
  const out   = []
  for (const cert of certs) {
    const map = CERT_WHY_MAP[cert]
    if (!map) continue
    const items = (map[cte]?.length ? map[cte] : map['*']) ?? []
    for (const s of items) if (!out.includes(s)) out.push(s)
  }
  return out
})

// ── CTE Templates ─────────────────────────────────────────
const cteTemplates = ref([])
const loadingCte   = ref(false)

async function loadTemplates(catId, bId) {
  loadingCte.value = true
  try {
    if (catId) {
      const { data } = await axios.get('/api/cte-templates', { params: { category_id: catId, batch_id: bId || undefined } })
      cteTemplates.value = data.templates
    } else {
      cteTemplates.value = GENERIC_TEMPLATES
    }
  } finally { loadingCte.value = false }
}

watch(selectedBatch, (b) => {
  if (b) loadTemplates(b.category_id, b.id)
  else cteTemplates.value = []
}, { immediate: true })

const requiredDone  = computed(() => cteTemplates.value.filter(t => t.is_required && t.is_done).length)
const requiredTotal = computed(() => cteTemplates.value.filter(t => t.is_required).length)
const completeness  = computed(() => requiredTotal.value ? Math.round((requiredDone.value / requiredTotal.value) * 100) : 0)

// ── Add Event Form ────────────────────────────────────────
const showAddForm  = ref(false)
const selectedCte  = ref(null)
const kdeValues    = ref({})
const pendingFiles = ref([])

const form = useForm({
  batch_id: '', cte_code: '',
  event_time: new Date().toISOString().slice(0, 16),
  kde_data: {}, who_name: '', where_address: '',
  where_lat: null, where_lng: null, why_reason: '', note: '',
})

function selectCte(tpl) {
  selectedCte.value = tpl
  showAddForm.value  = true
  form.batch_id      = batchId.value
  form.cte_code      = tpl.code
  kdeValues.value    = {}
  if (currentGps.value) {
    kdeValues.value['where_lat'] = currentGps.value.lat
    kdeValues.value['where_lng'] = currentGps.value.lng
  }
}

function buildKdeData() {
  const kde = {}
  if (selectedCte.value?.kde_schema) {
    for (const f of selectedCte.value.kde_schema) kde[f.key] = kdeValues.value[f.key] ?? null
  }
  return kde
}

function extractIndexFields() {
  const whoField = selectedCte.value?.kde_schema?.find(f => f.w === 'WHO' && f.required)
  const whyField = selectedCte.value?.kde_schema?.find(f => f.w === 'WHY')
  return {
    who_name:      kdeValues.value[whoField?.key] || '',
    where_address: kdeValues.value['where_address'] || kdeValues.value['where_from'] || '',
    where_lat:     kdeValues.value['where_lat'] || null,
    where_lng:     kdeValues.value['where_lng'] || null,
    why_reason:    kdeValues.value[whyField?.key] || '',
  }
}

function onFileSelect(e) {
  pendingFiles.value.push(...Array.from(e.target.files ?? []))
  e.target.value = ''
}

async function uploadPendingFiles(eventId) {
  for (const file of pendingFiles.value) {
    const fd = new FormData()
    fd.append('file', file)
    try {
      await axios.post(route('events.attachments.store', eventId), fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    } catch (e) { console.error('Upload thất bại:', file.name, e) }
  }
  pendingFiles.value = []
}

function submitForm() {
  const fields = extractIndexFields()
  form.kde_data      = buildKdeData()
  form.who_name      = fields.who_name
  form.where_address = fields.where_address
  form.where_lat     = fields.where_lat
  form.where_lng     = fields.where_lng
  form.why_reason    = fields.why_reason
  form.post(route('events.store'), {
    onSuccess: async (pg) => {
      const newEv = pg.props.events?.data?.[0]
      if (newEv && pendingFiles.value.length) await uploadPendingFiles(newEv.id)
      showAddForm.value = false; selectedCte.value = null; kdeValues.value = {}; pendingFiles.value = []
      form.reset(); form.event_time = new Date().toISOString().slice(0, 16)
      router.reload({ only: ['events'] })
    },
  })
}

// ── GPS ───────────────────────────────────────────────────
const currentGps = ref(null)
const gpsLoading = ref(false)
const gpsError   = ref('')

function getGps() {
  if (!navigator.geolocation) { gpsError.value = 'Trình duyệt không hỗ trợ GPS.'; return }
  gpsLoading.value = true; gpsError.value = ''
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      currentGps.value = { lat: pos.coords.latitude, lng: pos.coords.longitude }
      kdeValues.value['where_lat'] = pos.coords.latitude
      kdeValues.value['where_lng'] = pos.coords.longitude
      gpsLoading.value = false
    },
    (err) => { gpsError.value = 'Không lấy được GPS: ' + err.message; gpsLoading.value = false },
    { enableHighAccuracy: true, timeout: 10000 }
  )
}

// ── Upload attachment (trong timeline) ───────────────────
const uploadingEvent = ref(null)
const uploadProgress = ref(0)

async function uploadFile(ev, file) {
  if (!file) return
  uploadingEvent.value = ev.id
  const fd = new FormData(); fd.append('file', file)
  try {
    await axios.post(route('events.attachments.store', ev.id), fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (e) => { uploadProgress.value = Math.round((e.loaded * 100) / e.total) },
    })
    router.reload({ only: ['events'] })
  } catch (e) { alert('Upload thất bại: ' + (e.response?.data?.error || e.message)) }
  finally { uploadingEvent.value = null; uploadProgress.value = 0 }
}

// ── Edit ──────────────────────────────────────────────────
const editingEvent  = ref(null)
const editKdeValues = ref({})
const editForm = useForm({ cte_code: '', event_time: '', kde_data: {}, who_name: '', where_address: '', where_lat: null, where_lng: null, why_reason: '', note: '' })

function openEdit(ev) {
  editingEvent.value  = ev
  editKdeValues.value = { ...(ev.kde_data ?? {}) }
  editForm.cte_code   = ev.cte_code
  editForm.event_time = ev.event_time?.slice(0, 16) ?? ''
  editForm.note       = ev.note ?? ''
}

function submitEdit() {
  editForm.kde_data = { ...editKdeValues.value }
  editForm.put(route('events.update', editingEvent.value.id), {
    onSuccess: () => { editingEvent.value = null },
  })
}

// ── Delete / Publish ──────────────────────────────────────
function deleteEvent(ev) {
  if (ev.status === 'published') return
  if (!confirm('Xóa sự kiện "' + getCteName(ev) + '"?')) return
  router.delete(route('events.destroy', ev.id))
}

function publishEvent(ev) {
  if (ev.status === 'published') return
  if (!confirm('Publish lên IPFS sẽ KHÓA VĨNH VIỄN sự kiện này. Tiếp tục?')) return
  router.post(route('events.publish', ev.id))
}

// ── Xem chi tiết ─────────────────────────────────────────
const viewingEvent = ref(null)
function openView(ev) { viewingEvent.value = ev }
function closeView()  { viewingEvent.value = null }

// ── Helpers ───────────────────────────────────────────────
function getCteName(ev) {
  if (!ev) return ''
  return ev.cte_template?.name_vi ?? ev.cte_code ?? ev.event_type ?? 'Sự kiện'
}
function formatTime(t) {
  if (!t) return ''
  return new Date(t).toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
function shortCid(cid) {
  if (!cid) return ''
  return cid.length > 14 ? cid.slice(0, 8) + '...' + cid.slice(-6) : cid
}
function kdeLabel(key) {
  return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}
function batchTypeBadge(b) {
  return ({ merged: '[Gộp]', split: '[Tách]', received: '[Nhận]' })[b?.batch_type] ?? ''
}

const inputCls = 'w-full bg-black/20 border border-glass rounded-xl px-3 py-2.5 text-sm text-white/90 placeholder:text-white/30 outline-none focus:border-brand-500/60'
const prevEvents = () => { if (props.events?.prev_page_url) router.visit(props.events.prev_page_url, { preserveState: true }) }
const nextEvents = () => { if (props.events?.next_page_url) router.visit(props.events.next_page_url, { preserveState: true }) }
</script>

<template>
  <Head title="Sự kiện truy xuất" />
  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6 flex flex-wrap items-start justify-between gap-4" data-aos="fade-right">
      <div>
        <div class="text-brand-300 text-sm font-semibold">Truy xuất nguồn gốc</div>
        <h1 class="text-2xl font-bold mt-1 text-white/90">Ghi nhận sự kiện</h1>
        <p class="text-white/40 text-sm mt-1">Chọn lô hàng và ghi dữ liệu 5W theo TCVN 12850:2019.</p>
      </div>
    </div>

    <!-- Chọn lô -->
    <div class="rounded-2xl border border-glass bg-black/20 p-5 space-y-3" data-aos="fade-up" data-aos-delay="100">
      <label class="text-xs font-medium text-white/40 uppercase tracking-widest">Lô hàng đang ghi sự kiện</label>
      <select v-model.number="batchId" :class="inputCls">
        <option :value="null">— Chọn lô hàng —</option>
        <option v-for="b in batches" :key="b.id" :value="b.id">
          {{ b.code }} — {{ b.product_name }}{{ batchTypeBadge(b) ? ' ' + batchTypeBadge(b) : '' }} ({{ b.status }})
        </option>
      </select>
      <div v-if="selectedBatch" class="flex flex-wrap gap-2 text-xs" v-auto-animate>
        <span v-for="cert in (selectedBatch.certifications ?? [])" :key="cert"
          class="px-2 py-0.5 rounded-full border border-brand-500/30 bg-brand-500/10 text-brand-300">{{ cert }}</span>
        <span v-if="selectedBatch.current_quantity != null" class="text-white/30">
          {{ selectedBatch.current_quantity }} {{ selectedBatch.unit }}
        </span>
      </div>
    </div>

    <!-- Main grid -->
    <div v-if="selectedBatch" class="grid grid-cols-1 xl:grid-cols-3 gap-6">

      <!-- CỘT TRÁI: CTE steps -->
      <div class="rounded-2xl border border-glass bg-black/20 overflow-hidden h-fit">
        <div class="px-5 py-4 border-b border-glass">
          <div class="text-xs font-medium text-white/40 uppercase tracking-widest mb-1">Tiến độ ghi nhận</div>
          <div class="flex items-center gap-3">
            <div class="flex-1 h-1.5 rounded-full bg-white/10">
              <div class="h-full rounded-full bg-brand-500 transition-all" :style="{ width: completeness + '%' }"></div>
            </div>
            <span class="text-xs text-white/40">{{ completeness }}%</span>
          </div>
        </div>
        <div class="divide-y divide-white/5" v-auto-animate>
          <button v-for="tpl in cteTemplates" :key="tpl.code" @click="selectCte(tpl)"
            class="w-full flex items-center gap-3 px-5 py-3.5 text-left hover:bg-white/5 transition"
            :class="selectedCte?.code === tpl.code ? 'bg-brand-500/10 border-l-2 border-brand-500' : ''">
            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs shrink-0 border"
              :class="tpl.is_done ? 'bg-brand-500/20 text-brand-300 border-brand-500/40' : 'bg-white/5 text-white/30 border-white/10'">
              <span v-if="tpl.is_done">✓</span><span v-else>{{ tpl.step_order }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium truncate" :class="tpl.is_done ? 'text-white/40 line-through' : 'text-white/80'">{{ tpl.name_vi }}</div>
              <div class="text-xs mt-0.5" :class="tpl.is_done ? 'text-green-500/60' : tpl.is_required ? 'text-brand-400/70' : 'text-white/30'">
                {{ tpl.is_done ? 'Đã hoàn thành' : tpl.is_required ? 'Bắt buộc' : 'Tuỳ chọn' }}
              </div>
            </div>
          </button>
          <!-- Custom event -->
          <button @click="selectCte({ code: 'custom', name_vi: 'Sự kiện tuỳ chỉnh', step_order: 99, is_required: false, is_done: false, kde_schema: [] })"
            class="w-full flex items-center gap-3 px-5 py-3.5 text-left hover:bg-white/5 transition"
            :class="selectedCte?.code === 'custom' ? 'bg-brand-500/10 border-l-2 border-brand-500' : ''">
            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs border border-dashed border-white/20 text-white/30 shrink-0">+</div>
            <div>
              <div class="text-sm text-white/50">Sự kiện tuỳ chỉnh</div>
              <div class="text-xs text-white/20">Ghi bất kỳ hoạt động nào</div>
            </div>
          </button>
        </div>
      </div>

      <!-- CỘT PHẢI: Form + Timeline -->
      <div class="xl:col-span-2 space-y-6">

        <!-- Form ghi sự kiện -->
        <div v-if="showAddForm && selectedCte" class="rounded-2xl border border-white/10 bg-black/20 overflow-hidden">
          <div class="px-5 py-4 border-b border-white/8 flex items-center justify-between">
            <div>
              <div class="text-[11px] text-white/30 uppercase tracking-widest mb-0.5">Ghi nhận sự kiện</div>
              <div class="text-base font-semibold text-white/90">{{ selectedCte.name_vi }}</div>
            </div>
            <button @click="showAddForm=false; selectedCte=null" class="w-7 h-7 rounded-lg flex items-center justify-center text-white/30 hover:text-white/60 hover:bg-white/5 transition text-xl">×</button>
          </div>
          <div class="p-5 space-y-4">
            <div>
              <label class="block text-xs font-medium text-white/50 mb-1.5">Thời gian thực hiện</label>
              <input v-model="form.event_time" type="datetime-local" :class="inputCls" />
            </div>
            <template v-if="selectedCte.kde_schema?.length">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <template v-for="field in selectedCte.kde_schema" :key="field.key">
                  <template v-if="field.key !== 'where_lat' && field.key !== 'where_lng'">
                    <div v-if="field.w === 'WHY' && whySuggestions.length" class="md:col-span-2">
                      <label class="block text-xs font-medium text-white/50 mb-1.5">
                        {{ field.label }}<span v-if="field.required" class="text-red-400 ml-0.5">*</span>
                        <span class="ml-2 text-[10px] text-brand-300/70">💡 gợi ý từ chứng chỉ</span>
                      </label>
                      <div class="flex flex-wrap gap-1.5 mb-2">
                        <button v-for="s in whySuggestions" :key="s" type="button" @click="kdeValues[field.key] = s"
                          class="text-xs px-2.5 py-1.5 rounded-lg border transition"
                          :class="kdeValues[field.key]===s
                            ? 'border-brand-500/60 bg-brand-500/15 text-brand-300'
                            : 'border-white/10 bg-white/5 text-white/50 hover:bg-white/8'">
                          {{ s }}
                        </button>
                      </div>
                      <textarea v-model="kdeValues[field.key]" :placeholder="field.placeholder || field.label"
                        :class="inputCls" rows="2"></textarea>
                    </div>
                    <div v-else :class="field.type === 'textarea' ? 'md:col-span-2' : ''">
                      <label class="block text-xs font-medium text-white/50 mb-1.5">
                        {{ field.label }}<span v-if="field.required" class="text-red-400 ml-0.5">*</span>
                      </label>
                      <textarea v-if="field.type === 'textarea'" v-model="kdeValues[field.key]"
                        :placeholder="field.placeholder || ''" :class="inputCls" rows="2"></textarea>
                      <input v-else v-model="kdeValues[field.key]" :type="field.type || 'text'"
                        :placeholder="field.placeholder || ''" :class="inputCls" />
                    </div>
                  </template>
                </template>
              </div>
              <!-- GPS -->
              <div v-if="selectedCte.kde_schema.some(f => f.key === 'where_lat')" class="space-y-2">
                <label class="block text-xs font-medium text-white/50">GPS Tọa độ</label>
                <div class="flex gap-2 items-center">
                  <button type="button" @click="getGps" :disabled="gpsLoading"
                    class="text-xs px-3 py-1.5 rounded-lg border border-brand-500/40 bg-brand-500/10 hover:bg-brand-500/20 text-brand-300 transition disabled:opacity-50">
                    {{ gpsLoading ? '📡 Đang lấy...' : '📍 Lấy GPS' }}
                  </button>
                  <div v-if="currentGps" class="text-xs text-white/40 font-mono">
                    {{ currentGps.lat.toFixed(5) }}, {{ currentGps.lng.toFixed(5) }}
                  </div>
                  <div v-if="gpsError" class="text-xs text-red-400">{{ gpsError }}</div>
                </div>
              </div>
            </template>
            <div>
              <label class="block text-xs font-medium text-white/50 mb-1.5">Ghi chú thêm</label>
              <textarea v-model="form.note" :class="inputCls" rows="2" placeholder="Tuỳ chọn..."></textarea>
            </div>
            <!-- File đính kèm -->
            <div>
              <label class="block text-xs font-medium text-white/50 mb-1.5">Đính kèm ảnh/tài liệu</label>
              <div class="flex flex-wrap gap-2 items-center">
                <label class="cursor-pointer text-xs px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 hover:bg-white/8 text-white/50 transition">
                  + Chọn file
                  <input type="file" class="hidden" multiple accept=".jpg,.jpeg,.png,.pdf,.webp" @change="onFileSelect" />
                </label>
                <span v-for="(f, i) in pendingFiles" :key="i"
                  class="text-xs px-2 py-1 rounded bg-white/5 border border-white/10 text-white/50 flex items-center gap-1">
                  {{ f.name }}
                  <button type="button" @click="pendingFiles.splice(i,1)" class="text-white/30 hover:text-white/60">✕</button>
                </span>
              </div>
            </div>
            <div class="flex gap-2 pt-2">
              <button type="button" @click="submitForm" :disabled="form.processing"
                class="px-4 py-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-black text-sm font-bold transition disabled:opacity-50">
                {{ form.processing ? 'Đang lưu...' : 'Lưu sự kiện' }}
              </button>
              <button type="button" @click="showAddForm=false; selectedCte=null"
                class="px-4 py-2 rounded-xl border border-glass text-white/50 hover:bg-white/5 text-sm transition">Huỷ</button>
            </div>
          </div>
        </div>

        <!-- Timeline events -->
        <div class="rounded-2xl border border-glass overflow-hidden">
          <div class="px-5 py-4 border-b border-glass flex items-center justify-between">
            <div class="text-sm font-medium text-white/70">
              Lịch sử sự kiện lô <span class="text-brand-300 font-mono">{{ selectedBatch.code }}</span>
            </div>
            <div class="text-xs text-white/30">{{ events?.total ?? 0 }} sự kiện</div>
          </div>

          <div v-if="!events?.data?.length" class="flex flex-col items-center justify-center py-16 text-white/30">
            <div class="text-4xl mb-3">📋</div>
            <div class="text-sm">Chưa có sự kiện nào.</div>
          </div>

          <div v-else class="divide-y divide-white/5" v-auto-animate>
            <div v-for="(ev, i) in events.data" :key="ev.id" class="p-5 space-y-3"
              data-aos="fade-up" :data-aos-delay="300 + (i * 30)">
              <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-semibold text-white/90">{{ getCteName(ev) }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full border"
                      :class="ev.status==='published' ? 'border-green-500/40 bg-green-500/10 text-green-400' : 'border-white/10 bg-white/5 text-white/40'">
                      {{ ev.status === 'published' ? 'Published' : 'Draft' }}
                    </span>
                  </div>
                  <div class="text-xs text-white/40 mt-0.5">{{ formatTime(ev.event_time) }}</div>
                </div>
                <div class="flex gap-1.5 flex-wrap">
                  <button @click="openView(ev)"
                    class="text-xs px-2.5 py-1 rounded-lg border border-sky-500/30 bg-sky-500/10 hover:bg-sky-500/20 text-sky-300 transition">👁 Xem</button>
                  <template v-if="ev.status !== 'published'">
                    <button @click="openEdit(ev)"
                      class="text-xs px-2.5 py-1 rounded-lg border border-glass hover:bg-white/5 text-white/50 transition">Sửa</button>
                    <button @click="publishEvent(ev)"
                      class="text-xs px-2.5 py-1 rounded-lg border border-brand-500/40 bg-brand-500/10 hover:bg-brand-500/20 text-brand-300 transition">Publish</button>
                    <button @click="deleteEvent(ev)"
                      class="text-xs px-2.5 py-1 rounded-lg border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 text-red-400 transition">Xóa</button>
                  </template>
                </div>
              </div>

              <!-- IPFS / BC badges -->
              <div v-if="ev.ipfs_cid || ev.fabric_tx_id" class="flex gap-2 flex-wrap">
                <a v-if="ev.ipfs_url" :href="ev.ipfs_url" target="_blank"
                  class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded border border-brand-500/30 bg-brand-500/10 text-brand-300 hover:bg-brand-500/20 transition">
                  🔗 IPFS: {{ shortCid(ev.ipfs_cid) }}
                </a>
                <span v-if="ev.fabric_tx_id"
                  class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded border border-purple-500/30 bg-purple-500/10 text-purple-300">
                  ⛓ BC: {{ ev.fabric_tx_id.slice(0,12) }}...
                </span>
              </div>

              <!-- Attachments -->
              <div v-if="ev.attachments?.length" class="flex flex-wrap gap-2">
                <a v-for="att in ev.attachments" :key="att.cid ?? att.url" :href="att.url" target="_blank"
                  class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-lg border border-white/10 bg-white/5 hover:bg-white/8 text-white/50 transition">
                  📎 {{ att.name }}
                </a>
              </div>

              <!-- Upload attachment (draft only) -->
              <div v-if="ev.status !== 'published'">
                <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs px-2.5 py-1 rounded-lg border border-white/10 bg-white/5 hover:bg-white/8 text-white/40 transition">
                  {{ uploadingEvent === ev.id ? `Đang upload ${uploadProgress}%...` : '+ Đính kèm file' }}
                  <input type="file" class="hidden" accept=".jpg,.jpeg,.png,.pdf,.webp" @change="uploadFile(ev, $event.target.files?.[0])" />
                </label>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="events?.last_page > 1" class="px-5 py-3 border-t border-glass flex items-center justify-between">
            <button @click="prevEvents" :disabled="!events.prev_page_url" class="text-xs px-3 py-1.5 rounded-lg border border-glass text-white/50 disabled:opacity-30 hover:bg-white/5 transition">‹ Trước</button>
            <span class="text-xs text-white/30">{{ events.current_page }} / {{ events.last_page }}</span>
            <button @click="nextEvents" :disabled="!events.next_page_url" class="text-xs px-3 py-1.5 rounded-lg border border-glass text-white/50 disabled:opacity-30 hover:bg-white/5 transition">Sau ›</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="flex flex-col items-center justify-center py-20 text-white/20">
      <div class="text-5xl mb-4">📦</div>
      <div class="text-base">Chọn một lô hàng để bắt đầu ghi sự kiện</div>
    </div>

  </div>

  <!-- Modal xem chi tiết -->
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="viewingEvent" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeView">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-2xl max-h-[90vh] flex flex-col rounded-2xl border border-white/10 bg-[#0d1117] shadow-2xl overflow-hidden">
          <div class="px-6 py-4 border-b border-white/8 flex items-start justify-between gap-3 shrink-0">
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h2 class="text-base font-bold text-white/90">{{ getCteName(viewingEvent) }}</h2>
                <span class="text-xs px-2 py-0.5 rounded-full border"
                  :class="viewingEvent.status==='published' ? 'border-green-500/40 bg-green-500/10 text-green-400' : 'border-amber-500/40 bg-amber-500/10 text-amber-400'">
                  {{ viewingEvent.status === 'published' ? 'Published' : 'Draft' }}
                </span>
              </div>
              <div class="text-xs text-white/40 mt-1">
                Lô: <span class="text-white/60">{{ viewingEvent.batch?.code }}</span>
                &nbsp;·&nbsp;{{ formatTime(viewingEvent.event_time) }}
              </div>
            </div>
            <button @click="closeView" class="shrink-0 w-8 h-8 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/40 hover:text-white/70 text-xl transition">✕</button>
          </div>
          <div class="overflow-y-auto flex-1 p-6 space-y-5">
            <div class="grid grid-cols-1 gap-3">
              <div v-if="viewingEvent.who_name" class="rounded-xl bg-white/5 border border-white/8 px-4 py-3">
                <div class="text-[10px] text-white/30 uppercase tracking-widest mb-1">WHO — Người thực hiện</div>
                <div class="text-sm text-white/80">{{ viewingEvent.who_name }}</div>
              </div>
              <div v-if="viewingEvent.where_address" class="rounded-xl bg-white/5 border border-white/8 px-4 py-3">
                <div class="text-[10px] text-white/30 uppercase tracking-widest mb-1">WHERE — Địa điểm</div>
                <div class="text-sm text-white/80">{{ viewingEvent.where_address }}</div>
                <div v-if="viewingEvent.where_lat" class="text-xs text-white/30 mt-1 font-mono">
                  GPS: {{ viewingEvent.where_lat }}, {{ viewingEvent.where_lng }}
                </div>
              </div>
              <div v-if="viewingEvent.why_reason" class="rounded-xl bg-white/5 border border-white/8 px-4 py-3">
                <div class="text-[10px] text-white/30 uppercase tracking-widest mb-1">WHY — Lý do / Tiêu chuẩn</div>
                <div class="text-sm text-white/80">{{ viewingEvent.why_reason }}</div>
              </div>
              <div v-if="viewingEvent.note" class="rounded-xl bg-white/5 border border-white/8 px-4 py-3">
                <div class="text-[10px] text-white/30 uppercase tracking-widest mb-1">Ghi chú</div>
                <div class="text-sm text-white/70">{{ viewingEvent.note }}</div>
              </div>
            </div>
            <div v-if="viewingEvent.kde_data && Object.keys(viewingEvent.kde_data).length">
              <div class="text-[10px] text-white/30 uppercase tracking-widest mb-2">WHAT — Dữ liệu KDE chi tiết</div>
              <div class="rounded-xl border border-white/8 overflow-hidden">
                <div v-for="(val, key) in viewingEvent.kde_data" :key="key"
                  class="flex gap-3 px-4 py-2.5 border-b border-white/5 last:border-0 hover:bg-white/3 transition">
                  <span class="text-xs text-white/30 shrink-0 w-40 truncate">{{ kdeLabel(key) }}</span>
                  <span class="text-xs text-white/70 flex-1 break-words">{{ val ?? '—' }}</span>
                </div>
              </div>
            </div>
            <div v-if="viewingEvent.attachments?.length">
              <div class="text-[10px] text-white/30 uppercase tracking-widest mb-2">Đính kèm</div>
              <div class="flex flex-wrap gap-2">
                <a v-for="att in viewingEvent.attachments" :key="att.cid ?? att.url" :href="att.url" target="_blank"
                  class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 hover:bg-white/8 text-white/50 transition">
                  📎 {{ att.name }}
                </a>
              </div>
            </div>
            <div v-if="viewingEvent.ipfs_cid || viewingEvent.fabric_tx_id" class="rounded-xl bg-white/5 border border-white/8 px-4 py-3 space-y-1">
              <div class="text-[10px] text-white/30 uppercase tracking-widest mb-2">Blockchain / IPFS</div>
              <div v-if="viewingEvent.ipfs_cid" class="flex gap-2 text-xs text-white/40">
                <span class="text-white/30 shrink-0">IPFS CID</span>
                <span class="font-mono text-brand-300">{{ viewingEvent.ipfs_cid }}</span>
              </div>
              <div v-if="viewingEvent.fabric_tx_id" class="flex gap-2 flex-wrap text-xs text-white/40">
                <span class="text-white/30 shrink-0">Fabric TxID</span>
                <span class="font-mono text-purple-300 break-all">{{ viewingEvent.fabric_tx_id }}</span>
              </div>
              <div v-if="viewingEvent.published_at" class="flex gap-2 flex-wrap text-white/40 text-xs">
                <span class="text-white/30 shrink-0">Published</span>
                <span>{{ formatTime(viewingEvent.published_at) }}</span>
                <span v-if="viewingEvent.publisher">· bởi {{ viewingEvent.publisher?.name }}</span>
              </div>
            </div>
          </div>
          <div class="px-6 py-4 border-t border-white/8 flex gap-2 shrink-0">
            <a v-if="viewingEvent.ipfs_url" :href="viewingEvent.ipfs_url" target="_blank"
              class="text-xs px-3 py-2 rounded-xl border border-brand-500/30 bg-brand-500/10 hover:bg-brand-500/20 text-brand-300 transition">
              🔗 Xem trên IPFS
            </a>
            <button v-if="viewingEvent.status !== 'published'" @click="publishEvent(viewingEvent); closeView()"
              class="text-xs px-3 py-2 rounded-xl border border-green-500/30 bg-green-500/10 hover:bg-green-500/20 text-green-300 transition">
              Publish ngay
            </button>
            <div class="flex-1"></div>
            <button @click="closeView" class="text-xs px-4 py-2 rounded-xl border border-glass hover:bg-white/5 text-white/50 transition">Đóng</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Modal Edit -->
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="editingEvent" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="editingEvent=null">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-lg max-h-[90vh] flex flex-col rounded-2xl border border-glass bg-[#0d1117] overflow-hidden">
          <div class="px-5 py-4 border-b border-glass flex items-center justify-between shrink-0">
            <div class="text-base font-semibold text-white/90">Sửa sự kiện</div>
            <button @click="editingEvent=null" class="text-white/30 hover:text-white/60 text-xl">✕</button>
          </div>
          <div class="overflow-y-auto flex-1 p-5 space-y-4">
            <div>
              <label class="block text-xs font-medium text-white/50 mb-1.5">Thời gian thực hiện</label>
              <input v-model="editForm.event_time" type="datetime-local" :class="inputCls" />
            </div>
            <div v-for="(val, key) in editKdeValues" :key="key">
              <label class="block text-xs font-medium text-white/50 mb-1.5">{{ kdeLabel(key) }}</label>
              <input v-model="editKdeValues[key]" type="text" :class="inputCls" />
            </div>
            <div>
              <label class="block text-xs font-medium text-white/50 mb-1.5">Ghi chú</label>
              <textarea v-model="editForm.note" :class="inputCls" rows="2"></textarea>
            </div>
          </div>
          <div class="px-5 py-4 border-t border-glass flex justify-end gap-2 shrink-0">
            <button @click="editingEvent=null" class="text-xs px-4 py-2 rounded-xl border border-glass hover:bg-white/5 text-white/50 transition">Huỷ</button>
            <button @click="submitEdit" :disabled="editForm.processing"
              class="text-xs px-4 py-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-black font-bold transition disabled:opacity-50">
              {{ editForm.processing ? 'Đang lưu...' : 'Lưu thay đổi' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

</template>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity .2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
</style>