<script setup>
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { MapPinIcon, ShieldCheckIcon } from "@heroicons/vue/24/outline";

defineOptions({ layout: GuestLayout });

const props = defineProps({ token: String });
const loading = ref(false);
const error = ref("");

function devicePayload() {
  const ua = navigator.userAgent || "";
  const platform = navigator.platform || "";
  return { device_name: ua, device_platform: platform };
}

function go() {
  loading.value = true;
  error.value = "";
  const device = devicePayload();

  if (!navigator.geolocation) {
    error.value = "Trình duyệt của bạn không hỗ trợ định vị.";
    loading.value = false;
    return;
  }

  navigator.geolocation.getCurrentPosition(
    (pos) => {
      router.post(route("trace.resolve.public", props.token), {
        lat: pos.coords.latitude,
        lng: pos.coords.longitude,
        ...device,
      });
    },
    (err) => {
      loading.value = false;
      error.value = "Không thể lấy vị trí. Vui lòng kiểm tra quyền truy cập GPS của trình duyệt.";
      console.error(err);
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}
</script>

<template>
  <Head title="Xác thực vị trí — AGU Trace" />
  
  <div class="max-w-md mx-auto px-6 py-12 space-y-8" data-aos="fade-up">
    <!-- Header Icon -->
    <div class="flex justify-center">
      <div class="w-20 h-20 rounded-3xl bg-brand-500/10 border border-brand-500/20 flex items-center justify-center text-brand-400 shadow-2xl shadow-brand-500/10 animate-pulse">
        <MapPinIcon class="w-10 h-10" />
      </div>
    </div>

    <!-- Content -->
    <div class="text-center space-y-3">
      <h1 class="text-2xl font-black text-white">Xác thực điểm quét</h1>
      <p class="text-sm text-white/50 leading-relaxed px-4">
        Để đảm bảo tính minh bạch theo tiêu chuẩn <span class="text-brand-400 font-bold">TCVN 12850</span>, bạn cần cung cấp vị trí để hệ thống đối chiếu với điểm phát hành tại cửa hàng.
      </p>
    </div>

    <!-- Error/Notice -->
    <div v-if="error" class="p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-xs text-red-400 text-center animate-shake">
      ⚠️ {{ error }}
    </div>

    <!-- Action Button -->
    <div class="space-y-4">
      <button 
        @click="go" 
        :disabled="loading"
        class="w-full py-4 rounded-2xl bg-brand-500 text-brand-950 font-black text-sm uppercase tracking-widest hover:bg-brand-400 active:scale-95 transition-all shadow-xl shadow-brand-500/20 disabled:opacity-50 flex items-center justify-center gap-3"
      >
        <span v-if="loading" class="w-5 h-5 border-2 border-brand-950 border-t-transparent rounded-full animate-spin"></span>
        <span v-else>Đồng ý & Chia sẻ vị trí</span>
      </button>
      
      <div class="flex items-center justify-center gap-2 text-[10px] text-white/20 uppercase font-bold tracking-widest">
        <ShieldCheckIcon class="w-4 h-4" />
        Dữ liệu được mã hóa & bảo mật
      </div>
    </div>

    <!-- Footer -->
    <div class="pt-8 text-center border-t border-white/5">
      <p class="text-[10px] text-white/10 font-bold tracking-[0.2em]">AGU TRACEABILITY SYSTEM</p>
    </div>
  </div>
</template>

<style scoped>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-4px); }
  75% { transform: translateX(4px); }
}
.animate-shake { animation: shake 0.2s ease-in-out 0s 2; }
</style>
