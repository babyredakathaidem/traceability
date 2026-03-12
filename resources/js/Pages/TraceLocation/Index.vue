<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  locations: Object, // Đổi từ Array sang Object vì dùng paginate()
  ai_labels: Object, // Đổi từ aiLabels sang ai_labels cho khớp Controller
})

const showAddForm = ref(false)
const editingId  = ref(null)

const form = useForm({
  name: '',
  ai_type: '416', // Mặc định là vùng trồng
  gln: '',
  code: '',
  province: '',
  district: '',
  address_detail: '',
  lat: null,
  lng: null,
  area_ha: null,
  farm_code: '',
  status: 'active',
  note: '',
})

function openAdd() {
  editingId.value = null
  form.reset()
  showAddForm.value = true
}

function openEdit(loc) {
  editingId.value = loc.id
  form.name           = loc.name
  form.ai_type        = loc.ai_type
  form.gln            = loc.gln
  form.code           = loc.code
  form.province       = loc.province
  form.district       = loc.district
  form.address_detail = loc.address_detail
  form.lat            = loc.lat
  form.lng            = loc.lng
  form.area_ha        = loc.area_ha
  form.farm_code      = loc.farm_code
  form.status         = loc.status
  form.note           = loc.note
  showAddForm.value   = true
}

function submit() {
  if (editingId.value) {
    form.put(route('trace-locations.update', editingId.value), {
      onSuccess: () => { showAddForm.value = false }
    })
  } else {
    form.post(route('trace-locations.store'), {
      onSuccess: () => { showAddForm.value = false }
    })
  }
}

function deleteLocation(id) {
  if (confirm('Bạn có chắc muốn xóa địa điểm này?')) {
    form.delete(route('trace-locations.destroy', id))
  }
}

// ── GPS ───────────────────────────────────────────────────
const gpsLoading = ref(false)
function getGps() {
  gpsLoading.value = true
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      form.lat = pos.coords.latitude
      form.lng = pos.coords.longitude
      gpsLoading.value = false
    },
    (err) => { alert('Lỗi GPS: ' + err.message); gpsLoading.value = false },
    { enableHighAccuracy: true }
  )
}

const inputCls = 'w-full bg-[#0d1117] border border-white/10 rounded-xl px-4 py-3 text-sm text-white/90 placeholder:text-white/20 outline-none focus:border-brand-500/50 transition-all appearance-none'
const labelCls = 'block text-xs font-black text-white/40 uppercase tracking-widest mb-2'
</script>

