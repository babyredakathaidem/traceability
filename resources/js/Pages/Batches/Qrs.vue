<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref, watch, nextTick } from 'vue'
import VietmapPlacePicker from '@/Components/VietmapPlacePicker.vue'
import QRCode from 'qrcode'

const props = defineProps({
  batch:          Object,
  qrs:            Array,
  publicUrlBase:  String,
  privateUrlBase: String,
})

const publicQr  = computed(() => props.qrs?.find(q => q.type === 'public'))
const privateQr = computed(() => props.qrs?.find(q => q.type === 'private'))

const publicLink  = computed(() => {
  if (!publicQr.value) return ''
  return publicQr.value.gs1_digital_link || `${props.publicUrlBase}/${publicQr.value.token}`
})
const privateLink = computed(() => {
  if (!privateQr.value) return ''
  return privateQr.value.gs1_digital_link || `${props.privateUrlBase}/${privateQr.value.token}`
})

// ── Vietmap picker ────────────────────────────────────────
const place = ref({
  place_name: publicQr.value?.place_name  || '',
  lat:        publicQr.value?.allowed_lat || '',
  lng:        publicQr.value?.allowed_lng || '',
  refid:      '',
})

// ── Forms ─────────────────────────────────────────────────
const formEnsure = useForm({})
const formPublic = useForm({
  place_name:       place.value.place_name,
  allowed_lat:      place.value.lat,
  allowed_lng:      place.value.lng,
  allowed_radius_m: publicQr.value?.allowed_radius_m ?? 50,
})

// Đã lưu cấu hình chưa (dùng để ẩn/hiện form)
const configSaved = ref(
  !!(publicQr.value?.place_name && publicQr.value?.allowed_lat && publicQr.value?.allowed_radius_m)
)

function ensureQrs() {
  formEnsure.post(route('batches.qrs.ensure', props.batch.id))
}

function syncFromPicker(val) {
  place.value            = val
  formPublic.place_name  = val.place_name
  formPublic.allowed_lat = val.lat
  formPublic.allowed_lng = val.lng
}

function savePublic() {
  if (!publicQr.value) return
  formPublic.post(route('qrcodes.configurePublic', publicQr.value.id), {
    onSuccess: () => {
      configSaved.value    = true
      showMapPreview.value = false
      destroyMap('map-preview')
      // Render map xác nhận sau khi lưu
      nextTick(() => renderMap(
        formPublic.allowed_lat,
        formPublic.allowed_lng,
        formPublic.allowed_radius_m,
        'map-saved'
      ))
    },
  })
}

function editConfig() {
  configSaved.value = false
  nextTick(() => destroyMap('map-saved'))
}

// ── QR PNG — live update ──────────────────────────────────
const publicQrPng  = ref('')
const privateQrPng = ref('')

async function genQrPng(text) {
  if (!text) return ''
  return QRCode.toDataURL(text, {
    errorCorrectionLevel: 'M',
    width: 800,
    margin: 2,
    color: { dark: '#000000', light: '#ffffff' },
  })
}

async function refreshQrImages() {
  publicQrPng.value  = await genQrPng(publicLink.value)
  privateQrPng.value = await genQrPng(privateLink.value)
}

onMounted(refreshQrImages)
watch([publicLink, privateLink], refreshQrImages)

function download(dataUrl, filename) {
  if (!dataUrl) return
  const a = document.createElement('a')
  a.href = dataUrl; a.download = filename
  document.body.appendChild(a); a.click(); a.remove()
}

// ── Copy link ─────────────────────────────────────────────
const copied = ref(null)
async function copyLink(text, key) {
  await navigator.clipboard.writeText(text)
  copied.value = key
  setTimeout(() => { copied.value = null }, 2000)
}

// ── Leaflet map ───────────────────────────────────────────
const maps = {}

function loadLeaflet() {
  return new Promise((resolve) => {
    if (window.L) { resolve(); return }
    const css = document.createElement('link')
    css.rel = 'stylesheet'
    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
    document.head.appendChild(css)
    const script = document.createElement('script')
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
    script.onload = resolve
    document.head.appendChild(script)
  })
}

async function renderMap(lat, lng, radiusM, mapId) {
  if (!lat || !lng || !radiusM) return
  await loadLeaflet()
  await nextTick()
  const el = document.getElementById(mapId)
  if (!el) return
  if (maps[mapId]) { maps[mapId].remove(); delete maps[mapId] }
  const L   = window.L
  const map = L.map(el, { zoomControl: true, scrollWheelZoom: false })
  maps[mapId] = map
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 19,
  }).addTo(map)
  const center = [parseFloat(lat), parseFloat(lng)]
  const r = parseInt(radiusM)
  L.marker(center).addTo(map)
  L.circle(center, {
    radius: r, color: '#f97316', fillColor: '#f97316',
    fillOpacity: 0.15, weight: 2,
  }).addTo(map)
  map.setView(center, r < 100 ? 18 : r < 500 ? 16 : 14)
}

function destroyMap(mapId) {
  if (maps[mapId]) { maps[mapId].remove(); delete maps[mapId] }
}

