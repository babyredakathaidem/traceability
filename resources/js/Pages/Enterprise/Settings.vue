<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  enterprise: { type: Object, default: () => ({}) },
})

const flash = usePage().props.flash || {}

const form = useForm({
  name:                          props.enterprise.name                          ?? '',
  phone:                         props.enterprise.phone                         ?? '',
  email:                         props.enterprise.email                         ?? '',
  province:                      props.enterprise.province                      ?? '',
  district:                      props.enterprise.district                      ?? '',
  address_detail:                props.enterprise.address_detail                ?? '',
  representative_name:           props.enterprise.representative_name           ?? '',
  representative_id:             props.enterprise.representative_id             ?? '',
  business_cert_no:              props.enterprise.business_cert_no              ?? '',
  business_cert_issued_place:    props.enterprise.business_cert_issued_place    ?? '',
  business_license_no:           props.enterprise.business_license_no           ?? '',
  business_license_issued_place: props.enterprise.business_license_issued_place ?? '',
})

const submit = () => {
  form.put(route('enterprise.settings.update'))
}

const statusLabel = computed(() => {
  const map = { approved: 'Đã duyệt', pending: 'Chờ duyệt', rejected: 'Bị từ chối' }
  return map[props.enterprise.status] ?? props.enterprise.status
})
const statusCls = computed(() => {
  if (props.enterprise.status === 'approved') return 'text-green-400 bg-green-400/10 border-green-400/20'
  if (props.enterprise.status === 'rejected') return 'text-red-400 bg-red-400/10 border-red-400/20'
  return 'text-yellow-400 bg-yellow-400/10 border-yellow-400/20'
})

const inputCls = 'w-full rounded-xl border border-glass bg-black/20 px-3 py-2.5 text-sm text-white/90 placeholder:text-white/30 outline-none focus:border-brand-500/60'
const readonlyCls = 'w-full rounded-xl border border-glass bg-white/5 px-3 py-2.5 text-sm text-white/40 outline-none cursor-not-allowed'
const labelCls = 'block text-xs text-white/50 mb-1'
</script>

