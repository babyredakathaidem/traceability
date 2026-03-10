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
  UserPlusIcon, 
  EnvelopeIcon, 
  ShieldCheckIcon, 
  PencilSquareIcon, 
  TrashIcon,
  IdentificationIcon,
  KeyIcon,
  CheckCircleIcon,
  UsersIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  staffList: Array,
  availablePermissions: Object, // { key: label }
})

// ── State ────────────────────────────────────────────────
const showingModal = ref(false)
const isEditing   = ref(false)
const editingId    = ref(null)

const form = useForm({
  name:        '',
  email:       '',
  password:    '',
  permissions: [],
})

const openCreate = () => {
  form.reset()
  form.permissions = []
  isEditing.value = false
  showingModal.value = true
}

const openEdit = (u) => {
  form.name        = u.name
  form.email       = u.email
  form.permissions = Array.isArray(u.permissions) ? u.permissions : []
  form.password    = ''
  editingId.value  = u.id
  isEditing.value  = true
  showingModal.value = true
}

const submit = () => {
  if (isEditing.value) {
    form.put(route('enterprise.users.update', editingId.value), {
      onSuccess: () => closeModal(),
    })
  } else {
    form.post(route('enterprise.users.store'), {
      onSuccess: () => closeModal(),
    })
  }
}

const remove = (u) => {
  if (!confirm(`Xóa tài khoản của "${u.name}"?`)) return
  router.delete(route('enterprise.users.destroy', u.id))
}

const closeModal = () => {
  showingModal.value = false
  form.reset()
}

const togglePerm = (key) => {
  const idx = form.permissions.indexOf(key)
  if (idx >= 0) form.permissions.splice(idx, 1)
  else form.permissions.push(key)
}

const inputCls = "w-full bg-black/40 border border-glass rounded-2xl px-4 py-3 text-sm text-white/90 placeholder:text-white/20 focus:border-brand-500 outline-none transition-all focus:ring-4 focus:ring-brand-500/5"
const labelCls = 'block text-[10px] text-white/30 uppercase font-black tracking-widest mb-1.5 ml-1'
</script>