// Hiện map preview tự động khi đủ lat + lng + radius
const showMapPreview = ref(false)

watch(
  () => [formPublic.allowed_radius_m, formPublic.allowed_lat, formPublic.allowed_lng],
  ([r, lat, lng]) => {
    if (r && lat && lng) {
      showMapPreview.value = true
      nextTick(() => renderMap(lat, lng, r, 'map-preview'))
    } else {
      showMapPreview.value = false
      destroyMap('map-preview')
    }
  }
)

// Render map saved khi mount nếu đã có config
onMounted(() => {
  if (configSaved.value && publicQr.value?.allowed_lat) {
    nextTick(() => renderMap(
      publicQr.value.allowed_lat,
      publicQr.value.allowed_lng,
      publicQr.value.allowed_radius_m,
      'map-saved'
    ))
  }
})

onUnmounted(() => {
  Object.keys(maps).forEach(id => { maps[id]?.remove(); delete maps[id] })
})

function confirmMapPreview() {
  showMapPreview.value = false
  destroyMap('map-preview')
}

const inputCls = 'w-full bg-black/20 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none focus:border-brand-500 appearance-none'
</script>

<template>
  <Head :title="`QR — Lô ${batch?.code}`" />

  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/30 p-5">
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <div class="text-xs text-white/40 uppercase tracking-wider">QR Code</div>
          <h1 class="text-xl font-extrabold text-white/90 mt-1">
            Lô {{ batch?.code }}
          </h1>
          <p class="text-white/50 text-sm mt-0.5">{{ batch?.product_name }}</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <Link :href="route('batches.index')"
            class="px-3 py-1.5 text-sm border border-glass rounded-xl text-white/60 hover:bg-white/5 transition">
            Quay lai
          </Link>
          <button
            :disabled="formEnsure.processing"
            @click="ensureQrs"
            class="px-4 py-1.5 text-sm rounded-xl font-semibold transition disabled:opacity-50"
            :class="(publicQr && privateQr)
              ? 'border border-glass text-white/50 hover:bg-white/5'
              : 'bg-brand-500 text-cosmic-950 hover:bg-brand-600'">
            {{ (publicQr && privateQr) ? 'Tao lai QR' : 'Tao 2 QR' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="!publicQr && !privateQr"
      class="rounded-2xl border border-glass bg-black/20 p-8 text-center text-white/40 text-sm">
      Chưa có QR nào. Nhấn "Tạo 2 QR" để tạo QR public và private cho lô hàng này.
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      <!-- PUBLIC QR -->
      <div class="rounded-2xl border border-glass bg-black/30 p-5 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-xs text-white/40 uppercase tracking-wider">QR Public</div>
            <p class="text-sm text-white/60 mt-0.5">Đặt tại quầy trung bay / điểm bán</p>
          </div>
          <span class="text-xs px-2 py-0.5 rounded-full border border-brand-500/40 bg-brand-500/10 text-brand-300">
            PUBLIC
          </span>
        </div>

        <div v-if="!publicQr" class="text-sm text-white/40 py-4 text-center">
          Chua co QR public.
        </div>
        <template v-else>
          <!-- QR image -->
          <div class="flex gap-4 items-start">
            <div class="shrink-0">
              <div class="w-40 h-40 rounded-xl overflow-hidden bg-white flex items-center justify-center">
                <img v-if="publicQrPng" :src="publicQrPng" alt="QR Public" class="w-full h-full" />
                <div v-else class="text-xs text-gray-400">...</div>
              </div>
              <button
                :disabled="!publicQrPng"
                @click="download(publicQrPng, `QR_PUBLIC_${batch.code}.png`)"
                class="mt-2 w-40 py-1.5 text-xs border border-glass rounded-xl text-white/60 hover:bg-white/5 transition disabled:opacity-40">
                Tai PNG
              </button>
            </div>

            <div class="flex-1 space-y-2 min-w-0">
              <div class="bg-white/5 rounded-xl p-2">
                <p class="text-xs text-white/40 mb-1">Link QR</p>
                <p class="font-mono text-xs text-brand-300 break-all">{{ publicLink }}</p>
              </div>
              <button @click="copyLink(publicLink, 'public')"
                class="w-full py-1.5 text-xs border border-glass rounded-xl text-white/60 hover:bg-white/5 transition">
                {{ copied === 'public' ? 'Da sao chep!' : 'Sao chep link' }}
              </button>
              <div v-if="publicQr.place_name" class="bg-white/5 rounded-xl p-2 text-xs">
                <p class="text-white/40">Vị trí: {{ publicQr.place_name }}</p>
                <p class="text-white/40">Bán Kính: {{ publicQr.allowed_radius_m }}m</p>
              </div>
              <div v-else class="bg-orange-500/10 border border-orange-500/30 rounded-xl p-2 text-xs text-orange-400">
                Chưa cấu hình vị trí phát hành
              </div>
            </div>
          </div>

          <!-- Config form -->
          <!-- Map sau khi lưu xong -->
          <div v-if="configSaved && publicQr.allowed_lat" class="space-y-2">
            <div id="map-saved" class="w-full h-48 rounded-xl overflow-hidden border border-green-500/20"></div>
            <p class="text-xs text-white/30 text-center">
              Vùng hợp lệ quét QR — bán kính {{ publicQr.allowed_radius_m }}m
            </p>
            <button @click="editConfig"
              class="w-full py-1.5 text-xs border border-glass rounded-xl text-white/50 hover:bg-white/5 transition">
              Chỉnh sửa cấu hình
            </button>
          </div>

          <!-- Config form — ẩn khi đã lưu -->
          <div v-if="!configSaved" class="border-t border-white/5 pt-4 space-y-3">
            <p class="text-xs text-white/40 uppercase tracking-wider">Cấu hình vị trí phát hành</p>
            <VietmapPlacePicker :modelValue="place" @update:modelValue="syncFromPicker" />
            <div>
              <label class="text-xs text-white/50 block mb-1">Bán kính hợp lệ (mét)</label>
              <input type="number" v-model="formPublic.allowed_radius_m"
                :class="inputCls" min="1" max="5000" placeholder="VD: 100" />
            </div>

            <!-- Map preview tự động khi đủ dữ liệu -->
            <div v-if="showMapPreview"
              class="rounded-xl border border-brand-500/30 bg-brand-500/5 p-3 space-y-2">
              <p class="text-xs text-brand-400 font-semibold">Xem trước vùng hợp lệ</p>
              <div id="map-preview" class="w-full h-48 rounded-xl overflow-hidden"></div>
              <button @click="confirmMapPreview"
                class="w-full py-2 text-sm rounded-xl bg-white/10 border border-glass text-white/80 hover:bg-white/15 transition font-semibold">
                OK — Xác nhận vị trí
              </button>
            </div>

            <button :disabled="formPublic.processing" @click="savePublic"
              class="w-full py-2 text-sm rounded-xl bg-brand-500 text-cosmic-950 font-semibold hover:bg-brand-600 transition disabled:opacity-50">
              {{ formPublic.processing ? 'Đang lưu...' : 'Lưu cấu hình' }}
            </button>
            <p class="text-xs text-white/30">
              QR public chỉ hợp lệ khi người quét đứng trong bán kính quanh tọa độ phát hành.
            </p>
          </div>
        </template>
      </div>

      <!-- PRIVATE QR -->
      <div class="rounded-2xl border border-glass bg-black/30 p-5 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-xs text-white/40 uppercase tracking-wider">QR Private</div>
            <p class="text-sm text-white/60 mt-0.5">In tren bao bi / nap san pham</p>
          </div>
          <span class="text-xs px-2 py-0.5 rounded-full border border-white/20 bg-white/5 text-white/50">
            PRIVATE
          </span>
        </div>

        <div v-if="!privateQr" class="text-sm text-white/40 py-4 text-center">
          Chua co QR private.
        </div>
        <template v-else>
          <!-- QR image -->
          <div class="flex gap-4 items-start">
            <div class="shrink-0">
              <div class="w-40 h-40 rounded-xl overflow-hidden bg-white flex items-center justify-center">
                <img v-if="privateQrPng" :src="privateQrPng" alt="QR Private" class="w-full h-full" />
                <div v-else class="text-xs text-gray-400">...</div>
              </div>
              <button
                :disabled="!privateQrPng"
                @click="download(privateQrPng, `QR_PRIVATE_${batch.code}.png`)"
                class="mt-2 w-40 py-1.5 text-xs border border-glass rounded-xl text-white/60 hover:bg-white/5 transition disabled:opacity-40">
                Tai PNG
              </button>
            </div>

            <div class="flex-1 space-y-2 min-w-0">
              <div class="bg-white/5 rounded-xl p-2">
                <p class="text-xs text-white/40 mb-1">Link QR</p>
                <p class="font-mono text-xs text-white/60 break-all">{{ privateLink }}</p>
              </div>
              <button @click="copyLink(privateLink, 'private')"
                class="w-full py-1.5 text-xs border border-glass rounded-xl text-white/60 hover:bg-white/5 transition">
                {{ copied === 'private' ? 'Da sao chep!' : 'Sao chep link' }}
              </button>

              <div class="bg-white/5 rounded-xl p-2 text-xs space-y-1">
                <p class="text-white/50">Khong kiem tra vi tri</p>
                <p class="text-white/50">Het hieu luc sau <span class="text-white/80 font-semibold">48 gio</span> ke tu lan quet dau</p>
              </div>

              <div v-if="privateQr.first_scanned_at" class="bg-orange-500/10 border border-orange-500/30 rounded-xl p-2 text-xs text-orange-300 space-y-0.5">
                <p>Lan quet dau: {{ privateQr.first_scanned_at }}</p>
                <p>Het han: {{ privateQr.expires_at }}</p>
              </div>
              <div v-else class="bg-white/5 rounded-xl p-2 text-xs text-white/30">
                Chua duoc quet lan nao
              </div>
            </div>
          </div>

          <p class="text-xs text-white/30 border-t border-white/5 pt-3">
            He thong ghi log thoi gian, thiet bi va GPS (neu co) moi lan quet.
          </p>
        </template>
      </div>
    </div>

  </div>
</template>