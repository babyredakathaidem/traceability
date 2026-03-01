<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch } from "vue";
import VietmapPlacePicker from "@/Components/VietmapPlacePicker.vue";
import QRCode from "qrcode";

const props = defineProps({
  batch: Object,
  qrs: Array,
  publicUrlBase: String,
  privateUrlBase: String,
});

const publicQr = computed(() => props.qrs.find((q) => q.type === "public"));
const privateQr = computed(() => props.qrs.find((q) => q.type === "private"));

const publicLink = computed(() => (publicQr.value ? `${props.publicUrlBase}/${publicQr.value.token}` : ""));
const privateLink = computed(() => (privateQr.value ? `${props.privateUrlBase}/${privateQr.value.token}` : ""));

// Vietmap picker state (cho public)
const place = ref({
  place_name: publicQr.value?.place_name || "",
  lat: publicQr.value?.allowed_lat || "",
  lng: publicQr.value?.allowed_lng || "",
  refid: "",
});

// Forms
const formEnsure = useForm({});
const formPublic = useForm({
  place_name: place.value.place_name,
  allowed_lat: place.value.lat,
  allowed_lng: place.value.lng,
  allowed_radius_m: publicQr.value?.allowed_radius_m ?? 50,
});

function ensureQrs() {
  formEnsure.post(route("batches.qrs.ensure", props.batch.id));
}

function syncFromPicker(val) {
  place.value = val;
  formPublic.place_name = val.place_name;
  formPublic.allowed_lat = val.lat;
  formPublic.allowed_lng = val.lng;
}

function savePublic() {
  if (!publicQr.value) return;
  formPublic.post(route("qrcodes.configurePublic", publicQr.value.id));
}

// ===== QR image generation (PNG dataURL) =====
const publicQrPng = ref("");
const privateQrPng = ref("");

async function genQrPng(text) {
  if (!text) return "";
  // scale to print nicely: 1024px PNG
  return await QRCode.toDataURL(text, {
    errorCorrectionLevel: "M",
    width: 1024,
    margin: 2,
  });
}

async function refreshQrImages() {
  publicQrPng.value = await genQrPng(publicLink.value);
  privateQrPng.value = await genQrPng(privateLink.value);
}

onMounted(refreshQrImages);
watch([publicLink, privateLink], refreshQrImages);

// Download helper
function downloadDataUrl(dataUrl, filename) {
  if (!dataUrl) return;
  const a = document.createElement("a");
  a.href = dataUrl;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
}
</script>

<template>
  <Head title="QR theo lô" />

  <div class="p-6 max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold">QR theo lô</h1>
        <p class="text-sm text-gray-600">
          Lô: <b>{{ batch.code }}</b> — {{ batch.product_name }}
        </p>
      </div>

      <button
        class="border rounded-xl px-4 py-2 hover:bg-gray-50 disabled:opacity-50"
        :disabled="formEnsure.processing"
        @click="ensureQrs"
      >
        Tạo 2 QR (public/private)
      </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- PUBLIC -->
      <div class="bg-white border rounded-2xl p-5 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold">QR Public (quầy trưng bày)</h2>
          <span class="text-xs px-2 py-1 rounded-full border">PUBLIC</span>
        </div>

        <div v-if="!publicQr" class="text-sm text-gray-600">
          Chưa có QR public. Bấm “Tạo 2 QR”.
        </div>

        <template v-else>
          <div class="text-sm break-all">
            Link:
            <a class="text-blue-600 underline" :href="publicLink" target="_blank">{{ publicLink }}</a>
          </div>

          <!-- QR IMAGE -->
          <div class="flex items-start gap-4">
            <div class="shrink-0">
              <div class="w-44 h-44 border rounded-xl flex items-center justify-center bg-white overflow-hidden">
                <img v-if="publicQrPng" :src="publicQrPng" alt="QR Public" class="w-full h-full object-contain" />
                <div v-else class="text-xs text-gray-500">Chưa tạo QR</div>
              </div>

              <button
                class="mt-3 w-44 border rounded-xl px-3 py-2 text-sm hover:bg-gray-50 disabled:opacity-50"
                :disabled="!publicQrPng"
                @click="downloadDataUrl(publicQrPng, `QR_PUBLIC_${batch.code}.png`)"
              >
                Tải PNG
              </button>
            </div>

            <div class="flex-1 space-y-3">
              <VietmapPlacePicker :modelValue="place" @update:modelValue="syncFromPicker" />

              <div>
                <label class="text-sm font-medium">Bán kính hợp lệ (m)</label>
                <input
                  type="number"
                  class="mt-1 w-full border rounded-xl px-3 py-2"
                  v-model="formPublic.allowed_radius_m"
                />
              </div>

              <button
                class="w-full border rounded-xl px-4 py-2 hover:bg-gray-50 disabled:opacity-50"
                :disabled="formPublic.processing"
                @click="savePublic"
              >
                Lưu cấu hình public
              </button>

              <p class="text-xs text-gray-500">
                Public sẽ chỉ cho xem truy xuất nếu vị trí quét nằm trong bán kính quanh tọa độ phát hành.
              </p>
            </div>
          </div>
        </template>
      </div>

      <!-- PRIVATE -->
      <div class="bg-white border rounded-2xl p-5 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold">QR Private (trong nắp chai)</h2>
          <span class="text-xs px-2 py-1 rounded-full border">PRIVATE</span>
        </div>

        <div v-if="!privateQr" class="text-sm text-gray-600">
          Chưa có QR private. Bấm “Tạo 2 QR”.
        </div>

        <template v-else>
          <div class="text-sm break-all">
            Link:
            <a class="text-blue-600 underline" :href="privateLink" target="_blank">{{ privateLink }}</a>
          </div>

          <div class="flex items-start gap-4">
            <div class="shrink-0">
              <div class="w-44 h-44 border rounded-xl flex items-center justify-center bg-white overflow-hidden">
                <img v-if="privateQrPng" :src="privateQrPng" alt="QR Private" class="w-full h-full object-contain" />
                <div v-else class="text-xs text-gray-500">Chưa tạo QR</div>
              </div>

              <button
                class="mt-3 w-44 border rounded-xl px-3 py-2 text-sm hover:bg-gray-50 disabled:opacity-50"
                :disabled="!privateQrPng"
                @click="downloadDataUrl(privateQrPng, `QR_PRIVATE_${batch.code}.png`)"
              >
                Tải PNG
              </button>
            </div>

            <div class="flex-1 text-sm text-gray-700 space-y-2">
              <div>- Không check địa lý</div>
              <div>- Hết hiệu lực sau <b>48h</b> kể từ lần quét đầu</div>

              <div v-if="privateQr.first_scanned_at" class="text-sm text-gray-600">
                First scan: {{ privateQr.first_scanned_at }}<br />
                Expires: {{ privateQr.expires_at }}
              </div>

              <p class="text-xs text-gray-500">
                Dù private không check vị trí, hệ thống vẫn ghi log time + device + (nếu có) GPS.
              </p>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
