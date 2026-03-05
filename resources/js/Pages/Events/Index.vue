<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  batches: Array,
  events:  Object,
  filters: Object,
})


const flash = usePage().props.flash ?? {}

// ── Pending attachments (upload sau khi save event) ───────
const pendingFiles = ref([])

function onFileSelect(e) {
  const files = Array.from(e.target.files ?? [])
  pendingFiles.value.push(...files)
  e.target.value = '' // reset input để có thể chọn lại cùng file
}

async function uploadPendingFiles(eventId) {
  for (const file of pendingFiles.value) {
    const fd = new FormData()
    fd.append('file', file)
    try {
      await axios.post(route('events.attachments.store', eventId), fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    } catch (e) {
      console.error('Upload thất bại:', file.name, e)
    }
  }
  pendingFiles.value = []
}

// ── WHY suggestions theo cert + CTE ──────────────────────
const CERT_WHY_MAP = {
  'VietGAP': {
    'harvest':    [
      'Thực hiện thu hoạch theo tiêu chuẩn VietGAP',
      'Thu hoạch đúng độ chín, đảm bảo vệ sinh theo VietGAP',
      'Tuân thủ quy trình thu hoạch VietGAP — không dùng hóa chất cấm',
    ],
    'packaging':  [
      'Đóng gói theo quy định VietGAP về bao bì và nhãn mác',
      'Kiểm tra chất lượng bao bì theo tiêu chuẩn VietGAP',
    ],
    'transport':  [
      'Vận chuyển theo quy trình bảo quản VietGAP',
      'Đảm bảo điều kiện vận chuyển theo VietGAP',
    ],
    'storage':    [
      'Bảo quản theo điều kiện kho VietGAP',
      'Kiểm soát nhiệt độ, độ ẩm kho theo VietGAP',
    ],
    'inspection': [
      'Kiểm tra chất lượng sản phẩm theo tiêu chuẩn VietGAP',
      'Lấy mẫu kiểm nghiệm theo quy định VietGAP',
    ],
    '*': [
      'Thực hiện theo tiêu chuẩn VietGAP',
      'Tuân thủ quy trình sản xuất nông nghiệp tốt VietGAP',
    ],
  },
  'GlobalGAP': {
    'harvest':   ['Thu hoạch theo tiêu chuẩn GlobalGAP', 'Kiểm soát dư lượng thuốc BVTV theo GlobalGAP'],
    'packaging': ['Đóng gói theo GlobalGAP — truy xuất từng lô'],
    '*':         ['Thực hiện theo tiêu chuẩn GlobalGAP', 'Tuân thủ quy trình GlobalGAP'],
  },
  'ISO 22000': {
    'processing': ['Chế biến theo hệ thống quản lý ATTP ISO 22000:2018', 'Kiểm soát mối nguy theo ISO 22000'],
    'packaging':  ['Đóng gói theo ISO 22000 — kiểm soát điểm tới hạn CCP'],
    'storage':    ['Bảo quản theo ISO 22000 — kiểm soát nhiệt độ', 'Giám sát điều kiện bảo quản theo ISO 22000'],
    'inspection': ['Kiểm tra theo chương trình tiên quyết ISO 22000', 'Lấy mẫu kiểm nghiệm theo kế hoạch ISO 22000'],
    '*':          ['Thực hiện theo ISO 22000:2018', 'Tuân thủ hệ thống quản lý an toàn thực phẩm ISO 22000'],
  },
  'HACCP': {
    'processing': ['Kiểm soát điểm tới hạn CCP theo HACCP', 'Thực hiện theo kế hoạch HACCP đã phê duyệt', 'Giám sát giới hạn tới hạn CCP theo HACCP'],
    'packaging':  ['Đóng gói theo giới hạn tới hạn HACCP'],
    'storage':    ['Bảo quản theo CCP nhiệt độ trong kế hoạch HACCP'],
    '*':          ['Thực hiện theo kế hoạch HACCP', 'Kiểm soát mối nguy sinh học/hóa học/vật lý theo HACCP'],
  },
  'ISO 9001': {
    '*': ['Thực hiện theo hệ thống quản lý chất lượng ISO 9001:2015', 'Tuân thủ quy trình kiểm soát chất lượng ISO 9001'],
  },
  'Organic': {
    'harvest': ['Thu hoạch theo tiêu chuẩn hữu cơ — không dùng hóa chất tổng hợp'],
    'storage': ['Bảo quản hữu cơ — tách biệt với sản phẩm thông thường'],
    '*':       ['Thực hiện theo tiêu chuẩn nông nghiệp hữu cơ', 'Không sử dụng hóa chất tổng hợp theo tiêu chuẩn Organic'],
  },
  'OCOP': { '*': ['Sản phẩm đạt tiêu chuẩn OCOP', 'Thực hiện theo tiêu chí chất lượng OCOP'] },
  'FDA':  { '*': ['Đáp ứng tiêu chuẩn FDA', 'Thực hiện theo quy định FDA — Cục Quản lý Thực phẩm và Dược phẩm Hoa Kỳ'] },
  'BRC':  { '*': ['Thực hiện theo tiêu chuẩn BRC Global Standard', 'Tuân thủ yêu cầu an toàn thực phẩm BRC'] },
  'SQF':  { '*': ['Thực hiện theo tiêu chuẩn Safe Quality Food (SQF)'] },
}

// Template generic cho lô không có category (merged/split/received)
const GENERIC_TEMPLATES = [
  {
    id: 'transformation', code: 'transformation', name_vi: 'Ghi nhận hoạt động',
    step_order: 1, is_required: false, is_done: false,
    kde_schema: [
      { key: 'who_actor',     label: 'Người/đơn vị thực hiện', w: 'WHO',   type: 'text',     required: true,  placeholder: 'Tên người thực hiện' },
      { key: 'what_action',   label: 'Hoạt động thực hiện',    w: 'WHAT',  type: 'text',     required: true,  placeholder: 'VD: Phân loại, đóng gói, kiểm tra...' },
      { key: 'where_address', label: 'Địa điểm',               w: 'WHERE', type: 'text',     required: false, placeholder: 'Địa chỉ thực hiện' },
      { key: 'where_lat',     label: 'Latitude',               w: 'WHERE', type: 'number',   required: false },
      { key: 'where_lng',     label: 'Longitude',              w: 'WHERE', type: 'number',   required: false },
      { key: 'why_reason',    label: 'Lý do / Tiêu chuẩn',    w: 'WHY',   type: 'text',     required: false, placeholder: 'Căn cứ thực hiện' },
      { key: 'what_note',     label: 'Ghi chú thêm',           w: 'WHAT',  type: 'textarea', required: false, placeholder: 'Thông tin bổ sung' },
    ],
  },
  {
    id: 'inspection', code: 'inspection', name_vi: 'Kiểm tra chất lượng',
    step_order: 2, is_required: false, is_done: false,
    kde_schema: [
      { key: 'who_inspector',  label: 'Người kiểm tra',     w: 'WHO',   type: 'text',     required: true,  placeholder: 'Tên người/đơn vị kiểm tra' },
      { key: 'what_result',    label: 'Kết quả kiểm tra',   w: 'WHAT',  type: 'select',   required: true,  options: ['Đạt', 'Không đạt', 'Cần xem xét'] },
      { key: 'what_criteria',  label: 'Tiêu chí kiểm tra',  w: 'WHAT',  type: 'text',     required: false, placeholder: 'VD: Độ ẩm, màu sắc, mùi...' },
      { key: 'where_address',  label: 'Địa điểm',           w: 'WHERE', type: 'text',     required: false },
      { key: 'where_lat',      label: 'Latitude',           w: 'WHERE', type: 'number',   required: false },
      { key: 'where_lng',      label: 'Longitude',          w: 'WHERE', type: 'number',   required: false },
      { key: 'why_reason',     label: 'Tiêu chuẩn áp dụng', w: 'WHY',  type: 'text',     required: false, placeholder: 'VD: TCVN 5644:2008' },
      { key: 'what_note',      label: 'Ghi chú',             w: 'WHAT', type: 'textarea', required: false },
    ],
  },
  {
    id: 'storage', code: 'storage', name_vi: 'Nhập / Xuất kho',
    step_order: 3, is_required: false, is_done: false,
    kde_schema: [
      { key: 'who_actor',     label: 'Người thực hiện', w: 'WHO',   type: 'text',   required: true },
      { key: 'what_action',   label: 'Loại thao tác',   w: 'WHAT',  type: 'select', required: true, options: ['Nhập kho', 'Xuất kho', 'Kiểm kho'] },
      { key: 'what_quantity', label: 'Số lượng',        w: 'WHAT',  type: 'number', required: false },
      { key: 'where_address', label: 'Kho',             w: 'WHERE', type: 'text',   required: false, placeholder: 'Tên/địa chỉ kho' },
      { key: 'where_lat',     label: 'Latitude',        w: 'WHERE', type: 'number', required: false },
      { key: 'where_lng',     label: 'Longitude',       w: 'WHERE', type: 'number', required: false },
      { key: 'why_reason',    label: 'Lý do',           w: 'WHY',   type: 'text',   required: false },
    ],
  },
  {
    id: 'transport', code: 'transport', name_vi: 'Vận chuyển',
    step_order: 4, is_required: false, is_done: false,
    kde_schema: [
      { key: 'who_driver',    label: 'Người/đơn vị vận chuyển', w: 'WHO',   type: 'text', required: true },
      { key: 'what_vehicle',  label: 'Phương tiện',             w: 'WHAT',  type: 'text', required: false, placeholder: 'Biển số xe, loại xe...' },
      { key: 'where_from',    label: 'Điểm đi',                 w: 'WHERE', type: 'text', required: false },
      { key: 'where_to',      label: 'Điểm đến',                w: 'WHERE', type: 'text', required: false },
      { key: 'where_lat',     label: 'Latitude',                w: 'WHERE', type: 'number', required: false },
      { key: 'where_lng',     label: 'Longitude',               w: 'WHERE', type: 'number', required: false },
      { key: 'why_reason',    label: 'Điều kiện vận chuyển',    w: 'WHY',   type: 'text', required: false, placeholder: 'Nhiệt độ, độ ẩm...' },
    ],
  },
]

// ── Batch filter ──────────────────────────────────────────
const batchId = ref(props.filters?.batch_id ?? '')
watch(batchId, (val) => {
  showAddForm.value = false
  selectedCte.value = null
  router.get(route('events.index'), { batch_id: val || undefined }, { preserveState: true, replace: true })
})

const selectedBatch = computed(() => {
  const b = props.batches?.find(b => b.id == batchId.value)
  if (!b) return null
  return { ...b, certifications: b.certifications ?? [] }
})

// ── WHY suggestions ───────────────────────────────────────
const whySuggestions = computed(() => {
  const certs = selectedBatch.value?.certifications ?? []
  const cte   = selectedCte.value?.code ?? ''
  const suggestions = []
  for (const cert of certs) {
    const map = CERT_WHY_MAP[cert]
    if (!map) continue
    const items = (map[cte]?.length ? map[cte] : map['*']) ?? []
    for (const s of items) {
      if (!suggestions.includes(s)) suggestions.push(s)
    }
  }
  return suggestions
})

// ── CTE Templates ─────────────────────────────────────────
const cteTemplates = ref([])
const loadingCte   = ref(false)

async function loadTemplates(catId, bId) {
  loadingCte.value = true
  try {
    if (catId) {
      // Lô có category → load template theo ngành
      const { data } = await axios.get('/api/cte-templates', {
        params: { category_id: catId, batch_id: bId || undefined }
      })
      cteTemplates.value = data.templates
    } else {
      // Lô merged/split/received → dùng template generic
      cteTemplates.value = GENERIC_TEMPLATES
    }
  } finally {
    loadingCte.value = false
  }
}

watch(selectedBatch, (b) => {
  if (b) loadTemplates(b.category_id, b.id)
  else cteTemplates.value = []
}, { immediate: true })

const requiredDone  = computed(() => cteTemplates.value.filter(t => t.is_required && t.is_done).length)
const requiredTotal = computed(() => cteTemplates.value.filter(t => t.is_required).length)
const completeness  = computed(() =>
  requiredTotal.value ? Math.round((requiredDone.value / requiredTotal.value) * 100) : 0
)

// ── Add Event Form ────────────────────────────────────────
const showAddForm = ref(false)
const selectedCte = ref(null)
const kdeValues   = ref({})

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
  if (currentGps.value) {
    kdeValues.value['where_lat'] = currentGps.value.lat
    kdeValues.value['where_lng'] = currentGps.value.lng
  }
}

