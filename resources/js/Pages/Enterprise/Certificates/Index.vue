<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Modal from '@/Components/Modal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { 
  PlusIcon, 
  AcademicCapIcon, 
  CalendarIcon, 
  IdentificationIcon,
  CheckBadgeIcon,
  NoSymbolIcon,
  DocumentArrowUpIcon,
  PencilSquareIcon,
  BuildingOfficeIcon,
  GlobeAltIcon,
  ClipboardDocumentCheckIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  certificates: Array,
  standardNames: Array,
})

const showingModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

const form = useForm({
  name: '',
  organization: '',
  certificate_no: '',
  scope: '',
  issue_date: '',
  expiry_date: '',
  image: null,
  status: 'active',
  note: '',
})

const openCreateModal = () => {
  form.reset()
  isEditing.value = false
  showingModal.value = true
}
const openEditModal = (cert) => {
  form.name = cert.name
  form.organization = cert.organization
  form.certificate_no = cert.certificate_no
  form.scope = cert.scope
  form.issue_date = cert.issue_date_raw
  form.expiry_date = cert.expiry_date_raw
  form.status = cert.status
  form.note = cert.note
  form.image = null // Reset file input

  editingId.value = cert.id
  isEditing.value = true
  showingModal.value = true
}

const submit = () => {
  if (isEditing.value) {
    // Dùng form.post kèm _method: 'PUT' để hỗ trợ upload file trong Inertia
    form.transform((data) => ({
      ...data,
      _method: 'PUT',
    })).post(route('certificates.update', editingId.value), {
      forceFormData: true,
      onSuccess: () => closeModal(),
    })
  } else {
    form.post(route('certificates.store'), {
      onSuccess: () => closeModal(),
    })
  }
}


const remove = (id) => {
  if (confirm('Xóa chứng chỉ này? Thao tác này không thể hoàn tác.')) {
    router.delete(route('certificates.destroy', id))
  }
}

const closeModal = () => {
  showingModal.value = false
  form.reset()
}

const statusClass = (status, isExpired) => {
  if (isExpired || status === 'expired') return 'text-red-400 bg-red-400/10 border-red-400/20'
  if (status === 'revoked') return 'text-orange-400 bg-orange-400/10 border-orange-400/20'
  return 'text-brand-400 bg-brand-400/10 border-brand-400/20'
}

const statusLabel = (status, isExpired) => {
  if (isExpired || status === 'expired') return 'Hết hạn'
  if (status === 'revoked') return 'Bị thu hồi'
  return 'Đang hiệu lực'
}

const inputCls = "w-full bg-black/40 border border-glass rounded-2xl px-4 py-3 text-sm text-white/90 placeholder:text-white/20 focus:border-brand-500/60 outline-none transition-all focus:ring-4 focus:ring-brand-500/5"
</script>

