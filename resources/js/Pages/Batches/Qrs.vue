<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import { computed, onMounted, ref, watch } from 'vue'
import VietmapPlacePicker from '@/Components/VietmapPlacePicker.vue'
import QRCode from 'qrcode'

const props = defineProps({
  batch: Object,
  qrs: Array,
  publicUrlBase: String,
  privateUrlBase: String,
})

const publicQr  = computed(() => props.qrs?.find(q => q.type === 'public'))
const privateQr = computed(() => props.qrs?.find(q => q.type === 'private'))

const publicLink  = computed(() => publicQr.value  ? `${props.publicUrlBase}/${publicQr.value.token}`   : '')
const privateLink = computed(() => privateQr.value ? `${props.privateUrlBase}/${privateQr.value.token}` : '')

// Vietmap picker state
const place = ref({
  place_name: publicQr.value?.place_name || '',
  lat: publicQr.value?.allowed_lat || '',
  lng: publicQr.value?.allowed_lng || '',
  refid: '',
})

// Forms
const formEnsure = useForm({})
const formPublic = useForm({
  place_name:      place.value.place_name,
  allowed_lat:     place.value.lat,
  allowed_lng:     place.value.lng,
  allowed_radius_m: publicQr.value?.allowed_radius_m ?? 50,
})

function ensureQrs() {
  formEnsure.post(route('batches.qrs.ensure', props.batch.id))
}

function syncFromPicker(val) {
  place.value = val
  formPublic.place_name  = val.place_name
  formPublic.allowed_lat = val.lat
  formPublic.allowed_lng = val.lng
}

function savePublic() {
  if (!publicQr.value) return
  formPublic.post(route('qrcodes.configurePublic', publicQr.value.id))
}

// QR PNG generation
const publicQrPng  = ref('')
const privateQrPng = ref('')

async function genQrPng(text) {
  if (!text) return ''
  return await QRCode.toDataURL(text, {
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
  a.href = dataUrl
  a.download = filename
  document.body.appendChild(a)
  a.click()
  a.remove()
}

// Copy link to clipboard
const copied = ref(null)
async function copyLink(text, key) {
  await navigator.clipboard.writeText(text)
  copied.value = key
  setTimeout(() => { copied.value = null }, 2000)
}

const inputCls = 'w-full bg-black/20 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none focus:border-brand-500'
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
      Chua co QR nao. Bam "Tao 2 QR" de tao QR public va private cho lo nay.
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      <!-- PUBLIC QR -->
      <div class="rounded-2xl border border-glass bg-black/30 p-5 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-xs text-white/40 uppercase tracking-wider">QR Public</div>
            <p class="text-sm text-white/60 mt-0.5">Dat tai quay trung bay / diem ban</p>
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
                <p class="text-white/40">Vi tri: {{ publicQr.place_name }}</p>
                <p class="text-white/40">Ban kinh: {{ publicQr.allowed_radius_m }}m</p>
              </div>
              <div v-else class="bg-orange-500/10 border border-orange-500/30 rounded-xl p-2 text-xs text-orange-400">
                Chua cau hinh vi tri phat hanh
              </div>
            </div>
          </div>

          <!-- Config form -->
          <div class="border-t border-white/5 pt-4 space-y-3">
            <p class="text-xs text-white/40 uppercase tracking-wider">Cau hinh vi tri phat hanh</p>
            <VietmapPlacePicker :modelValue="place" @update:modelValue="syncFromPicker" />
            <div>
              <label class="text-xs text-white/50 block mb-1">Ban kinh hop le (met)</label>
              <input type="number" v-model="formPublic.allowed_radius_m" :class="inputCls" min="1" max="5000" />
            </div>
            <button
              :disabled="formPublic.processing"
              @click="savePublic"
              class="w-full py-2 text-sm rounded-xl bg-brand-500 text-cosmic-950 font-semibold hover:bg-brand-600 transition disabled:opacity-50">
              Luu cau hinh
            </button>
            <p class="text-xs text-white/30">
              QR public chi xem duoc khi nguoi quet dung trong ban kinh quanh toa do phat hanh.
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