function buildKdeData() {
  const kde = {}
  if (selectedCte.value?.kde_schema) {
    for (const f of selectedCte.value.kde_schema) {
      kde[f.key] = kdeValues.value[f.key] ?? null
    }
  }
  return kde
}

function extractIndexFields() {
  const whoField   = selectedCte.value?.kde_schema?.find(f => f.w === 'WHO' && f.required)
  const whereField = selectedCte.value?.kde_schema?.find(f => f.key === 'where_address')
  const whyField   = selectedCte.value?.kde_schema?.find(f => f.w === 'WHY')
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
    onSuccess: async (page) => {
      // Lấy event vừa tạo từ danh sách events mới nhất
      const newEvent = page.props.events?.data?.[0]
      if (newEvent && pendingFiles.value.length) {
        await uploadPendingFiles(newEvent.id)
      }

      showAddForm.value = false
      selectedCte.value = null
      kdeValues.value   = {}
      pendingFiles.value = []
      form.reset()
      form.event_time = new Date().toISOString().slice(0, 16)
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
  gpsLoading.value = true
  gpsError.value   = ''
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      currentGps.value             = { lat: pos.coords.latitude, lng: pos.coords.longitude }
      kdeValues.value['where_lat'] = pos.coords.latitude
      kdeValues.value['where_lng'] = pos.coords.longitude
      gpsLoading.value             = false
    },
    (err) => { gpsError.value = 'Không lấy được GPS: ' + err.message; gpsLoading.value = false },
    { enableHighAccuracy: true, timeout: 10000 }
  )
}