<template>
  <Head title="Năng lực & Chứng nhận" />

  <AuthenticatedLayout>
    <div class="space-y-8 max-w-6xl mx-auto pb-12">
      
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6" data-aos="fade-down">
        <div class="space-y-1">
          <div class="flex items-center gap-2 text-brand-400 font-black text-[10px] uppercase tracking-[0.3em]">
            <span class="w-8 h-[1px] bg-brand-500/50"></span>
            Enterprise Assets
          </div>
          <h1 class="text-3xl font-black text-white tracking-tight">Hồ sơ năng lực & <span class="text-brand-400">Chứng nhận</span></h1>
          <p class="text-white/40 text-sm font-medium">Quản lý các tiêu chuẩn VietGAP, GlobalGAP, ISO để gia tăng giá trị thương hiệu.</p>
        </div>
        
        <button 
          @click="openCreateModal"
          class="group relative flex items-center gap-2 bg-brand-500 hover:bg-brand-400 text-cosmic-950 px-6 py-3 rounded-2xl transition-all font-black text-xs uppercase tracking-widest shadow-xl shadow-brand-900/20 active:scale-95 overflow-hidden"
        >
          <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
          <PlusIcon class="w-5 h-5 stroke-[3px]" />
          Thêm chứng nhận mới
        </button>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" data-aos="fade-up" data-aos-delay="100">
        <div v-for="(stat, i) in [
          { label: 'Tổng chứng nhận', val: certificates.length, icon: AcademicCapIcon, color: 'text-brand-400' },
          { label: 'Đang hiệu lực', val: certificates.filter(c => c.status === 'active' && !c.is_expired).length, icon: CheckBadgeIcon, color: 'text-brand-400' },
          { label: 'Cần cập nhật', val: certificates.filter(c => c.status !== 'active' || c.is_expired).length, icon: NoSymbolIcon, color: 'text-red-400' }
        ]" :key="i" class="bg-white/5 border border-glass rounded-3xl p-6 relative overflow-hidden group">
          <component :is="stat.icon" class="absolute -right-4 -bottom-4 w-24 h-24 opacity-5 group-hover:opacity-10 transition-all duration-500" />
          <div class="relative z-10 flex items-center gap-4">
            <div class="p-3 rounded-2xl bg-white/5 border border-white/10 shadow-inner">
              <component :is="stat.icon" class="w-6 h-6" :class="stat.color" />
            </div>
            <div>
              <div class="text-[10px] text-white/30 uppercase font-black tracking-widest">{{ stat.label }}</div>
              <div class="text-2xl font-black text-white mt-0.5">{{ stat.val }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div v-if="certificates.length === 0" 
        class="bg-white/5 border border-dashed border-white/10 rounded-[2rem] p-20 text-center" data-aos="zoom-in" data-aos-delay="200">
        <div class="w-24 h-24 rounded-full bg-brand-500/10 flex items-center justify-center mx-auto mb-6 shadow-2xl shadow-brand-500/10 animate-pulse">
          <AcademicCapIcon class="w-12 h-12 text-brand-500/40" />
        </div>
        <h3 class="text-xl font-bold text-white/80">Chưa có chứng nhận nào</h3>
        <p class="text-white/30 text-sm mt-2 max-w-xs mx-auto leading-relaxed">Hãy bắt đầu bằng việc khai báo các chứng chỉ chất lượng để khách hàng tin tưởng hơn.</p>
        <button @click="openCreateModal" class="mt-6 text-brand-400 font-bold text-sm hover:underline uppercase tracking-widest">Khai báo ngay →</button>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6" v-auto-animate>
        <div 
          v-for="(cert, i) in certificates" 
          :key="cert.id"
          class="relative bg-white/5 border border-glass rounded-[2rem] p-6 hover:bg-white/10 transition-all duration-500 group overflow-hidden shadow-lg hover:shadow-brand-500/5"
          data-aos="fade-up" :data-aos-delay="100 + (i * 50)"
        >
          <!-- Background Glow -->
          <div class="absolute -right-10 -top-10 w-40 h-40 bg-brand-500/5 blur-3xl rounded-full group-hover:bg-brand-500/10 transition-all duration-700"></div>

          <div class="flex items-start justify-between gap-4 relative z-10">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-lg bg-brand-500/20 flex items-center justify-center text-brand-400 shadow-inner">
                  <ClipboardDocumentCheckIcon class="w-5 h-5" />
                </div>
                <h3 class="text-lg font-black text-white truncate leading-tight group-hover:text-brand-300 transition-colors">{{ cert.name }}</h3>
              </div>
              
              <div class="text-sm text-white/50 font-bold flex items-center gap-2 mb-6">
                <BuildingOfficeIcon class="w-4 h-4 opacity-40" />
                {{ cert.organization || 'Đang cập nhật tổ chức cấp' }}
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div class="bg-black/30 rounded-2xl p-3 border border-white/5">
                  <div class="text-[9px] text-white/20 uppercase font-black tracking-tighter mb-1">Mã số chứng chỉ</div>
                  <div class="text-xs text-white/70 font-mono font-bold truncate">{{ cert.certificate_no || 'N/A' }}</div>
                </div>
                <div class="bg-black/30 rounded-2xl p-3 border border-white/5">
                  <div class="text-[9px] text-white/20 uppercase font-black tracking-tighter mb-1">Thời hạn</div>
                  <div class="text-xs font-bold" :class="cert.is_expired ? 'text-red-400' : 'text-brand-400'">
                    {{ cert.expiry_date || 'Vô thời hạn' }}
                  </div>
                </div>
              </div>
            </div>

            <div class="flex flex-col gap-2 shrink-0">
              <span :class="statusClass(cert.status, cert.is_expired)" class="text-[9px] px-2 py-1 rounded-lg border font-black uppercase tracking-widest text-center">
                {{ statusLabel(cert.status, cert.is_expired) }}
              </span>
              <div class="flex gap-1 mt-auto pt-12">
                <button @click="openEditModal(cert)" class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-white/30 hover:text-white hover:bg-white/10 transition-all" title="Sửa">
                  <PencilSquareIcon class="w-5 h-5" />
                </button>
                <button @click="remove(cert.id)" class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-white/20 hover:text-red-400 hover:bg-red-500/10 transition-all" title="Xóa">
                  <TrashIcon class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
          
          <div class="mt-6 pt-4 border-t border-white/5 flex items-center justify-between relative z-10">
            <div class="flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_8px_rgba(249,115,22,0.6)]"></span>
              <span class="text-[10px] text-brand-400/60 font-black uppercase tracking-widest italic">Áp dụng cho {{ cert.batches_count }} lô hàng</span>
            </div>
            <button v-if="cert.image_url" @click="lightboxUrl = cert.image_url" class="text-[10px] font-black text-white/40 hover:text-white transition-colors flex items-center gap-1.5">
              <DocumentArrowUpIcon class="w-4 h-4" />
              XEM MINH CHỨNG
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Premium Modal ────────────────────────────────────── -->
    <Modal :show="showingModal" @close="closeModal" max-width="2xl">
      <div class="bg-cosmic-950 p-8 relative overflow-hidden">
        <!-- Glow Effect -->
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-brand-500/10 blur-[100px] rounded-full"></div>
        <div class="absolute -left-20 -bottom-20 w-64 h-64 bg-brand-500/10 blur-[100px] rounded-full"></div>

        <div class="relative z-10">
          <div class="flex items-center gap-4 mb-8">
            <div class="w-12 h-12 rounded-2xl bg-brand-500/20 border border-brand-500/30 flex items-center justify-center text-brand-400 shadow-xl shadow-brand-900/20">
              <AcademicCapIcon class="w-7 h-7 stroke-[2.5px]" />
            </div>
            <div>
              <h2 class="text-2xl font-black text-white tracking-tight">
                {{ isEditing ? 'Cập nhật chứng nhận' : 'Khai báo năng lực mới' }}
              </h2>
              <p class="text-xs text-white/40 font-medium">Thông tin này sẽ được dùng để xác thực chất lượng sản phẩm.</p>
            </div>
          </div>

          <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              
              <!-- Certificate Name -->
              <div class="md:col-span-2">
                <div class="flex items-center justify-between mb-2">
                  <label class="text-[10px] text-white/30 uppercase font-black tracking-[0.2em]">Tên chứng chỉ / Tiêu chuẩn</label>
                  <span class="text-[10px] text-brand-400/50 font-bold italic">Gợi ý có sẵn</span>
                </div>
                
                <div class="flex flex-wrap gap-1.5 mb-3">
                  <button v-for="name in standardNames" :key="name" type="button" @click="form.name = name"
                    class="text-[9px] px-2 py-1 rounded-lg bg-brand-500/5 border border-brand-500/10 text-white/40 hover:bg-brand-500 hover:text-cosmic-950 transition-all font-black uppercase tracking-tighter">
                    {{ name }}
                  </button>
                </div>

                <div class="relative group">
                  <IdentificationIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/20 group-focus-within:text-brand-400 transition-colors" />
                  <input v-model="form.name" list="std-names" :class="[inputCls, 'pl-12']" placeholder="Gõ tên hoặc chọn gợi ý..." required />
                  <datalist id="std-names">
                    <option v-for="n in standardNames" :key="n" :value="n" />
                  </datalist>
                </div>
                <InputError :message="form.errors.name" class="mt-2" />
              </div>

              <!-- Organization -->
              <div>
                <label class="text-[10px] text-white/30 uppercase font-black tracking-[0.2em] block mb-2">Tổ chức cấp</label>
                <div class="relative group">
                  <BuildingOfficeIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/20 group-focus-within:text-brand-400 transition-colors" />
                  <input v-model="form.organization" :class="[inputCls, 'pl-12']" placeholder="Cơ quan kiểm định..." />
                </div>
              </div>

              <!-- Certificate No -->
              <div>
                <label class="text-[10px] text-white/30 uppercase font-black tracking-[0.2em] block mb-2">Số hiệu văn bản</label>
                <div class="relative group">
                  <GlobeAltIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/20 group-focus-within:text-brand-400 transition-colors" />
                  <input v-model="form.certificate_no" :class="[inputCls, 'pl-12']" placeholder="Số hiệu..." />
                </div>
              </div>

              <!-- Scope -->
              <div class="md:col-span-2">
                <label class="text-[10px] text-white/30 uppercase font-black tracking-[0.2em] block mb-2">Phạm vi chứng nhận</label>
                <textarea v-model="form.scope" rows="2" :class="inputCls" placeholder="Ví dụ: Áp dụng cho toàn bộ quy trình sản xuất lúa gạo tại trang trại X..."></textarea>
              </div>

              <!-- Dates -->
              <div>
                <label class="text-[10px] text-white/30 uppercase font-black tracking-[0.2em] block mb-2">Ngày cấp</label>
                <input v-model="form.issue_date" type="date" :class="inputCls" />
              </div>
              <div>
                <label class="text-[10px] text-white/30 uppercase font-black tracking-[0.2em] block mb-2">Ngày hết hạn</label>
                <input v-model="form.expiry_date" type="date" :class="inputCls" />
              </div>

              <!-- Image -->
              <div class="md:col-span-2">
                <label class="text-[10px] text-white/30 uppercase font-black tracking-[0.2em] block mb-2">Ảnh chụp / Bản scan minh chứng</label>
                <div class="relative border-2 border-dashed border-white/5 rounded-2xl p-4 hover:border-brand-500/30 transition-all bg-black/20 group">
                  <input type="file" @input="form.image = $event.target.files[0]" 
                    class="absolute inset-0 opacity-0 cursor-pointer z-20" />
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-white/5 flex items-center justify-center text-white/20 group-hover:text-brand-400 transition-colors">
                      <DocumentArrowUpIcon class="w-6 h-6" />
                    </div>
                    <div>
                      <div class="text-xs font-bold text-white/60">Kéo thả hoặc Click để tải lên</div>
                      <div class="text-[10px] text-white/20 uppercase font-black mt-0.5">Hỗ trợ: JPG, PNG, PDF (Max 5MB)</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-8 pt-6 border-t border-white/5 flex justify-end gap-4">
              <button type="button" @click="closeModal" class="px-6 py-3 rounded-2xl text-white/40 hover:text-white text-xs font-black uppercase tracking-widest transition-colors">
                Hủy bỏ
              </button>
              <button 
                :disabled="form.processing"
                class="bg-brand-500 text-cosmic-950 px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-brand-900/30 hover:bg-brand-400 active:scale-95 transition-all disabled:opacity-50"
              >
                {{ form.processing ? 'Đang thực hiện...' : (isEditing ? 'LƯU THAY ĐỔI' : 'XÁC NHẬN KHAI BÁO') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

<style scoped>
@keyframes shimmer {
  100% { transform: translateX(100%); }
}
.group-hover\:animate-shimmer {
  animation: shimmer 1.5s infinite;
}
</style>