<template>
  <Head title="Cài đặt doanh nghiệp" />

  <div class="space-y-6 max-w-3xl">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6 flex items-center justify-between gap-4">
      <div>
        <div class="text-brand-300 text-sm font-semibold">Quản trị</div>
        <h1 class="text-2xl font-bold mt-1 text-white/90">Cài đặt doanh nghiệp</h1>
        <p class="text-white/40 text-sm mt-1">Cập nhật thông tin liên lạc và đại diện pháp lý.</p>
      </div>
      <div class="flex items-center gap-3 flex-wrap">
        <div v-if="flash.success" class="text-sm text-green-400">{{ flash.success }}</div>
        <span :class="statusCls" class="text-xs px-3 py-1 rounded-full border font-semibold">
          {{ statusLabel }}
        </span>
      </div>
    </div>

    <!-- Thông tin cố định (readonly) -->
    <div class="rounded-2xl border border-glass bg-black/20 p-5 space-y-4">
      <div class="text-xs text-white/40 uppercase tracking-widest">Thông tin đăng ký (không thể thay đổi)</div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label :class="labelCls">Mã doanh nghiệp hệ thống</label>
          <input :value="enterprise.code" :class="readonlyCls" readonly />
        </div>
        <div>
          <label :class="labelCls">Mã số doanh nghiệp (MST)</label>
          <input :value="enterprise.business_code" :class="readonlyCls" readonly />
        </div>
        <div>
          <label :class="labelCls">Ngày cấp MST</label>
          <input :value="enterprise.business_code_issued_at" :class="readonlyCls" readonly />
        </div>
        <div v-if="enterprise.approved_at">
          <label :class="labelCls">Ngày được duyệt</label>
          <input :value="enterprise.approved_at" :class="readonlyCls" readonly />
        </div>
      </div>

      <!-- Link xem GCN -->
      <div v-if="enterprise.cert_file_url">
        <label :class="labelCls">Giấy chứng nhận ĐKDN</label>
        <a :href="enterprise.cert_file_url" target="_blank"
          class="inline-flex items-center gap-2 text-xs text-brand-400 hover:underline mt-1">
          Xem file đã nộp
        </a>
      </div>
    </div>

    <!-- Form chỉnh sửa -->
    <form @submit.prevent="submit" class="rounded-2xl border border-glass bg-black/20 p-5 space-y-5">
      <div class="text-xs text-white/40 uppercase tracking-widest">Thông tin có thể cập nhật</div>

      <!-- Tên DN + Phone + Email -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label :class="labelCls">Tên doanh nghiệp <span class="text-red-400">*</span></label>
          <input v-model="form.name" :class="inputCls" placeholder="Tên đầy đủ của doanh nghiệp" />
          <p v-if="form.errors.name" class="text-xs text-red-400 mt-1">{{ form.errors.name }}</p>
        </div>
        <div>
          <label :class="labelCls">Số điện thoại <span class="text-red-400">*</span></label>
          <input v-model="form.phone" :class="inputCls" placeholder="0xxx xxx xxx" />
          <p v-if="form.errors.phone" class="text-xs text-red-400 mt-1">{{ form.errors.phone }}</p>
        </div>
        <div>
          <label :class="labelCls">Email doanh nghiệp <span class="text-red-400">*</span></label>
          <input v-model="form.email" type="email" :class="inputCls" placeholder="email@congty.com" />
          <p v-if="form.errors.email" class="text-xs text-red-400 mt-1">{{ form.errors.email }}</p>
        </div>
      </div>

      <!-- Địa chỉ -->
      <div class="border-t border-white/5 pt-4">
        <div class="text-xs text-white/30 mb-3">Địa chỉ</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="labelCls">Tỉnh/Thành phố <span class="text-red-400">*</span></label>
            <input v-model="form.province" :class="inputCls" placeholder="VD: An Giang" />
            <p v-if="form.errors.province" class="text-xs text-red-400 mt-1">{{ form.errors.province }}</p>
          </div>
          <div>
            <label :class="labelCls">Quận/Huyện <span class="text-red-400">*</span></label>
            <input v-model="form.district" :class="inputCls" placeholder="VD: Long Xuyên" />
            <p v-if="form.errors.district" class="text-xs text-red-400 mt-1">{{ form.errors.district }}</p>
          </div>
          <div class="md:col-span-2">
            <label :class="labelCls">Địa chỉ cụ thể</label>
            <input v-model="form.address_detail" :class="inputCls" placeholder="Số nhà, tên đường, ấp/khu phố..." />
          </div>
        </div>
      </div>

      <!-- Người đại diện -->
      <div class="border-t border-white/5 pt-4">
        <div class="text-xs text-white/30 mb-3">Người đại diện pháp lý</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="labelCls">Họ tên đại diện</label>
            <input v-model="form.representative_name" :class="inputCls" placeholder="Nguyễn Văn A" />
          </div>
          <div>
            <label :class="labelCls">Số CCCD/Hộ chiếu</label>
            <input v-model="form.representative_id" :class="inputCls" placeholder="0xxxxxxxxx" />
          </div>
        </div>
      </div>

      <!-- Giấy phép kinh doanh -->
      <div class="border-t border-white/5 pt-4">
        <div class="text-xs text-white/30 mb-3">Giấy phép kinh doanh</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="labelCls">Số GCN ĐKDN</label>
            <input v-model="form.business_cert_no" :class="inputCls" placeholder="Số giấy chứng nhận" />
          </div>
          <div>
            <label :class="labelCls">Nơi cấp GCN</label>
            <input v-model="form.business_cert_issued_place" :class="inputCls" placeholder="VD: Sở KH&ĐT tỉnh An Giang" />
          </div>
          <div>
            <label :class="labelCls">Số giấy phép kinh doanh</label>
            <input v-model="form.business_license_no" :class="inputCls" placeholder="Số giấy phép KD" />
          </div>
          <div>
            <label :class="labelCls">Nơi cấp giấy phép KD</label>
            <input v-model="form.business_license_issued_place" :class="inputCls" placeholder="VD: UBND tỉnh An Giang" />
          </div>
        </div>
      </div>

      <!-- Submit -->
      <div class="border-t border-white/5 pt-4 flex items-center justify-between gap-4">
        <p class="text-xs text-white/30">
          Mã số thuế và mã hệ thống không thể thay đổi sau khi được duyệt.
        </p>
        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-2.5 rounded-xl bg-brand-500 text-cosmic-950 font-extrabold text-sm hover:bg-brand-600 transition disabled:opacity-50 whitespace-nowrap"
        >
          {{ form.processing ? 'Đang lưu...' : 'Lưu thay đổi' }}
        </button>
      </div>
    </form>

  </div>
</template>