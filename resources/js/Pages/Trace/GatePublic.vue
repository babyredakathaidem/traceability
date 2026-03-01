<script setup>
import { Head, router } from "@inertiajs/vue3";
const props = defineProps({ token: String });

function devicePayload() {
  const ua = navigator.userAgent || "";
  const platform = navigator.platform || "";
  return { device_name: ua, device_platform: platform };
}

function go() {
  const device = devicePayload();

  if (!navigator.geolocation) {
    router.post(route("trace.resolve.public", props.token), { lat: null, lng: null, ...device });
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
    () => {
      router.post(route("trace.resolve.public", props.token), { lat: null, lng: null, ...device });
    },
    { enableHighAccuracy: true, timeout: 8000 }
  );
}
</script>

<template>
  <Head title="Xác thực QR Public" />
  <div class="min-h-screen flex items-center justify-center p-6">
    <div class="bg-white border rounded-2xl p-6 max-w-md w-full space-y-3">
      <h1 class="text-lg font-semibold">Xác thực vị trí quét</h1>
      <p class="text-sm text-gray-600">
        QR công khai chỉ hợp lệ khi bật định vị để đối chiếu điểm phát hành.
      </p>
      <button class="border rounded-xl px-4 py-2 hover:bg-gray-50 w-full" @click="go">
        Cho phép định vị & tiếp tục
      </button>
    </div>
  </div>
</template>