<template>
  <Head title="Quản lý địa điểm truy vết" />

  <div class="space-y-6 font-sans">
    
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4" data-aos="fade-right">
      <div>
        <div class="text-brand-400 text-[10px] font-black uppercase tracking-[0.2em]">Master Data</div>
        <h1 class="text-2xl font-black text-white tracking-tight">Địa điểm truy vết (GLN)</h1>
        <p class="text-white/30 text-xs mt-0.5">Quản lý ruộng lúa, kho bãi, nhà máy theo chuẩn GS1.</p>
      </div>
      <button @click="openAdd" class="px-6 py-3 rounded-2xl bg-brand-500 hover:bg-brand-400 text-black font-black text-xs uppercase tracking-widest shadow-xl shadow-brand-500/20 transition-all active:scale-95">
        + Thêm địa điểm mới
      </button>
    </div>

    <!-- Empty state -->
    <div v-if="locations.data.length === 0 && !showAddForm" class="py-20 text-center bg-white/5 rounded-3xl border border-white/5 border-dashed" data-aos="fade-up">
      <div class="text-5xl mb-4">📍</div>
      <div class="text-white/40 font-bold uppercase tracking-widest text-sm">Chưa có địa điểm nào được khai báo</div>
      <p class="text-white/20 text-xs mt-2">Hãy bắt đầu bằng việc thêm Ruộng lúa hoặc Kho hàng đầu tiên.</p>
    </div>

    <!-- Grid danh sách -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" v-auto-animate>
      <div v-for="loc in locations.data" :key="loc.id" 
        class="group relative p-6 rounded-3xl border border-white/5 bg-white/5 hover:bg-white/10 transition-all duration-300 shadow-xl overflow-hidden"
        data-aos="fade-up">
        
        <!-- Glow effect -->
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-brand-500/5 blur-3xl rounded-full group-hover:bg-brand-500/10 transition-colors"></div>

        <div class="flex items-start justify-between mb-4">
          <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-2xl">
            {{ loc.ai_type === '416' ? '🌱' : loc.ai_type === '414' ? '🏭' : '🏠' }}
          </div>
          <div class="flex gap-1">
            <button @click="openEdit(loc)" class="p-2 rounded-lg bg-white/5 hover:bg-brand-500/20 hover:text-brand-400 transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
            </button>
            <button @click="deleteLocation(loc.id)" class="p-2 rounded-lg bg-white/5 hover:bg-red-500/20 hover:text-red-400 transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v2m3 4s-4 9 5 13" /></svg>
            </button>
          </div>
        </div>

        <div class="space-y-1">
          <h3 class="text-base font-black text-white/90 truncate">{{ loc.name }}</h3>
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-black text-brand-400 uppercase tracking-tighter bg-brand-500/10 px-1.5 py-0.5 rounded border border-brand-500/20">GLN: {{ loc.gln }}</span>
            <span class="text-[9px] font-bold text-white/30 uppercase">{{ ai_labels[loc.ai_type] }}</span>
          </div>
        </div>

        <div class="mt-4 pt-4 border-t border-white/5 space-y-2">
          <div class="flex items-start gap-2 text-xs text-white/40">
            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="line-clamp-2 italic">{{ loc.address_detail }}, {{ loc.province }}</span>
          </div>
          <div v-if="loc.lat" class="text-[10px] font-mono text-white/20">
            GPS: {{ loc.lat.toFixed(5) }}, {{ loc.lng.toFixed(5) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showAddForm" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showAddForm = false">
          <div class="absolute inset-0 bg-black/80 backdrop-blur-md"></div>
          <div class="relative w-full max-w-2xl max-h-[90vh] flex flex-col rounded-[2.5rem] border border-white/10 bg-[#0d1117] shadow-2xl overflow-hidden">
            
            <div class="px-8 py-6 border-b border-white/5 flex items-center justify-between shrink-0">
              <div>
                <h2 class="text-xl font-black text-white tracking-tight">{{ editingId ? 'Sửa địa điểm' : 'Khai báo địa điểm mới' }}</h2>
                <p class="text-white/30 text-xs">Cung cấp thông tin tọa độ và mã định danh GS1.</p>
              </div>
              <button @click="showAddForm = false" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center text-white/30 hover:text-white transition">✕</button>
            </div>

            <div class="overflow-y-auto flex-1 p-8 space-y-6 scrollbar-thin">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label :class="labelCls">Tên gợi nhớ *</label>
                  <input v-model="form.name" type="text" :class="inputCls" placeholder="Ví dụ: Ruộng ông Bảy..." required />
                </div>
                <div class="relative">
                  <label :class="labelCls">Loại địa điểm *</label>
                  <div class="relative group">
                    <select v-model="form.ai_type" :class="inputCls" required>
                      <option v-for="(label, ai) in ai_labels" :key="ai" :value="ai" class="bg-[#0d1117]">
                        ({{ ai }}) {{ label }}
                      </option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-white/30 group-focus-within:text-brand-500 transition-colors">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label :class="labelCls">Mã GLN (13 số) *</label>
                  <input v-model="form.gln" type="text" :class="inputCls" placeholder="893..." required />
                </div>
                <div>
                  <label :class="labelCls">Mã nội bộ (Code)</label>
                  <input v-model="form.code" type="text" :class="inputCls" placeholder="R-001..." />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label :class="labelCls">Tỉnh/Thành phố</label>
                  <input v-model="form.province" type="text" :class="inputCls" />
                </div>
                <div>
                  <label :class="labelCls">Quận/Huyện</label>
                  <input v-model="form.district" type="text" :class="inputCls" />
                </div>
              </div>

              <div>
                <label :class="labelCls">Địa chỉ cụ thể</label>
                <input v-model="form.address_detail" type="text" :class="inputCls" placeholder="Số nhà, ấp, xã..." />
              </div>

              <div class="p-6 rounded-3xl bg-brand-500/5 border border-brand-500/20 space-y-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="text-xs font-black uppercase text-brand-400 tracking-widest">Tọa độ GPS</span>
                  </div>
                  <button type="button" @click="getGps" :disabled="gpsLoading" class="text-[10px] font-black uppercase text-brand-400 hover:underline">
                    {{ gpsLoading ? 'Đang lấy...' : '📍 Lấy vị trí hiện tại' }}
                  </button>
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="text-[9px] font-bold text-white/30 uppercase mb-1 block">Latitude</label>
                    <input v-model="form.lat" type="number" step="0.0000001" :class="inputCls" class="!py-2 text-xs" />
                  </div>
                  <div>
                    <label class="text-[9px] font-bold text-white/30 uppercase mb-1 block">Longitude</label>
                    <input v-model="form.lng" type="number" step="0.0000001" :class="inputCls" class="!py-2 text-xs" />
                  </div>
                </div>
              </div>

              <div>
                <label :class="labelCls">Ghi chú</label>
                <textarea v-model="form.note" :class="inputCls" rows="2"></textarea>
              </div>
            </div>

            <div class="px-8 py-6 border-t border-white/5 flex justify-end gap-3 shrink-0">
              <button @click="showAddForm = false" class="px-6 py-3 rounded-2xl border border-white/10 text-white/40 font-bold text-xs uppercase tracking-widest hover:bg-white/5 transition">Hủy</button>
              <button @click="submit" :disabled="form.processing" class="px-10 py-3 rounded-2xl bg-brand-500 hover:bg-brand-400 text-black font-black text-xs uppercase tracking-widest shadow-2xl shadow-brand-500/20 transition-all disabled:opacity-50">
                {{ form.processing ? 'ĐANG LƯU...' : 'LƯU ĐỊA ĐIỂM' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity .3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
</style>
