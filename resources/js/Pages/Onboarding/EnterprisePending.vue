<script setup>
import { Head } from '@inertiajs/vue3'
import { onMounted, onBeforeUnmount, ref } from 'vue'

const checking = ref(true)
const errorMsg = ref(null)

let timer = null

async function checkStatus() {
  try {
    checking.value = true
    errorMsg.value = null

    const res = await fetch(route('onboarding.enterprise.status'), {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    })

    if (!res.ok) throw new Error('Request failed')
    const data = await res.json()

    // chưa có enterprise -> quay về form tạo
    if (!data.has_enterprise) {
      window.location.href = route('onboarding.enterprise.create')
      return
    }

    // ✅ approved -> dashboard
    if (data.status === 'approved') {
      window.location.href = route('dashboard')
      return
    }

    // rejected -> rejected page
    if (data.status === 'rejected') {
      window.location.href = route('onboarding.enterprise.rejected')
      return
    }

    // pending -> ở lại
  } catch (e) {
    errorMsg.value = 'Không thể kiểm tra trạng thái duyệt. Vui lòng thử lại.'
  } finally {
    checking.value = false
  }
}

onMounted(async () => {
  // check ngay lần đầu
  await checkStatus()

  // polling mỗi 3s
  timer = setInterval(checkStatus, 3000)
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})
</script>

<template>
  <Head title="Chờ duyệt doanh nghiệp" />

  <div class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-lg w-full bg-white border rounded-xl p-6 text-center">
      <h1 class="text-lg font-semibold">Hồ sơ doanh nghiệp đang chờ duyệt</h1>
      <p class="text-sm text-gray-600 mt-2">
        Doanh nghiệp của bạn đã được gửi lên hệ thống. Vui lòng chờ quản trị hệ thống duyệt trước khi sử dụng các chức năng nội bộ.
      </p>

      <div class="mt-4 text-xs text-gray-500">
        Hệ thống đang tự động kiểm tra trạng thái duyệt mỗi 3 giây...
      </div>

      <div v-if="checking" class="mt-4 text-sm text-gray-600">
        Đang kiểm tra...
      </div>

      <div v-if="errorMsg" class="mt-4 text-sm text-red-600">
        {{ errorMsg }}
      </div>

      <button
        class="mt-5 px-4 py-2 rounded border"
        @click="checkStatus"
      >
        Kiểm tra ngay
      </button>
    </div>
  </div>
</template>