<template>
  <Head title="Quản lý nhân sự" />


    <div class="space-y-8 max-w-5xl mx-auto pb-12" data-aos="fade-up">
      
      <!-- Header (Matching Settings Style) -->
      <div class="rounded-[2rem] border border-glass bg-black/40 p-8 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-2xl" data-aos="fade-down" data-aos-delay="100">
        <div>
          <div class="text-brand-400 text-xs font-black uppercase tracking-[0.2em] mb-1">Administration</div>
          <h1 class="text-3xl font-black text-white tracking-tight">Đội ngũ & <span class="text-brand-400">Nhân sự</span></h1>
          <p class="text-white/40 text-sm font-medium mt-1">Phân quyền chi tiết cho nhân viên tham gia chuỗi cung ứng.</p>
        </div>
        
        <button 
          @click="openCreate"
          class="flex items-center gap-2 bg-brand-500 hover:bg-brand-400 text-cosmic-950 px-8 py-4 rounded-2xl transition-all font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-brand-900/20 active:scale-95"
        >
          <UserPlusIcon class="w-5 h-5 stroke-[3px]" />
          Thêm nhân viên
        </button>
      </div>

      <!-- Main Content Area -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Summary -->
        <div class="lg:col-span-1 space-y-6">
          <div class="rounded-[2rem] border border-brand-500/20 bg-brand-500/5 p-6 flex flex-col gap-4 relative overflow-hidden shadow-xl" data-aos="fade-right" data-aos-delay="200">
             <div class="absolute -right-4 -bottom-4 opacity-5 text-brand-400">
              <UsersIcon class="w-24 h-24" />
            </div>
            <div class="p-3 w-fit bg-brand-500/20 rounded-2xl text-brand-400 shadow-inner">
              <UsersIcon class="w-8 h-8 stroke-[2.5px]" />
            </div>
            <div>
              <h3 class="text-lg font-black text-white leading-tight">Tổng nhân sự</h3>
              <p class="text-3xl font-black text-brand-400 mt-2">{{ staffList.length }}</p>
              <p class="text-xs text-white/40 mt-1 leading-relaxed">Tài khoản đang hoạt động trong hệ thống của bạn.</p>
            </div>
          </div>

          <div class="rounded-[2rem] border border-glass bg-black/20 p-6 space-y-5" data-aos="fade-right" data-aos-delay="300">
            <div class="text-[10px] text-white/30 uppercase font-black tracking-widest mb-2 border-b border-white/5 pb-2">Hướng dẫn phân quyền</div>
            <p class="text-xs text-white/40 leading-relaxed italic">
              "Mỗi nhân viên nên được cấp quyền tối thiểu cần thiết để thực hiện công việc (Principle of Least Privilege) theo đúng quy định Thông tư 02."
            </p>
          </div>
        </div>

        <!-- Right Column: User Cards -->
        <div class="lg:col-span-2 space-y-4">
          <div v-if="staffList.length === 0" 
            class="rounded-[2rem] border border-glass bg-black/20 p-20 text-center" data-aos="zoom-in">
            <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mx-auto mb-4 text-white/20">
              <UserPlusIcon class="w-8 h-8" />
            </div>
            <div class="text-white/40 font-bold uppercase tracking-widest text-sm">Chưa có tài khoản nhân viên</div>
          </div>

          <div v-else class="grid grid-cols-1 gap-4">
            <div 
              v-for="(u, i) in staffList" 
              :key="u.id"
              class="rounded-[2rem] border border-glass bg-black/20 p-6 hover:bg-white/5 transition-all group relative overflow-hidden shadow-lg"
              data-aos="fade-left" :data-aos-delay="i * 50"
            >
              <div class="flex items-center gap-6 relative z-10">
                <div class="w-14 h-14 rounded-2xl bg-brand-500/10 border border-brand-500/20 flex items-center justify-center text-brand-400 shrink-0 shadow-inner group-hover:scale-110 transition-transform">
                  <span class="text-xl font-black uppercase">{{ u.name[0] }}</span>
                </div>
                
                <div class="flex-1 min-w-0">
                  <h3 class="text-base font-black text-white truncate">{{ u.name }}</h3>
                  <div class="flex items-center gap-1.5 text-xs text-white/30 mt-0.5">
                    <EnvelopeIcon class="w-3.5 h-3.5" />
                    <span class="truncate">{{ u.email }}</span>
                  </div>
                  
                  <div class="flex flex-wrap gap-1.5 mt-3">
                    <span v-for="p in (u.permissions || [])" :key="p" 
                      class="text-[9px] px-2 py-0.5 rounded-lg bg-white/5 text-white/40 border border-white/5 uppercase font-bold tracking-widest">
                      {{ p.split('.').pop() }}
                    </span>
                  </div>
                </div>
                
                <div class="flex items-center gap-2">
                  <button @click="openEdit(u)" class="p-3 rounded-2xl bg-white/5 border border-white/10 text-white/30 hover:text-white hover:bg-white/10 transition-all">
                    <PencilSquareIcon class="w-5 h-5" />
                  </button>
                  <button @click="remove(u)" class="p-3 rounded-2xl bg-white/5 border border-white/10 text-white/20 hover:text-red-400 hover:bg-red-500/10 transition-all">
                    <TrashIcon class="w-5 h-5" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Modal Form (Matching Theme) -->
    <Modal :show="showingModal" @close="closeModal" max-width="2xl">
      <div class="p-10 bg-cosmic-950 relative overflow-hidden">
        <div class="absolute -right-20 -top-20 w-80 h-80 bg-brand-500/10 blur-[100px] rounded-full"></div>
        
        <div class="relative z-10">
          <h2 class="text-2xl font-black text-white tracking-tight flex items-center gap-3">
            <IdentificationIcon class="w-8 h-8 text-brand-400" />
            {{ isEditing ? 'Cập nhật tài khoản' : 'Thêm nhân viên mới' }}
          </h2>
          <p class="text-xs text-white/40 mb-8 font-medium mt-1 italic">Vui lòng cung cấp thông tin chính xác để định danh trong chuỗi cung ứng.</p>

          <form @submit.prevent="submit" class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <!-- Name -->
              <div>
                <label :class="labelCls">Họ và tên nhân viên <span class="text-red-400">*</span></label>
                <div class="relative group">
                  <IdentificationIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/20 group-focus-within:text-brand-400 transition-colors" />
                  <input v-model="form.name" :class="[inputCls, 'pl-12']" placeholder="Nguyễn Văn A" required />
                </div>
                <InputError :message="form.errors.name" class="mt-2" />
              </div>

              <!-- Email -->
              <div>
                <label :class="labelCls">Địa chỉ Email <span class="text-red-400">*</span></label>
                <div class="relative group">
                  <EnvelopeIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/20 group-focus-within:text-brand-400 transition-colors" />
                  <input v-model="form.email" type="email" :class="[inputCls, 'pl-12']" placeholder="email@congty.com" :disabled="isEditing" required />
                </div>
                <InputError :message="form.errors.email" class="mt-2" />
              </div>

              <!-- Password -->
              <div v-if="!isEditing" class="md:col-span-2">
                <label :class="labelCls">Mật khẩu khởi tạo <span class="text-red-400">*</span></label>
                <div class="relative group">
                  <KeyIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/20 group-focus-within:text-brand-400 transition-colors" />
                  <input v-model="form.password" type="password" :class="[inputCls, 'pl-12']" placeholder="••••••••" required />
                </div>
                <InputError :message="form.errors.password" class="mt-2" />
              </div>

              <!-- Permissions Grid -->
              <div class="md:col-span-2 space-y-4">
                <label :class="labelCls">Phân quyền chức năng nghiệp vụ</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-black/40 p-6 rounded-[2rem] border border-glass shadow-inner">
                  <button 
                    v-for="(label, key) in availablePermissions" 
                    :key="key"
                    type="button"
                    @click="togglePerm(key)"
                    class="flex items-center gap-3 p-4 rounded-2xl border transition-all text-left group"
                    :class="form.permissions.includes(key) ? 'bg-brand-500/10 border-brand-500/40' : 'bg-white/5 border-white/5 hover:border-white/20'"
                  >
                    <div class="w-6 h-6 rounded-lg border flex items-center justify-center transition-colors"
                      :class="form.permissions.includes(key) ? 'bg-brand-500 border-brand-500 text-cosmic-950 shadow-[0_0_15px_rgba(255,165,0,0.5)]' : 'border-white/20'">
                      <CheckCircleIcon v-if="form.permissions.includes(key)" class="w-4 h-4" />
                    </div>
                    <span class="text-xs font-black transition-colors" :class="form.permissions.includes(key) ? 'text-white' : 'text-white/40 group-hover:text-white/60'">
                      {{ label }}
                    </span>
                  </button>
                </div>
                <InputError :message="form.errors.permissions" class="mt-2" />
              </div>
            </div>

            <div class="mt-10 pt-8 border-t border-white/5 flex items-center justify-end gap-6">
              <button type="button" @click="closeModal" class="text-xs font-black text-white/40 hover:text-white transition-colors uppercase tracking-widest">Hủy bỏ</button>
              <button 
                :disabled="form.processing"
                class="bg-brand-500 text-cosmic-950 px-12 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-brand-900/30 hover:bg-brand-400 active:scale-95 transition-all disabled:opacity-50">
              <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
                {{ form.processing ? 'Đang thực hiện...' : (isEditing ? 'LƯU THAY ĐỔI' : 'TẠO TÀI KHOẢN') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Modal>
</template>
