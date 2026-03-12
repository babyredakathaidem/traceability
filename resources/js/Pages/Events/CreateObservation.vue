<script setup>
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  batches: Array,
  locations: Array,
  certificates: Array,
})

const form = useForm({
  cte_code: '',
  event_time: new Date().toISOString().slice(0, 16),
  who_name: '',
  trace_location_id: '',
  kde_data: {},
  note: '',
  input_batch_ids: [],
  why_reason: '',
  has_certification: false,
  certification: {
    certificate_id: '',
    result: 'pass',
    reference_no: '',
    issued_date: '',
    expiry_date: '',
  }
})

function submit() {
  form.post(route('observation-events.store'))
}

// FIX màu bạc & lỏ: Ép màu nền Deep Dark và thêm shadow inner
const inputCls = 'w-full bg-[#0d1117] border border-white/10 rounded-xl px-4 py-3 text-sm text-white/90 placeholder:text-white/20 outline-none focus:border-sky-500/50 focus:ring-1 focus:ring-sky-500/20 transition-all shadow-inner appearance-none'
const labelCls = 'block text-xs font-bold text-white/40 uppercase tracking-widest mb-2'
</script>

<template>
  <Head title="Ghi nhận quan sát" />

  <div class="max-w-3xl mx-auto py-8 px-4 font-sans text-white">
    <div class="mb-8 flex items-center justify-between" data-aos="fade-down">
      <div>
        <h1 class="text-2xl font-black tracking-tight">Ghi nhận quan sát</h1>
        <p class="text-white/40 text-sm mt-1 font-medium">Ghi dữ liệu trạng thái lô hàng (Gieo hạt, chăm sóc, kiểm định...)</p>
      </div>
      <Link :href="route('events.index')" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/30 hover:text-white hover:bg-white/10 transition-all text-xl">✕</Link>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      
      <!-- Section 1: Thông tin sự kiện -->
      <div class="p-6 rounded-3xl border border-white/5 bg-white/5 space-y-5 shadow-2xl" data-aos="fade-up">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-sky-500/20 flex items-center justify-center text-sky-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h2 class="text-sm font-black uppercase tracking-widest text-white/90">Thông tin sự kiện</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="relative">
            <label :class="labelCls">Loại sự kiện (CTE) *</label>
            <div class="relative group">
              <select v-model="form.cte_code" :class="inputCls" required>
                <option value="" class="bg-[#0d1117]">/— Chọn công đoạn —</option>
                <option value="planting" class="bg-[#0d1117]">🌱 Gieo hạt / Trồng</option>
                <option value="growing" class="bg-[#0d1117]">🌿 Bón phân / Chăm sóc</option>
                <option value="spraying" class="bg-[#0d1117]">💦 Phun thuốc</option>
                <option value="storage" class="bg-[#0d1117]">🏭 Lưu kho</option>
                <option value="inspection" class="bg-[#0d1117]">🔍 Kiểm định / Test mẫu</option>
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-white/30 group-focus-within:text-sky-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
            </div>
          </div>
          <div>
            <label :class="labelCls">Thời gian thực hiện *</label>
            <input v-model="form.event_time" type="datetime-local" :class="inputCls" required />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label :class="labelCls">Người thực hiện *</label>
            <input v-model="form.who_name" type="text" :class="inputCls" placeholder="Tên người ghi nhận" required />
          </div>
          <div class="relative">
            <label :class="labelCls">Địa điểm (GLN)</label>
            <div class="relative group">
              <select v-model="form.trace_location_id" :class="inputCls">
                <option value="" class="bg-[#0d1117]">/— Chọn địa điểm —</option>
                <option v-for="loc in locations" :key="loc.id" :value="loc.id" class="bg-[#0d1117]">
                  {{ loc.name }} ({{ loc.gln }})
                </option>
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-white/30 group-focus-within:text-sky-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2: Lô hàng tác động -->
      <div class="p-6 rounded-3xl border border-white/5 bg-white/5 space-y-5 shadow-2xl" data-aos="fade-up" data-aos-delay="100">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center text-amber-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
          </div>
          <h2 class="text-sm font-black uppercase tracking-widest text-white/90">Lô hàng tác động</h2>
        </div>

        <div>
          <label :class="labelCls">Chọn (các) lô hàng thực hiện công đoạn *</label>
          <div class="grid grid-cols-1 gap-2 max-h-60 overflow-y-auto p-3 bg-black/40 rounded-2xl border border-white/5 shadow-inner scrollbar-thin scrollbar-thumb-white/10">
            <label v-for="b in batches" :key="b.id" 
              class="flex items-center gap-4 p-4 rounded-xl border transition-all cursor-pointer group"
              :class="form.input_batch_ids.includes(b.id) ? 'bg-sky-500/10 border-sky-500/40' : 'bg-white/5 border-white/5 hover:bg-white/10'">
              <div class="relative flex items-center">
                <input type="checkbox" :value="b.id" v-model="form.input_batch_ids" 
                  class="w-5 h-5 rounded border-white/20 bg-transparent text-sky-500 focus:ring-sky-500/20 transition-all cursor-pointer" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-xs font-mono font-black tracking-tighter" :class="form.input_batch_ids.includes(b.id) ? 'text-sky-300' : 'text-white/70'">{{ b.code }}</div>
                <div class="text-[10px] uppercase font-bold opacity-40 mt-0.5 truncate">{{ b.product_name }} · {{ b.current_quantity }} {{ b.unit }}</div>
              </div>
              <div v-if="form.input_batch_ids.includes(b.id)" class="text-sky-400">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- Section 3: Chứng nhận -->
      <div class="p-6 rounded-3xl border border-emerald-500/20 bg-emerald-500/5 space-y-5 relative overflow-hidden shadow-2xl" data-aos="fade-up" data-aos-delay="200">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-500/10 blur-3xl rounded-full pointer-events-none"></div>
        
        <div class="flex items-center justify-between relative z-10">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
              </svg>
            </div>
            <h2 class="text-sm font-black uppercase tracking-widest text-white/90">Chứng nhận đi kèm</h2>
          </div>
          <button type="button" @click="form.has_certification = !form.has_certification"
            class="w-12 h-6 rounded-full transition-all relative border border-white/10 shadow-lg"
            :class="form.has_certification ? 'bg-emerald-500' : 'bg-white/5'">
            <div class="absolute top-1 w-4 h-4 rounded-full bg-white shadow-sm transition-all" :style="{ left: form.has_certification ? '26px' : '4px' }"></div>
          </button>
        </div>

        <div v-if="form.has_certification" class="space-y-5 relative z-10 pt-3 animate-in fade-in slide-in-from-top-2 duration-500">
          <div class="relative">
            <label :class="labelCls">Loại chứng chỉ *</label>
            <div class="relative group">
              <select v-model="form.certification.certificate_id" :class="inputCls">
                <option value="" class="bg-[#0d1117]">/— Chọn chứng chỉ —</option>
                <option v-for="c in certificates" :key="c.id" :value="c.id" class="bg-[#0d1117]">
                  {{ c.name }} ({{ c.organization }})
                </option>
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-white/30 group-focus-within:text-emerald-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="relative">
              <label :class="labelCls">Kết quả đánh giá *</label>
              <div class="relative group">
                <select v-model="form.certification.result" :class="inputCls">
                  <option value="pass" class="bg-[#0d1117]">Đạt (Pass)</option>
                  <option value="fail" class="bg-[#0d1117]">Không đạt (Fail)</option>
                  <option value="conditional" class="bg-[#0d1117]">Có điều kiện</option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-white/30 group-focus-within:text-emerald-500 transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
              </div>
            </div>
            <div>
              <label :class="labelCls">Số phiếu kiểm nghiệm / AI (400)</label>
              <input v-model="form.certification.reference_no" type="text" :class="inputCls" placeholder="Ví dụ: (400) KN-2026..." />
            </div>
          </div>
        </div>
      </div>

      <!-- Section 4: Lý do & Ghi chú -->
      <div class="p-6 rounded-3xl border border-white/5 bg-white/5 space-y-5 shadow-2xl" data-aos="fade-up" data-aos-delay="300">
        <div>
          <label :class="labelCls">Lý do thực hiện / Mục đích</label>
          <input v-model="form.why_reason" type="text" :class="inputCls" placeholder="Ví dụ: Kiểm tra chất lượng trước thu hoạch" />
        </div>
        <div>
          <label :class="labelCls">Ghi chú chi tiết</label>
          <textarea v-model="form.note" :class="inputCls" rows="3" placeholder="Ghi nhận thêm các thông tin ngoại lệ nếu có..."></textarea>
        </div>
      </div>

      <div class="flex items-center justify-end gap-4 pt-4 pb-12">
        <Link :href="route('events.index')" class="px-8 py-3 rounded-2xl border border-white/10 text-white/40 font-black text-xs uppercase tracking-widest hover:bg-white/5 transition-all">Hủy</Link>
        <button type="submit" :disabled="form.processing"
          class="px-12 py-3 rounded-2xl bg-sky-500 hover:bg-sky-400 text-black font-black text-xs uppercase tracking-widest shadow-2xl shadow-sky-500/20 transition-all active:scale-95 disabled:opacity-50">
          {{ form.processing ? 'ĐANG LƯU...' : 'XÁC NHẬN LƯU SỰ KIỆN' }}
        </button>
      </div>

    </form>
  </div>
</template>

<style>
/* Tùy chỉnh thanh cuộn cho mượt mà */
.scrollbar-thin::-webkit-scrollbar { width: 4px; }
.scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
.scrollbar-thin::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
</style>
