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

// ── Batch filter ──────────────────────────────────────────
const batchId = ref(props.filters?.batch_id ?? '')
watch(batchId, (val) => {
  showAddForm.value = false
  selectedCte.value = null
  router.get(route('events.index'), { batch_id: val || undefined }, { preserveState: true, replace: true })
})
const selectedBatch = computed(() => props.batches.find(b => b.id == batchId.value))

// ── CTE Templates ─────────────────────────────────────────
const cteTemplates = ref([])
const loadingCte   = ref(false)

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
  if (selectedCte.value) {
    for (const f of selectedCte.value.kde_schema) {
      kde[f.key] = kdeValues.value[f.key] ?? null
    }
  }
  return kde
}

function extractIndexFields() {
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
      form.event_time = new Date().toISOString().slice(0, 16)
    },
  })
}

// ── GPS ───────────────────────────────────────────────────
const currentGps = ref(null)
const gpsLoading = ref(false)
const gpsError   = ref('')

function getGps() {
  if (!navigator.geolocation) { gpsError.value = 'Trinh duyet khong ho tro GPS.'; return }
  gpsLoading.value = true
  gpsError.value   = ''
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      currentGps.value             = { lat: pos.coords.latitude, lng: pos.coords.longitude }
      kdeValues.value['where_lat'] = pos.coords.latitude
      kdeValues.value['where_lng'] = pos.coords.longitude
      gpsLoading.value             = false
    },
    (err) => { gpsError.value = 'Khong lay duoc GPS: ' + err.message; gpsLoading.value = false },
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
    alert('Upload that bai: ' + (e.response?.data?.error || e.message))
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
  if (!confirm('Xoa su kien "' + getCteName(ev) + '"?')) return
  router.delete(route('events.destroy', ev.id))
}

function publishEvent(ev) {
  if (ev.status === 'published') return
  if (!confirm('Publish len IPFS se KHOA VINH VIEN su kien nay. Tiep tuc?')) return
  router.post(route('events.publish', ev.id))
}

// ── Helpers ───────────────────────────────────────────────
const wBadge = {
  WHO:   { bg: 'bg-blue-500/15 text-blue-300 border-blue-500/30',   label: 'WHO' },
  WHAT:  { bg: 'bg-orange-500/15 text-orange-300 border-orange-500/30', label: 'WHAT' },
  WHERE: { bg: 'bg-green-500/15 text-green-300 border-green-500/30', label: 'WHERE' },
  WHEN:  { bg: 'bg-purple-500/15 text-purple-300 border-purple-500/30', label: 'WHEN' },
  WHY:   { bg: 'bg-rose-500/15 text-rose-300 border-rose-500/30',   label: 'WHY' },
}

function getCteName(ev) {
  const tpl = cteTemplates.value.find(t => t.code === ev.cte_code)
  return tpl?.name_vi || ev.event_type || 'Su kien'
}

function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('vi-VN', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' })
}

function shortCid(cid) {
  if (!cid) return ''
  return cid.length > 16 ? cid.slice(0, 8) + '...' + cid.slice(-6) : cid
}

// Group KDE fields by 5W
function groupByW(schema) {
  const groups = {}
  for (const f of schema) {
    const w = f.w || 'OTHER'
    if (!groups[w]) groups[w] = []
    groups[w].push(f)
  }
  return groups
}

const inputCls = 'w-full bg-black/20 border border-glass rounded-xl px-3 py-2.5 text-sm text-white/90 placeholder:text-white/30 outline-none focus:border-brand-500/60'
</script>

