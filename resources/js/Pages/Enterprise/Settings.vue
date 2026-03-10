<script setup>
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { 
  AcademicCapIcon, 
  ChevronRightIcon, 
  InformationCircleIcon,
  BuildingOffice2Icon,
  PhoneIcon,
  EnvelopeIcon,
  MapPinIcon,
  UserIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline'

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

const inputCls = 'w-full rounded-2xl border border-glass bg-black/40 px-4 py-3 text-sm text-white/90 placeholder:text-white/20 outline-none focus:border-brand-500 transition-all focus:ring-4 focus:ring-brand-500/5'
const readonlyCls = 'w-full rounded-2xl border border-white/5 bg-white/5 px-4 py-3 text-sm text-white/30 outline-none cursor-not-allowed'
const labelCls = 'block text-[10px] text-white/30 uppercase font-black tracking-widest mb-1.5 ml-1'
</script>

<template>
  <Head title="Cài đặt doanh nghiệp" />

  <div class="space-y-8 max-w-4xl mx-auto pb-12" data-aos="fade-up">

    <!-- Header -->
    <div class="rounded-[2rem] border border-glass bg-black/40 p-8 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-2xl" data-aos="fade-down" data-aos-delay="100">
      <div>
        <div class="text-brand-400 text-xs font-black uppercase tracking-[0.2em] mb-1">Workspace Control</div>
        <h1 class="text-3xl font-black text-white tracking-tight">Thiết lập <span class="text-brand-400">Doanh nghiệp</span></h1>
        <p class="text-white/40 text-sm font-medium mt-1">Cấu hình hồ sơ pháp lý và thông tin định danh hệ thống.</p>
      </div>
      <div class="flex items-center gap-3">
        <div v-if="flash.success" class="text-sm text-green-400 font-bold animate-pulse">{{ flash.success }}</div>
        <span :class="statusCls" class="text-[10px] px-4 py-1.5 rounded-full border font-black uppercase tracking-widest">
          {{ statusLabel }}
        </span>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <!-- Cột trái: Thông tin cố định & Năng lực -->
      <div class="lg:col-span-1 space-y-6">
        
        <!-- Section: Năng lực & Chứng nhận -->
        <div 
          @click="router.visit(route('certificates.index'))"
          class="rounded-[2rem] border border-brand-500/20 bg-brand-500/5 p-6 flex flex-col gap-4 group hover:bg-brand-500/10 transition-all cursor-pointer relative overflow-hidden shadow-xl"
          data-aos="fade-right" data-aos-delay="200"
        >
          <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition-all text-brand-400">
            <AcademicCapIcon class="w-24 h-24" />
          </div>
          <div class="p-3 w-fit bg-brand-500/20 rounded-2xl text-brand-400 shadow-inner">
            <AcademicCapIcon class="w-8 h-8 stroke-[2.5px]" />
          </div>
          <div>
            <h3 class="text-lg font-black text-white group-hover:text-brand-300 transition-colors leading-tight">Hồ sơ năng lực</h3>
            <p class="text-xs text-white/40 mt-1 leading-relaxed">VietGAP, GlobalGAP, ISO... Chứng nhận chất lượng.</p>
          </div>
          <div class="flex items-center gap-2 text-[10px] font-black text-brand-400 uppercase tracking-widest mt-2">
            Quản lý ngay <ChevronRightIcon class="w-3 h-3 stroke-[3px]" />
          </div>
        </div>

        <!-- Thông tin cố định (readonly) -->
        <div class="rounded-[2rem] border border-glass bg-black/20 p-6 space-y-5" data-aos="fade-right" data-aos-delay="300">
          <div class="text-[10px] text-white/30 uppercase font-black tracking-widest mb-2 border-b border-white/5 pb-2">Định danh hệ thống</div>
          
          <div class="space-y-4">
            <div>
              <label :class="labelCls">Mã doanh nghiệp</label>
              <div class="font-mono text-xs text-brand-300 bg-brand-500/5 border border-brand-500/10 px-3 py-2 rounded-xl italic">
                {{ enterprise.code }}
              </div>
            </div>
            <div>
              <label :class="labelCls">Mã số thuế (MST)</label>
              <div class="font-mono text-xs text-white/60 bg-white/5 border border-white/10 px-3 py-2 rounded-xl">
                {{ enterprise.business_code }}
              </div>
            </div>
            <div v-if="enterprise.cert_file_url">
              <label :class="labelCls">Tài liệu pháp lý</label>
              <a :href="enterprise.cert_file_url" target="_blank"
                class="flex items-center gap-2 text-xs text-brand-400 hover:text-brand-300 transition-colors font-bold mt-1">
                <DocumentTextIcon class="w-4 h-4" />
                Bản scan GCN ĐKDN
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Cột phải: Form chỉnh sửa -->
      <div class="lg:col-span-2">
        <form @submit.prevent="submit" class="rounded-[2rem] border border-glass bg-black/20 p-8 space-y-8 shadow-2xl" data-aos="fade-left" data-aos-delay="400">
          
          <!-- Thông tin cơ bản -->
          <div class="space-y-5">
            <div class="flex items-center gap-2 text-[10px] text-brand-400 font-black uppercase tracking-widest mb-4">
              <BuildingOffice2Icon class="w-4 h-4" />
              Thông tin liên lạc
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2 group">
                <label :class="labelCls">Tên doanh nghiệp <span class="text-red-400">*</span></label>
                <input v-model="form.name" :class="inputCls" />
                <p v-if="form.errors.name" class="text-xs text-red-400 mt-1">{{ form.errors.name }}</p>
              </div>
              <div>
                <label :class="labelCls">Số điện thoại <span class="text-red-400">*</span></label>
                <div class="relative">
                  <PhoneIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20 group-focus-within:text-brand-400" />
                  <input v-model="form.phone" :class="[inputCls, 'pl-11']" />
                </div>
              </div>
              <div>
                <label :class="labelCls">Email công ty <span class="text-red-400">*</span></label>
                <div class="relative">
                  <EnvelopeIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20 group-focus-within:text-brand-400" />
                  <input v-model="form.email" type="email" :class="[inputCls, 'pl-11']" />
                </div>
              </div>
            </div>
          </div>

          <!-- Địa chỉ -->
          <div class="space-y-5 pt-6 border-t border-white/5">
            <div class="flex items-center gap-2 text-[10px] text-brand-400 font-black uppercase tracking-widest mb-4">
              <MapPinIcon class="w-4 h-4" />
              Địa chỉ trụ sở
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label :class="labelCls">Tỉnh/Thành phố <span class="text-red-400">*</span></label>
                <input v-model="form.province" :class="inputCls" />
              </div>
              <div>
                <label :class="labelCls">Quận/Huyện <span class="text-red-400">*</span></label>
                <input v-model="form.district" :class="inputCls" />
              </div>
              <div class="md:col-span-2">
                <label :class="labelCls">Địa chỉ chi tiết</label>
                <input v-model="form.address_detail" :class="inputCls" placeholder="Số nhà, đường, phường/xã..." />
              </div>
            </div>
          </div>

          <!-- Người đại diện & Pháp lý -->
          <div class="space-y-5 pt-6 border-t border-white/5">
            <div class="flex items-center gap-2 text-[10px] text-brand-400 font-black uppercase tracking-widest mb-4">
              <UserIcon class="w-4 h-4" />
              Đại diện & Pháp lý
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label :class="labelCls">Họ tên đại diện</label>
                <input v-model="form.representative_name" :class="inputCls" />
              </div>
              <div>
                <label :class="labelCls">Số CCCD/Hộ chiếu</label>
                <input v-model="form.representative_id" :class="inputCls" />
              </div>
              <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label :class="labelCls">Số GCN ĐKDN</label>
                  <input v-model="form.business_cert_no" :class="inputCls" />
                </div>
                <div>
                  <label :class="labelCls">Nơi cấp GCN</label>
                  <input v-model="form.business_cert_issued_place" :class="inputCls" />
                </div>
              </div>
            </div>
          </div>

          <!-- Submit -->
          <div class="pt-8 flex items-center justify-between gap-6">
            <p class="text-[10px] text-white/20 italic max-w-xs leading-relaxed">
              * Vui lòng kiểm tra kỹ thông tin. Một số trường dữ liệu sẽ được dùng để in lên nhãn QR truy xuất.
            </p>
            <button
              type="submit"
              :disabled="form.processing"
              class="relative group px-10 py-4 rounded-2xl bg-brand-500 text-cosmic-950 font-black text-xs uppercase tracking-[0.2em] hover:bg-brand-400 active:scale-95 transition-all shadow-xl shadow-brand-900/20 disabled:opacity-50 overflow-hidden"
            >
              <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
              {{ form.processing ? 'Đang cập nhật...' : 'Lưu thiết lập' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<style scoped>
@keyframes shimmer { 100% { transform: translateX(100%); } }
.group-hover\:animate-shimmer { animation: shimmer 1.5s infinite; }
</style>
