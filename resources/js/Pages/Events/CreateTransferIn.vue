<script setup>
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import TraceLocationPicker from '@/Components/events/TraceLocationPicker.vue'

const props = defineProps({
  locations: Array,   // kept for compat — TraceLocationPicker tự fetch từ API
  products:  Array,
})

const form = useForm({
  external_party_name:    '',
  external_ref:           '',
  event_time:             new Date().toISOString().slice(0, 16),
  who_name:               '',
  trace_location_id:      null,   // FIX: null thay vì '' để tránh validation conflict
  kde_data:               {},
  note:                   '',
  gs1_document_ref:       '',
  output_product_id:      null,   // FIX: null thay vì ''
  output_quantity:        '',
  output_unit:            '',
  output_batch_type:      'raw_material',
  output_production_date: '',
  output_expiry_date:     '',
})

function submit() {
  form.post(route('events.transfer.in'))
}

const inputCls = 'w-full bg-[#0d1117] border border-white/10 rounded-xl px-4 py-3 text-sm text-white/90 placeholder:text-white/20 outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/20 transition-all shadow-inner'
const labelCls = 'block text-xs font-bold text-white/40 uppercase tracking-widest mb-2'
</script>

<template>
  <Head title="Nhập hàng từ bên ngoài" />

  <div class="max-w-3xl mx-auto py-8 px-4">
    <div class="mb-8 flex items-center justify-between" data-aos="fade-down">
      <div>
        <h1 class="text-2xl font-black text-white tracking-tight">Nhập hàng mới</h1>
        <p class="text-white/40 text-sm mt-1">Ghi nhận sự kiện nhập hàng từ bên ngoài hệ thống.</p>
      </div>
      <Link :href="route('dashboard')" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/30 hover:text-white hover:bg-white/10 transition-all text-xl">✕</Link>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      
      <!-- Section 1: Thông tin nguồn gốc -->
      <div class="p-6 rounded-3xl border border-white/5 bg-white/5 space-y-5 shadow-xl" data-aos="fade-up">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-8 h-8 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <h2 class="text-sm font-black text-white/80 uppercase tracking-widest">Thông tin nguồn cung</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="labelCls">Nhà cung cấp (Bên ngoài) *</label>
            <input v-model="form.external_party_name" type="text" :class="inputCls" placeholder="Ví dụ: Công ty Giống cây trồng..." required />
            <div v-if="form.errors.external_party_name" class="text-red-400 text-xs mt-1">{{ form.errors.external_party_name }}</div>
          </div>
          <div>
            <label :class="labelCls">Số hóa đơn / Hợp đồng</label>
            <input v-model="form.external_ref" type="text" :class="inputCls" placeholder="Ví dụ: HD-2026-001" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="labelCls">Thời gian nhập *</label>
            <input v-model="form.event_time" type="datetime-local" :class="inputCls" required />
            <div v-if="form.errors.event_time" class="text-red-400 text-xs mt-1">{{ form.errors.event_time }}</div>
          </div>
          <div>
            <label :class="labelCls">Người tiếp nhận *</label>
            <input v-model="form.who_name" type="text" :class="inputCls" placeholder="Tên cán bộ nhập kho" required />
            <div v-if="form.errors.who_name" class="text-red-400 text-xs mt-1">{{ form.errors.who_name }}</div>
          </div>
        </div>

        <!--
          TraceLocationPicker:
          - v-model nhận/trả trace_location_id (Number | null)
          - Tự fetch locations từ GET /api/trace-locations (auth + tenant)
          - Dùng Teleport + getBoundingClientRect → không bị overflow clip
        -->
        <TraceLocationPicker
          v-model="form.trace_location_id"
          label="Kho / Địa điểm tiếp nhận"
        />
        <div v-if="form.errors.trace_location_id" class="text-red-400 text-xs -mt-3">
          {{ form.errors.trace_location_id }}
        </div>
      </div>

      <!-- Section 2: Tạo lô hàng mới -->
      <!--
        FIX: đổi overflow-hidden → overflow-visible
        Nếu giữ overflow-hidden sẽ clip Teleport bị ảnh hưởng ở Safari/Firefox
        (Teleport dùng position:fixed nên thực ra không bị clip, nhưng blur glow
        sẽ bị cut nếu để hidden)
      -->
      <div class="p-6 rounded-3xl border border-emerald-500/20 bg-emerald-500/5 space-y-5 relative overflow-visible shadow-xl" data-aos="fade-up" data-aos-delay="100">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-500/10 blur-3xl rounded-full pointer-events-none"></div>
        
        <div class="flex items-center gap-3 mb-2 relative z-10">
          <div class="w-8 h-8 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
          </div>
          <h2 class="text-sm font-black text-white/80 uppercase tracking-widest">Khởi tạo lô hàng nguyên liệu</h2>
        </div>

        <div class="relative z-10 space-y-4">
          <div>
            <label :class="labelCls">Sản phẩm *</label>
            <select v-model.number="form.output_product_id" :class="inputCls" required>
              <option :value="null" class="bg-[#0d1117]">/— Chọn sản phẩm trong danh mục —</option>
              <option v-for="p in products" :key="p.id" :value="p.id" class="bg-[#0d1117]">
                {{ p.name }} ({{ p.gtin ?? 'Chưa có GTIN' }})
              </option>
            </select>
            <div v-if="form.errors.output_product_id" class="text-red-400 text-xs mt-1">{{ form.errors.output_product_id }}</div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label :class="labelCls">Số lượng nhập *</label>
              <input v-model="form.output_quantity" type="number" step="0.001" :class="inputCls" placeholder="0.000" required />
              <div v-if="form.errors.output_quantity" class="text-red-400 text-xs mt-1">{{ form.errors.output_quantity }}</div>
            </div>
            <div>
              <label :class="labelCls">Đơn vị tính *</label>
              <input v-model="form.output_unit" type="text" :class="inputCls" placeholder="tấn, kg, bao..." required />
              <div v-if="form.errors.output_unit" class="text-red-400 text-xs mt-1">{{ form.errors.output_unit }}</div>
            </div>
          </div>

          <div>
            <label :class="labelCls">Loại lô hàng</label>
            <div class="flex gap-2">
              <button v-for="t in [['raw_material','Nguyên liệu'], ['wip','Bán TP'], ['finished','Thành phẩm']]" 
                :key="t[0]" type="button" @click="form.output_batch_type = t[0]"
                class="flex-1 py-2.5 rounded-xl border text-[9px] font-black uppercase tracking-tighter transition-all"
                :class="form.output_batch_type === t[0] ? 'bg-emerald-500 border-emerald-500 text-black shadow-lg shadow-emerald-500/20' : 'border-white/10 bg-white/5 text-white/40 hover:bg-white/10'">
                {{ t[1] }}
              </button>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label :class="labelCls">Ngày sản xuất</label>
              <input v-model="form.output_production_date" type="date" :class="inputCls" />
            </div>
            <div>
              <label :class="labelCls">Hạn sử dụng</label>
              <input v-model="form.output_expiry_date" type="date" :class="inputCls" />
            </div>
          </div>
        </div>
      </div>

      <!-- Section 3: Chứng từ GS1 -->
      <div class="p-6 rounded-3xl border border-white/5 bg-white/5 space-y-4 shadow-xl" data-aos="fade-up" data-aos-delay="200">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-white/60">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <h2 class="text-sm font-black text-white/80 uppercase tracking-widest">Chứng từ & Ghi chú</h2>
        </div>

        <div>
          <label :class="labelCls">Mã chứng từ GS1 (AI 400)</label>
          <input v-model="form.gs1_document_ref" type="text" :class="inputCls" placeholder="(400) ABC-123..." />
        </div>
        <div>
          <label :class="labelCls">Ghi chú thêm</label>
          <textarea v-model="form.note" :class="inputCls" rows="3" placeholder="Ví dụ: Kiểm tra cảm quan đạt yêu cầu..."></textarea>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 pt-4 pb-12">
        <Link :href="route('dashboard')" class="px-8 py-3 rounded-2xl border border-white/10 text-white/40 font-black text-xs uppercase tracking-widest hover:bg-white/5 transition">Hủy</Link>
        <button type="submit" :disabled="form.processing"
          class="px-10 py-3 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-black font-black text-xs uppercase tracking-widest shadow-2xl shadow-emerald-500/20 transition-all active:scale-95 disabled:opacity-50">
          {{ form.processing ? 'ĐANG LƯU...' : 'XÁC NHẬN NHẬP HÀNG' }}
        </button>
      </div>

    </form>
  </div>
</template>