<template>
  <Head title="Su kien truy xuat" />

  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6 flex flex-wrap items-start justify-between gap-4">
      <div>
        <div class="text-brand-300 text-sm font-semibold">Truy xuat nguon goc</div>
        <h1 class="text-2xl font-bold mt-1 text-white/90">Ghi nhan su kien</h1>
        <p class="text-white/40 text-sm mt-1">Chon lo hang va ghi du lieu 5W theo TCVN 12850:2019.</p>
      </div>
      <div v-if="flash.success"
        class="px-4 py-2 rounded-xl border border-green-500/30 bg-green-500/10 text-sm text-green-400">
        {{ flash.success }}
      </div>
    </div>

    <!-- Chon lo -->
    <div class="rounded-2xl border border-glass bg-black/20 p-4 flex flex-wrap items-center gap-4">
      <label class="text-sm text-white/50 whitespace-nowrap">Lo hang:</label>
      <select v-model="batchId"
        class="flex-1 min-w-48 bg-black/30 border border-glass rounded-xl px-4 py-2.5 text-sm text-white/90 outline-none focus:border-brand-500/60">
        <option value="">-- Chon lo hang --</option>
        <option v-for="b in batches" :key="b.id" :value="b.id">
          {{ b.code }} — {{ b.product_name }}
          {{ b.status === 'recalled' ? '[THU HOI]' : '' }}
        </option>
      </select>

      <div v-if="selectedBatch" class="flex items-center gap-2">
        <span class="text-xs px-2 py-1 rounded-full border"
          :class="selectedBatch.status === 'recalled'
            ? 'border-red-500/30 bg-red-500/10 text-red-400'
            : selectedBatch.status === 'active'
              ? 'border-green-500/30 bg-green-500/10 text-green-400'
              : 'border-white/10 bg-white/5 text-white/40'">
          {{ selectedBatch.status === 'recalled' ? 'Da thu hoi' : selectedBatch.status === 'active' ? 'Hoat dong' : selectedBatch.status }}
        </span>
      </div>
    </div>

    <!-- Layout 2 col khi da chon lo -->
    <div v-if="selectedBatch" class="grid grid-cols-1 xl:grid-cols-3 gap-6">

      <!-- COT TRAI: CTE Stepper -->
      <div class="xl:col-span-1 space-y-4">

        <!-- Progress tong the -->
        <div class="rounded-2xl border border-glass bg-black/20 p-5">
          <div class="flex items-center justify-between mb-3">
            <div class="text-sm font-semibold text-white/70">Tien do chuan TCVN</div>
            <div class="text-sm font-bold"
              :class="completeness === 100 ? 'text-green-400' : 'text-brand-400'">
              {{ completeness }}%
            </div>
          </div>
          <div class="h-2 bg-white/10 rounded-full overflow-hidden">
            <div class="h-2 rounded-full transition-all duration-500"
              :class="completeness === 100 ? 'bg-green-500' : 'bg-brand-500'"
              :style="{ width: completeness + '%' }" />
          </div>
          <div class="text-xs text-white/40 mt-2">
            {{ requiredDone }}/{{ requiredTotal }} buoc bat buoc da hoan thanh
          </div>
        </div>

        <!-- Danh sach CTE steps -->
        <div class="rounded-2xl border border-glass bg-black/20 overflow-hidden">
          <div class="px-5 py-3 border-b border-glass text-xs text-white/40 uppercase tracking-widest">
            Cac buoc truy xuat
          </div>

          <div v-if="loadingCte" class="p-5 text-center text-white/30 text-sm">Dang tai...</div>

          <div v-else class="divide-y divide-white/5">
            <button
              v-for="tpl in cteTemplates" :key="tpl.code"
              @click="!tpl.is_done && selectCte(tpl)"
              class="w-full flex items-center gap-3 px-5 py-3.5 text-left transition"
              :class="tpl.is_done
                ? 'opacity-60 cursor-default'
                : selectedCte?.code === tpl.code
                  ? 'bg-brand-500/10 border-l-2 border-brand-500'
                  : 'hover:bg-white/5 cursor-pointer'"
            >
              <!-- Step indicator -->
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
                <div class="text-xs mt-0.5" :class="tpl.is_done ? 'text-green-500/60' : tpl.is_required ? 'text-brand-400/70' : 'text-white/30'">
                  {{ tpl.is_done ? 'Da hoan thanh' : tpl.is_required ? 'Bat buoc' : 'Tuy chon' }}
                </div>
              </div>

              <span v-if="!tpl.is_done" class="text-white/20 text-xs">›</span>
            </button>

            <!-- Custom event -->
            <button
              @click="selectCte({ code: 'custom', name_vi: 'Su kien tuy chinh', step_order: '—', kde_schema: [
                { key: 'who_performer', label: 'Nguoi thuc hien', w: 'WHO', type: 'text', required: true },
                { key: 'what_activity', label: 'Noi dung hoat dong', w: 'WHAT', type: 'textarea', required: true },
                { key: 'where_address', label: 'Dia diem', w: 'WHERE', type: 'text', required: false },
                { key: 'why_note', label: 'Ghi chu / Ly do', w: 'WHY', type: 'textarea', required: false },
              ], is_required: false, is_done: false })"
              class="w-full flex items-center gap-3 px-5 py-3.5 text-left hover:bg-white/5 transition"
              :class="selectedCte?.code === 'custom' ? 'bg-brand-500/10 border-l-2 border-brand-500' : ''"
            >
              <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs border border-dashed border-white/20 text-white/30 shrink-0">+</div>
              <div>
                <div class="text-sm text-white/50">Su kien tuy chinh</div>
                <div class="text-xs text-white/20">Ghi bat ky hoat dong nao</div>
              </div>
            </button>
          </div>
        </div>

      </div>

      <!-- COT PHAI: Form + Timeline -->
      <div class="xl:col-span-2 space-y-6">

        <!-- Form ghi su kien -->
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

            <!-- KDE fields phẳng -->
            <template v-if="selectedCte.kde_schema?.length">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <template v-for="field in selectedCte.kde_schema" :key="field.key">
                  <template v-if="field.key !== 'where_lat' && field.key !== 'where_lng'">
                    <div :class="field.type === 'textarea' ? 'md:col-span-2' : ''">
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
                      <input v-else v-model="kdeValues[field.key]" type="text"
                        :placeholder="field.placeholder || ''" :class="inputCls" />
                    </div>
                  </template>
                </template>
              </div>

              <!-- GPS row -->
              <div v-if="selectedCte.kde_schema.some(f => f.key === 'where_lat')">
                <label class="block text-xs font-medium text-white/50 mb-1.5">Tọa độ GPS</label>
                <div class="flex gap-2">
                  <input v-model="kdeValues['where_lat']" type="number" step="any"
                    placeholder="Latitude" :class="inputCls" class="flex-1" />
                  <input v-model="kdeValues['where_lng']" type="number" step="any"
                    placeholder="Longitude" :class="inputCls" class="flex-1" />
                  <button type="button" @click="getGps" :disabled="gpsLoading"
                    class="px-3 rounded-xl border border-white/10 text-white/40 text-xs hover:bg-white/5 hover:text-white/70 transition disabled:opacity-40 whitespace-nowrap">
                    {{ gpsLoading ? '...' : 'Lấy GPS' }}
                  </button>
                </div>
                <div v-if="gpsError" class="text-xs text-red-400 mt-1">{{ gpsError }}</div>
                <div v-if="currentGps" class="text-xs text-white/25 mt-1">
                  {{ currentGps.lat.toFixed(5) }}, {{ currentGps.lng.toFixed(5) }}
                </div>
              </div>
            </template>

            <!-- Ghi chú -->
            <div>
              <label class="block text-xs font-medium text-white/50 mb-1.5">Ghi chú (tùy chọn)</label>
              <textarea v-model="form.note" rows="2" placeholder="Ghi chú thêm..."
                :class="inputCls" class="resize-none" />
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-1">
              <button @click="submitForm" :disabled="form.processing"
                class="flex-1 py-2.5 rounded-xl bg-brand-500 text-cosmic-950 font-bold text-sm hover:bg-brand-600 transition disabled:opacity-50">
                {{ form.processing ? 'Đang lưu...' : 'Lưu sự kiện' }}
              </button>
              <button @click="showAddForm = false; selectedCte = null"
                class="px-5 py-2.5 rounded-xl border border-white/10 text-white/40 hover:bg-white/5 text-sm transition">
                Hủy
              </button>
            </div>
          </div>
        </div>

        <!-- Placeholder khi chua chon buoc -->
        <div v-else-if="!showAddForm"
          class="rounded-2xl border border-dashed border-white/10 bg-white/2 p-10 text-center">
          <div class="text-3xl mb-3">📋</div>
          <div class="text-white/40 text-sm">Chon mot buoc truy xuat o cot trai de bat dau ghi du lieu.</div>
        </div>

        <!-- Timeline su kien -->
        <div class="rounded-2xl border border-glass bg-black/20 overflow-hidden">
          <div class="px-5 py-4 border-b border-glass flex items-center justify-between">
            <div class="font-semibold text-white/80">Lich su su kien</div>
            <div class="text-xs text-white/30">
              {{ events.total ?? 0 }} su kien · Trang {{ events.current_page }}/{{ events.last_page }}
            </div>
          </div>

          <div v-if="!events.data.length" class="py-16 text-center">
            <div class="text-3xl mb-3">📭</div>
            <div class="text-white/30 text-sm">Chua co su kien nao. Chon buoc o cot trai de them su kien dau tien.</div>
          </div>

          <!-- Timeline list -->
          <div v-else class="divide-y divide-white/5">
            <div v-for="ev in events.data" :key="ev.id" class="p-5 hover:bg-white/3 transition">
              <div class="flex items-start gap-4">

                <!-- Timeline dot -->
                <div class="mt-1 shrink-0">
                  <div class="w-9 h-9 rounded-full flex items-center justify-center border-2"
                    :class="ev.status === 'published'
                      ? 'border-green-500/60 bg-green-500/15'
                      : 'border-orange-500/40 bg-orange-500/10'">
                    <span class="text-sm">{{ ev.status === 'published' ? '✓' : '○' }}</span>
                  </div>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="font-semibold text-white/90 text-sm">{{ getCteName(ev) }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full border font-medium"
                      :class="ev.status === 'published'
                        ? 'border-green-500/30 bg-green-500/10 text-green-400'
                        : 'border-orange-500/30 bg-orange-500/10 text-orange-400'">
                      {{ ev.status === 'published' ? 'Published' : 'Draft' }}
                    </span>
                  </div>

                  <div class="text-xs text-white/40 flex flex-wrap gap-x-4 gap-y-1 mb-2">
                    <span v-if="ev.event_time">🕐 {{ fmtDate(ev.event_time) }}</span>
                    <span v-if="ev.who_name">👤 {{ ev.who_name }}</span>
                    <span v-if="ev.where_address">📍 {{ ev.where_address }}</span>
                    <span v-if="ev.batch">📦 {{ ev.batch.code }}</span>
                  </div>

                  <!-- IPFS badge -->
                  <div v-if="ev.ipfs_cid" class="flex items-center gap-2 mb-2">
                    <span class="text-xs px-2 py-0.5 rounded bg-green-500/10 border border-green-500/20 text-green-400 font-mono">
                      IPFS: {{ shortCid(ev.ipfs_cid) }}
                    </span>
                    <a v-if="ev.ipfs_url" :href="ev.ipfs_url" target="_blank"
                      class="text-xs text-green-400/60 hover:text-green-400 underline">Xem</a>
                  </div>

                  <!-- Attachments -->
                  <div v-if="ev.attachments?.length" class="flex flex-wrap gap-1 mb-2">
                    <a v-for="att in ev.attachments" :key="att.cid"
                      :href="att.url" target="_blank"
                      class="text-xs px-2 py-0.5 rounded bg-white/5 border border-glass text-white/40 hover:text-white/70 transition">
                      📎 {{ att.name }}
                    </a>
                  </div>

                  <!-- Actions -->
                  <div class="flex flex-wrap gap-2 mt-3">
                    <button v-if="ev.status === 'draft'" @click="openEdit(ev)"
                      class="px-3 py-1.5 text-xs border border-glass hover:bg-white/5 text-white/50 rounded-lg transition">
                      ✏️ Sua
                    </button>
                    <button v-if="ev.status === 'draft'" @click="publishEvent(ev)"
                      class="px-3 py-1.5 text-xs border border-brand-500/40 bg-brand-500/10 text-brand-300 hover:bg-brand-500/20 rounded-lg transition font-semibold">
                      ↑ Publish IPFS
                    </button>
                    <button v-if="ev.status === 'draft'" @click="deleteEvent(ev)"
                      class="px-3 py-1.5 text-xs border border-red-500/20 hover:bg-red-500/10 text-red-400/60 hover:text-red-400 rounded-lg transition">
                      🗑 Xoa
                    </button>

                    <!-- Upload dinh kem (chi draft) -->
                    <label v-if="ev.status === 'draft'"
                      class="px-3 py-1.5 text-xs border border-glass hover:bg-white/5 text-white/50 rounded-lg transition cursor-pointer">
                      📎 Dinh kem
                      <input type="file" class="hidden" accept=".jpg,.jpeg,.png,.pdf,.webp"
                        @change="e => uploadFile(ev, e.target.files[0])" />
                    </label>
                  </div>

                  <!-- Upload progress -->
                  <div v-if="uploadingEvent === ev.id" class="mt-2">
                    <div class="h-1 bg-white/10 rounded-full overflow-hidden">
                      <div class="h-1 bg-brand-500 rounded-full transition-all" :style="{ width: uploadProgress + '%' }" />
                    </div>
                    <div class="text-xs text-white/30 mt-1">Dang upload... {{ uploadProgress }}%</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="events.last_page > 1" class="px-5 py-4 border-t border-glass flex items-center justify-between">
            <button
              :disabled="events.current_page <= 1"
              @click="router.get(route('events.index'), { page: events.current_page - 1, batch_id: batchId || undefined })"
              class="px-4 py-2 text-sm border border-glass rounded-xl text-white/50 hover:bg-white/5 transition disabled:opacity-30">
              Truoc
            </button>
            <span class="text-xs text-white/30">Trang {{ events.current_page }} / {{ events.last_page }}</span>
            <button
              :disabled="events.current_page >= events.last_page"
              @click="router.get(route('events.index'), { page: events.current_page + 1, batch_id: batchId || undefined })"
              class="px-4 py-2 text-sm border border-glass rounded-xl text-white/50 hover:bg-white/5 transition disabled:opacity-30">
              Sau
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- Chua chon lo -->
    <div v-else class="rounded-2xl border border-dashed border-white/10 p-16 text-center">
      <div class="text-4xl mb-4">📦</div>
      <div class="text-white/40">Chon mot lo hang o tren de xem va ghi su kien truy xuat.</div>
    </div>

  </div>

  <!-- Edit Modal -->
  <div v-if="editingEvent"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-sm p-4"
    @click.self="editingEvent = null">
    <div class="bg-cosmic-900 border border-glass rounded-2xl w-full max-w-xl max-h-[85vh] overflow-y-auto">
      <div class="sticky top-0 px-5 py-4 border-b border-glass bg-cosmic-900 flex items-center justify-between">
        <div class="font-bold text-white/90">Sua: {{ getCteName(editingEvent) }}</div>
        <button @click="editingEvent = null"
          class="w-8 h-8 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/40 text-lg">✕</button>
      </div>

      <div class="p-5 space-y-4">
        <div>
          <label class="text-xs text-white/50 mb-1 block">Thoi gian</label>
          <input v-model="editForm.event_time" type="datetime-local" :class="inputCls" />
        </div>
        <div v-for="(val, key) in editKdeValues" :key="key">
          <label class="text-xs text-white/40 mb-1 block capitalize">{{ key.replace(/_/g,' ') }}</label>
          <textarea v-model="editKdeValues[key]" rows="2" :class="inputCls" class="resize-y" />
        </div>
        <div>
          <label class="text-xs text-white/50 mb-1 block">Ghi chu</label>
          <textarea v-model="editForm.note" rows="2" :class="inputCls" class="resize-none" />
        </div>
      </div>

      <div class="sticky bottom-0 px-5 py-4 border-t border-glass bg-cosmic-900 flex gap-2">
        <button @click="submitEdit" :disabled="editForm.processing"
          class="flex-1 py-2 bg-brand-500 hover:bg-brand-400 text-black font-bold rounded-xl text-sm disabled:opacity-50 transition">
          Luu thay doi
        </button>
        <button @click="editingEvent = null"
          class="px-5 py-2 border border-glass hover:bg-white/5 text-white/50 rounded-xl text-sm transition">
          Huy
        </button>
      </div>
    </div>
  </div>

</template>