// ── Attachment upload ─────────────────────────────────────
const uploadingEvent = ref(null)
const uploadProgress = ref(0)

async function uploadFile(event, file) {
  if (!file) return
  uploadingEvent.value = event.id
  const fd = new FormData()
  fd.append('file', file)
  try {
    await axios.post(route('events.attachments.store', event.id), fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (e) => { uploadProgress.value = Math.round((e.loaded * 100) / e.total) },
    })
    router.reload({ only: ['events'] })
  } catch (e) {
    alert('Upload thất bại: ' + (e.response?.data?.error || e.message))
  } finally {
    uploadingEvent.value = null
    uploadProgress.value  = 0
  }
}

// ── Edit / Delete / Publish ───────────────────────────────
const editingEvent  = ref(null)
const editKdeValues = ref({})
const editForm = useForm({
  cte_code: '', event_time: '', kde_data: {},
  who_name: '', where_address: '', where_lat: null, where_lng: null, why_reason: '', note: '',
})

function openEdit(ev) {
  editingEvent.value  = ev
  editKdeValues.value = { ...ev.kde_data }
  editForm.cte_code   = ev.cte_code
  editForm.event_time = ev.event_time?.slice(0, 16) ?? ''
  editForm.note       = ev.note ?? ''
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
    onSuccess: () => { editingEvent.value = null },
  })
}

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

// ── Helpers ───────────────────────────────────────────────
function getCteName(ev) {
  if (!ev) return ''
  return ev.cte_template?.name_vi ?? ev.cte_code ?? ev.event_type ?? 'Sự kiện'
}

function formatTime(t) {
  if (!t) return ''
  const d = new Date(t)
  return d.toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function shortCid(cid) {
  if (!cid) return ''
  return cid.length > 14 ? cid.slice(0, 8) + '...' + cid.slice(-6) : cid
}

// Badge type cho lô
function batchTypeBadge(b) {
  if (!b) return ''
  const map = { merged: '[Gộp]', split: '[Tách]', received: '[Nhận]' }
  return map[b.batch_type] ?? ''
}

function batchTypeColor(b) {
  const map = { merged: 'text-purple-400', split: 'text-amber-400', received: 'text-blue-400' }
  return map[b?.batch_type] ?? 'text-white/50'
}

const inputCls = 'w-full bg-black/20 border border-glass rounded-xl px-3 py-2.5 text-sm text-white/90 placeholder:text-white/30 outline-none focus:border-brand-500/60'

// Pagination events
const prevEvents = () => { if (props.events?.prev_page_url) router.visit(props.events.prev_page_url, { preserveState: true }) }
const nextEvents = () => { if (props.events?.next_page_url) router.visit(props.events.next_page_url, { preserveState: true }) }
</script>

<template>
  <Head title="Sự kiện truy xuất" />

  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6 flex flex-wrap items-start justify-between gap-4">
      <div>
        <div class="text-brand-300 text-sm font-semibold">Truy xuất nguồn gốc</div>
        <h1 class="text-2xl font-bold mt-1 text-white/90">Ghi nhận sự kiện</h1>
        <p class="text-white/40 text-sm mt-1">Chọn lô hàng và ghi dữ liệu 5W theo TCVN 12850:2019.</p>
      </div>
      <div v-if="flash.success"
        class="px-4 py-2 rounded-xl border border-green-500/30 bg-green-500/10 text-sm text-green-400">
        {{ flash.success }}
      </div>
    </div>

    <!-- Chọn lô -->
    <div class="rounded-2xl border border-glass bg-black/20 p-4 flex flex-wrap items-center gap-4">
      <label class="text-sm text-white/50 whitespace-nowrap">Lô hàng:</label>
      <select v-model="batchId"
        class="flex-1 min-w-48 bg-black/30 border border-glass rounded-xl px-4 py-2.5 text-sm text-white/90 outline-none focus:border-brand-500/60">
        <option value="">-- Chọn lô hàng --</option>
        <option v-for="b in batches" :key="b.id" :value="b.id">
          {{ b.code }} — {{ b.product_name }}
          {{ batchTypeBadge(b) }}
        </option>
      </select>

      <!-- Info lô đang chọn -->
      <div v-if="selectedBatch" class="flex flex-wrap items-center gap-2">
        <span class="text-xs px-2 py-1 rounded-full border border-green-500/30 bg-green-500/10 text-green-400">
          Hoạt động
        </span>
        <span v-if="selectedBatch.batch_type && selectedBatch.batch_type !== 'original'"
          class="text-xs px-2 py-1 rounded-full border border-white/10 bg-white/5"
          :class="batchTypeColor(selectedBatch)">
          {{ batchTypeBadge(selectedBatch) }}
        </span>
        <!-- Chứng chỉ của lô -->
        <span v-for="cert in selectedBatch.certifications" :key="cert"
          class="text-xs px-2 py-0.5 rounded border border-brand-500/30 bg-brand-500/10 text-brand-300">
          {{ cert }}
        </span>
        <!-- Số lượng -->
        <span v-if="selectedBatch.current_quantity" class="text-xs text-white/40">
          {{ selectedBatch.current_quantity }} {{ selectedBatch.unit }}
        </span>
      </div>
    </div>

    <!-- Layout 2 col khi đã chọn lô -->
    <div v-if="selectedBatch" class="grid grid-cols-1 xl:grid-cols-3 gap-6">

      <!-- CỘT TRÁI: CTE Stepper -->
      <div class="xl:col-span-1 space-y-4">

        <!-- Progress tổng thể (chỉ hiện khi có template theo ngành) -->
        <div v-if="requiredTotal > 0" class="rounded-2xl border border-glass bg-black/20 p-5">
          <div class="flex items-center justify-between mb-3">
            <div class="text-sm font-semibold text-white/70">Tiến độ chuẩn TCVN</div>
            <div class="text-sm font-bold" :class="completeness === 100 ? 'text-green-400' : 'text-brand-400'">
              {{ completeness }}%
            </div>
          </div>
          <div class="h-2 bg-white/10 rounded-full overflow-hidden">
            <div class="h-2 rounded-full transition-all duration-500"
              :class="completeness === 100 ? 'bg-green-500' : 'bg-brand-500'"
              :style="{ width: completeness + '%' }" />
          </div>
          <div class="text-xs text-white/40 mt-2">
            {{ requiredDone }}/{{ requiredTotal }} bước bắt buộc đã hoàn thành
          </div>
        </div>

        <!-- Info lô không có CTE ngành -->
        <div v-else-if="!loadingCte && selectedBatch.batch_type && selectedBatch.batch_type !== 'original'"
          class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4">
          <div class="text-xs text-amber-300 font-semibold mb-1">Lô qua biến đổi</div>
          <div class="text-xs text-white/40">
            Lô {{ selectedBatch.batch_type === 'merged' ? 'gộp' : selectedBatch.batch_type === 'split' ? 'tách' : 'nhận' }}
            — dùng các bước ghi nhận chung bên dưới.
          </div>
        </div>

        <!-- Danh sách CTE steps -->
        <div class="rounded-2xl border border-glass bg-black/20 overflow-hidden">
          <div class="px-5 py-3 border-b border-glass text-xs text-white/40 uppercase tracking-widest">
            {{ selectedBatch.category_id ? 'Các bước truy xuất' : 'Ghi nhận hoạt động' }}
          </div>

          <div v-if="loadingCte" class="p-5 text-center text-white/30 text-sm">Đang tải...</div>

          <div v-else class="divide-y divide-white/5">
            <button
              v-for="tpl in cteTemplates" :key="tpl.code"
              @click="selectCte(tpl)"
              class="w-full flex items-center gap-3 px-5 py-3.5 text-left transition cursor-pointer"
              :class="selectedCte?.code === tpl.code
                ? 'bg-brand-500/10 border-l-2 border-brand-500'
                : tpl.is_done
                  ? 'opacity-50 hover:bg-white/3'
                  : 'hover:bg-white/5'"
            >
              <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition"
                :class="tpl.is_done
                  ? 'bg-green-500/20 text-green-400 border border-green-500/40'
                  : tpl.is_required
                    ? 'bg-brand-500/20 text-brand-300 border border-brand-500/40'
                    : 'bg-white/5 text-white/30 border border-white/10'">
                <span v-if="tpl.is_done">✓</span>
                <span v-else>{{ tpl.step_order }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-sm font-medium truncate"
                  :class="tpl.is_done ? 'text-white/40 line-through' : 'text-white/80'">
                  {{ tpl.name_vi }}
                </div>
                <div class="text-xs mt-0.5"
                  :class="tpl.is_done ? 'text-green-500/60' : tpl.is_required ? 'text-brand-400/70' : 'text-white/30'">
                  {{ tpl.is_done ? 'Đã hoàn thành' : tpl.is_required ? 'Bắt buộc' : 'Tuỳ chọn' }}
                </div>
              </div>
              <span class="text-white/20 text-xs">›</span>
            </button>

            <!-- Custom event luôn hiện -->
            <button
              @click="selectCte({
                code: 'custom', name_vi: 'Sự kiện tuỳ chỉnh', step_order: '—', is_required: false, is_done: false,
                kde_schema: [
                  { key: 'who_performer', label: 'Người thực hiện',     w: 'WHO',   type: 'text',     required: true,  placeholder: 'Tên người/đơn vị' },
                  { key: 'what_activity', label: 'Nội dung hoạt động',  w: 'WHAT',  type: 'textarea', required: true,  placeholder: 'Mô tả hoạt động' },
                  { key: 'where_address', label: 'Địa điểm',            w: 'WHERE', type: 'text',     required: false },
                  { key: 'where_lat',     label: 'Latitude',            w: 'WHERE', type: 'number',   required: false },
                  { key: 'where_lng',     label: 'Longitude',           w: 'WHERE', type: 'number',   required: false },
                  { key: 'why_reason',    label: 'Ghi chú / Lý do',     w: 'WHY',   type: 'textarea', required: false },
                ]
              })"
              class="w-full flex items-center gap-3 px-5 py-3.5 text-left hover:bg-white/5 transition"
              :class="selectedCte?.code === 'custom' ? 'bg-brand-500/10 border-l-2 border-brand-500' : ''"
            >
              <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs border border-dashed border-white/20 text-white/30 shrink-0">+</div>
              <div>
                <div class="text-sm text-white/50">Sự kiện tuỳ chỉnh</div>
                <div class="text-xs text-white/20">Ghi bất kỳ hoạt động nào</div>
              </div>
            </button>
          </div>
        </div>

      </div>

      <!-- CỘT PHẢI: Form + Timeline -->
      <div class="xl:col-span-2 space-y-6">

        <!-- Form ghi sự kiện -->
        <div v-if="showAddForm && selectedCte"
          class="rounded-2xl border border-white/10 bg-black/20 overflow-hidden">

          <div class="px-5 py-4 border-b border-white/8 flex items-center justify-between">
            <div>
              <div class="text-[11px] text-white/30 uppercase tracking-widest mb-0.5">Ghi nhận sự kiện</div>
              <div class="text-base font-semibold text-white/90">{{ selectedCte.name_vi }}</div>
            </div>
            <button @click="showAddForm = false; selectedCte = null"
              class="w-7 h-7 rounded-lg flex items-center justify-center text-white/30 hover:text-white/60 hover:bg-white/5 transition text-xl leading-none">×</button>
          </div>

          <div class="p-5 space-y-4">

            <!-- Thời gian -->
            <div>
              <label class="block text-xs font-medium text-white/50 mb-1.5">Thời gian thực hiện</label>
              <input v-model="form.event_time" type="datetime-local" :class="inputCls" />
            </div>

            <!-- KDE fields -->
            <template v-if="selectedCte.kde_schema?.length">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <template v-for="field in selectedCte.kde_schema" :key="field.key">
                  <template v-if="field.key !== 'where_lat' && field.key !== 'where_lng'">

                    <!-- WHY field: có cert suggestions -->
                    <div v-if="field.w === 'WHY' && whySuggestions.length" class="md:col-span-2">
                      <label class="block text-xs font-medium text-white/50 mb-1.5">
                        {{ field.label }}
                        <span v-if="field.required" class="text-red-400 ml-0.5">*</span>
                        <span class="ml-2 text-[10px] text-brand-300/70">💡 gợi ý từ chứng chỉ của lô</span>
                      </label>
                      <!-- Suggestions chips -->
                      <div class="flex flex-wrap gap-1.5 mb-2">
                        <button
                          v-for="s in whySuggestions" :key="s"
                          type="button"
                          @click="kdeValues[field.key] = s"
                          class="text-xs px-2.5 py-1.5 rounded-lg border transition text-left"
                          :class="kdeValues[field.key] === s
                            ? 'border-brand-500/60 bg-brand-500/15 text-brand-300'
                            : 'border-glass bg-white/5 text-white/50 hover:bg-white/8'"
                        >{{ s }}</button>
                      </div>
                      <input v-model="kdeValues[field.key]" type="text"
                        :class="inputCls" placeholder="Hoặc nhập lý do tuỳ chỉnh..." />
                    </div>

                    <!-- WHY field: không có cert -->
                    <div v-else-if="field.w === 'WHY'" class="md:col-span-2">
                      <label class="block text-xs font-medium text-white/50 mb-1.5">
                        {{ field.label }}<span v-if="field.required" class="text-red-400 ml-0.5">*</span>
                      </label>
                      <input v-model="kdeValues[field.key]" type="text"
                        :placeholder="field.placeholder || ''" :class="inputCls" />
                    </div>

                    <!-- Các field khác -->
                    <div v-else :class="field.type === 'textarea' ? 'md:col-span-2' : ''">
                      <label class="block text-xs font-medium text-white/50 mb-1.5">
                        {{ field.label }}<span v-if="field.required" class="text-red-400 ml-0.5">*</span>
                      </label>
                      <textarea v-if="field.type === 'textarea'"
                        v-model="kdeValues[field.key]" rows="2"
                        :placeholder="field.placeholder || ''" :class="inputCls" class="resize-none" />
                      <select v-else-if="field.type === 'select'"
                        v-model="kdeValues[field.key]" :class="inputCls">
                        <option value="">— Chọn —</option>
                        <option v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</option>
                      </select>
                      <input v-else-if="field.type === 'date'"
                        v-model="kdeValues[field.key]" type="date" :class="inputCls" />
                      <input v-else-if="field.type === 'number'"
                        v-model="kdeValues[field.key]" type="number" step="any"
                        :placeholder="field.placeholder || ''" :class="inputCls" />
                      <input v-else
                        v-model="kdeValues[field.key]" type="text"
                        :placeholder="field.placeholder || ''" :class="inputCls" />
                    </div>

                  </template>
                </template>
              </div>

              <!-- GPS row -->
              <div v-if="selectedCte.kde_schema.some(f => f.key === 'where_lat')">
                <label class="block text-xs font-medium text-white/50 mb-1.5">Tọa độ GPS</label>
                <div class="flex gap-2 flex-wrap">
                  <input v-model="kdeValues['where_lat']" type="number" step="any"
                    placeholder="Latitude" :class="inputCls" class="flex-1 min-w-24" />
                  <input v-model="kdeValues['where_lng']" type="number" step="any"
                    placeholder="Longitude" :class="inputCls" class="flex-1 min-w-24" />
                  <button type="button" @click="getGps" :disabled="gpsLoading"
                    class="px-3 rounded-xl border border-white/10 text-white/40 text-xs hover:bg-white/5 hover:text-white/70 transition disabled:opacity-40 whitespace-nowrap">
                    {{ gpsLoading ? 'Đang lấy...' : 'Vị trí hiện tại' }}
                  </button>
                </div>
                <div v-if="gpsError" class="text-xs text-red-400 mt-1">{{ gpsError }}</div>
              </div>
            </template>

            <!-- Note -->
            <div>
              <label class="block text-xs font-medium text-white/50 mb-1.5">Ghi chú thêm (tuỳ chọn)</label>
              <textarea v-model="form.note" rows="2" :class="inputCls" class="resize-none"
                placeholder="Thông tin bổ sung..." />
            </div>
            <!-- Đính kèm file minh chứng (tuỳ chọn) -->
            <div>
              <label class="block text-xs font-medium text-white/50 mb-1.5">
                Đính kèm file minh chứng
                <span class="text-white/25 ml-1">(tuỳ chọn — jpg, png, pdf, tối đa 10MB)</span>
              </label>

              <!-- Preview files đã chọn -->
              <div v-if="pendingFiles.length" class="flex flex-wrap gap-2 mb-2">
                <div v-for="(f, i) in pendingFiles" :key="i"
                  class="flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg border border-brand-500/30 bg-brand-500/10 text-brand-300">
                  <span>📎 {{ f.name }}</span>
                  <button type="button" @click="pendingFiles.splice(i, 1)"
                    class="text-white/40 hover:text-red-400 transition leading-none">✕</button>
                </div>
              </div>

              <label
                class="flex items-center gap-2 px-3 py-2 rounded-xl border border-dashed border-white/20 hover:border-brand-500/40 bg-white/3 hover:bg-brand-500/5 cursor-pointer transition text-sm text-white/40 hover:text-white/60">
                <span>+ Chọn file</span>
                <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf,.webp" class="hidden"
                  @change="onFileSelect" />
              </label>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-1">
              <button @click="submitForm" :disabled="form.processing"
                class="flex-1 py-2.5 bg-brand-500 hover:bg-brand-400 text-black font-bold rounded-xl text-sm disabled:opacity-50 transition">
                {{ form.processing ? 'Đang lưu...' : 'Lưu sự kiện' }}
              </button>
              <button @click="showAddForm = false; selectedCte = null"
                class="px-5 py-2.5 border border-glass hover:bg-white/5 text-white/50 rounded-xl text-sm transition">
                Huỷ
              </button>
            </div>
          </div>
        </div>

        <!-- Lịch sử sự kiện -->
        <div class="rounded-2xl border border-glass bg-black/20 overflow-hidden">
          <div class="px-5 py-4 border-b border-glass flex items-center justify-between">
            <div class="text-sm font-semibold text-white/70">Lịch sử sự kiện</div>
            <div class="text-xs text-white/40">{{ events?.total ?? 0 }} sự kiện</div>
          </div>

          <div v-if="!events?.data?.length" class="flex flex-col items-center justify-center py-16 text-white/30">
            <div class="text-4xl mb-3">📋</div>
            <div class="text-sm">Chưa có sự kiện nào. Chọn bước ở cột trái để thêm.</div>
          </div>

          <div v-else class="divide-y divide-white/5">
            <div v-for="ev in events.data" :key="ev.id" class="p-5 space-y-3">

              <!-- Header event -->
              <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-semibold text-white/90">{{ getCteName(ev) }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full border"
                      :class="ev.status === 'published'
                        ? 'border-green-500/40 bg-green-500/10 text-green-400'
                        : 'border-white/10 bg-white/5 text-white/40'">
                      {{ ev.status === 'published' ? 'Published' : 'Draft' }}
                    </span>
                  </div>
                  <div class="text-xs text-white/40 mt-0.5">{{ formatTime(ev.event_time) }}</div>
                </div>
                <!-- Actions -->
                <div v-if="ev.status !== 'published'" class="flex gap-1.5">
                  <button @click="openEdit(ev)"
                    class="text-xs px-2.5 py-1 rounded-lg border border-glass hover:bg-white/5 text-white/50 transition">Sửa</button>
                  <button @click="publishEvent(ev)"
                    class="text-xs px-2.5 py-1 rounded-lg border border-brand-500/40 bg-brand-500/10 hover:bg-brand-500/20 text-brand-300 transition">Publish</button>
                  <button @click="deleteEvent(ev)"
                    class="text-xs px-2.5 py-1 rounded-lg border border-red-500/30 bg-red-500/5 hover:bg-red-500/10 text-red-400 transition">Xoá</button>
                </div>
              </div>

              <!-- 5W summary -->
              <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-xs">
                <div v-if="ev.who_name" class="flex gap-1.5">
                  <span class="text-white/30 shrink-0">WHO</span>
                  <span class="text-white/70">{{ ev.who_name }}</span>
                </div>
                <div v-if="ev.where_address" class="flex gap-1.5">
                  <span class="text-white/30 shrink-0">WHERE</span>
                  <span class="text-white/70">{{ ev.where_address }}</span>
                </div>
                <div v-if="ev.why_reason" class="flex gap-1.5 col-span-2">
                  <span class="text-white/30 shrink-0">WHY</span>
                  <span class="text-white/70">{{ ev.why_reason }}</span>
                </div>
              </div>

              <!-- IPFS info -->
              <div v-if="ev.ipfs_cid" class="flex flex-wrap items-center gap-3 text-xs">
                <a :href="ev.ipfs_url" target="_blank"
                  class="flex items-center gap-1 text-brand-400 hover:text-brand-300 transition">
                  <span>IPFS</span>
                  <span class="font-mono">{{ shortCid(ev.ipfs_cid) }}</span>
                </a>
                <span v-if="ev.tx_hash" class="text-white/30 font-mono">TX: {{ shortCid(ev.tx_hash) }}</span>
              </div>

              <!-- Attachments -->
              <div v-if="ev.attachments?.length" class="flex flex-wrap gap-2">
                <a v-for="att in ev.attachments" :key="att.cid" :href="att.url" target="_blank"
                  class="text-xs px-2 py-1 rounded-lg border border-white/10 bg-white/5 text-white/50 hover:text-white/80 transition flex items-center gap-1">
                  📎 {{ att.name }}
                </a>
              </div>

              <!-- Upload attachment (draft only) -->
              <div v-if="ev.status !== 'published'" class="flex items-center gap-2">
                <label class="text-xs text-white/30 cursor-pointer hover:text-white/50 transition flex items-center gap-1">
                  <span>+ Đính kèm file</span>
                  <input type="file" class="hidden" accept=".jpg,.jpeg,.png,.pdf,.webp"
                    @change="uploadFile(ev, $event.target.files[0])" />
                </label>
                <div v-if="uploadingEvent === ev.id" class="text-xs text-brand-300">
                  {{ uploadProgress }}%
                </div>
              </div>

            </div>
          </div>

          <!-- Pagination -->
          <div v-if="events?.last_page > 1"
            class="px-5 py-3 border-t border-glass flex items-center justify-between text-xs text-white/40">
            <span>{{ events.from }}–{{ events.to }} / {{ events.total }}</span>
            <div class="flex gap-2">
              <button @click="prevEvents" :disabled="!events.prev_page_url"
                class="px-3 py-1 rounded-lg border border-glass hover:bg-white/5 disabled:opacity-30 transition">Trước</button>
              <button @click="nextEvents" :disabled="!events.next_page_url"
                class="px-3 py-1 rounded-lg border border-glass hover:bg-white/5 disabled:opacity-30 transition">Sau</button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Chưa chọn lô -->
    <div v-else class="flex flex-col items-center justify-center py-20 text-white/30">
      <div class="text-5xl mb-4">📦</div>
      <div class="text-base">Chọn một lô hàng để bắt đầu ghi sự kiện</div>
    </div>

  </div>

  <!-- Modal Edit -->
  <div v-if="editingEvent" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-cosmic-900 border border-glass rounded-2xl w-full max-w-xl max-h-[85vh] overflow-y-auto">
      <div class="sticky top-0 px-5 py-4 border-b border-glass bg-cosmic-900 flex items-center justify-between">
        <div class="font-bold text-white/90">Sửa: {{ getCteName(editingEvent) }}</div>
        <button @click="editingEvent = null"
          class="w-8 h-8 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/40 text-lg">✕</button>
      </div>
      <div class="p-5 space-y-4">
        <div>
          <label class="text-xs text-white/50 mb-1 block">Thời gian</label>
          <input v-model="editForm.event_time" type="datetime-local" :class="inputCls" />
        </div>
        <div v-for="(val, key) in editKdeValues" :key="key">
          <label class="text-xs text-white/40 mb-1 block capitalize">{{ key.replace(/_/g, ' ') }}</label>
          <textarea v-model="editKdeValues[key]" rows="2" :class="inputCls" class="resize-y" />
        </div>
        <div>
          <label class="text-xs text-white/50 mb-1 block">Ghi chú</label>
          <textarea v-model="editForm.note" rows="2" :class="inputCls" class="resize-none" />
        </div>
      </div>
      <div class="sticky bottom-0 px-5 py-4 border-t border-glass bg-cosmic-900 flex gap-2">
        <button @click="submitEdit" :disabled="editForm.processing"
          class="flex-1 py-2 bg-brand-500 hover:bg-brand-400 text-black font-bold rounded-xl text-sm disabled:opacity-50 transition">
          Lưu thay đổi
        </button>
        <button @click="editingEvent = null"
          class="px-5 py-2 border border-glass hover:bg-white/5 text-white/50 rounded-xl text-sm transition">
          Huỷ
        </button>
      </div>
    </div>
  </div>

</template>