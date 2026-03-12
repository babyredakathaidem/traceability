<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  batches: Array,
  locations: Array,
  products: Array,
})

const form = useForm({
  cte_code: '',
  event_time: new Date().toISOString().slice(0, 16),
  who_name: '',
  trace_location_id: '',
  kde_data: {},
  note: '',
  // Input: mảng {id, quantity, unit}
  input_batches: [],
  // Output
  output_product_id: '',
  output_quantity: '',
  output_unit: '',
  output_batch_type: 'wip',
  output_production_date: '',
  output_expiry_date: '',
})

function addInputBatch(batch) {
  if (form.input_batches.find(b => b.id === batch.id)) return
  form.input_batches.push({
    id: batch.id,
    code: batch.code,
    product_name: batch.product_name,
    quantity: batch.current_quantity,
    unit: batch.unit
  })
}

function removeInputBatch(id) {
  form.input_batches = form.input_batches.filter(b => b.id !== id)
}

function submit() {
  form.post(route('transformation-events.store'))
}

const inputCls = 'w-full bg-[#0d1117] border border-white/10 rounded-xl px-4 py-3 text-sm text-white/90 placeholder:text-white/20 outline-none focus:border-brand-500/50 focus:ring-1 focus:ring-brand-500/20 transition-all shadow-inner appearance-none'
const labelCls = 'block text-xs font-bold text-white/40 uppercase tracking-widest mb-2'
</script>

<template>
  <Head title="Chế biến / Thu hoạch" />

  <div class="max-w-4xl mx-auto py-8 px-4 font-sans text-white">
    <div class="mb-8 flex items-center justify-between" data-aos="fade-down">
      <div>
        <h1 class="text-2xl font-black tracking-tight">Chế biến / Thu hoạch</h1>
        <p class="text-white/40 text-sm mt-1 font-medium italic">Giai đoạn biến đổi: Tiêu thụ lô cũ → Đẻ ra lô mới.</p>
      </div>
      <Link :href="route('batches.index')" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/30 hover:text-white hover:bg-white/10 transition-all text-xl">✕</Link>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      
      <!-- Grid 2 cột chính -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- CỘT TRÁI: ĐẦU VÀO (INPUT) -->
        <div class="space-y-6">
          <div class="p-6 rounded-3xl border border-white/5 bg-white/5 space-y-5 shadow-2xl h-full" data-aos="fade-right">
            <div class="flex items-center gap-3 mb-2">
              <div class="w-10 h-10 rounded-xl bg-orange-500/20 flex items-center justify-center text-orange-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </div>
              <h2 class="text-sm font-black uppercase tracking-widest text-white/90">Nguyên liệu đầu vào</h2>
            </div>

            <div class="space-y-4">
              <div class="relative group">
                <label :class="labelCls">Thêm lô hàng vào danh sách *</label>
                <div class="relative">
                  <select @change="e => addInputBatch(batches.find(b => b.id == e.target.value))" :class="inputCls">
                    <option value="" class="bg-[#0d1117]">/— Chọn lô để chế biến —</option>
                    <option v-for="b in batches" :key="b.id" :value="b.id" class="bg-[#0d1117]">
                      {{ b.code }} ({{ b.product_name }})
                    </option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-white/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </div>
                </div>
              </div>

              <div class="space-y-2 max-h-[400px] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-white/10">
                <div v-if="form.input_batches.length === 0" class="py-12 text-center border-2 border-dashed border-white/5 rounded-2xl text-white/20 text-xs font-bold uppercase tracking-widest">
                  Chưa chọn nguyên liệu
                </div>
                <div v-for="item in form.input_batches" :key="item.id" 
                  class="p-4 rounded-2xl bg-black/40 border border-white/10 flex flex-col gap-3 animate-in slide-in-from-left-2 duration-300">
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <div class="text-xs font-mono font-black text-brand-300 tracking-tighter">{{ item.code }}</div>
                      <div class="text-[10px] uppercase font-bold text-white/40 truncate">{{ item.product_name }}</div>
                    </div>
                    <button type="button" @click="removeInputBatch(item.id)" class="text-white/20 hover:text-red-400 transition-colors text-lg">✕</button>
                  </div>
                  <div class="flex gap-2">
                    <div class="flex-1">
                      <input v-model="item.quantity" type="number" step="0.001" :class="inputCls" class="!py-2 !px-3 text-xs" placeholder="SL tiêu thụ" />
                    </div>
                    <div class="w-20">
                      <input v-model="item.unit" type="text" :class="inputCls" class="!py-2 !px-3 text-xs" placeholder="Đơn vị" />
                    </div>
                  </div>
                </div>
              </div>
              <p v-if="form.input_batches.length" class="text-[10px] text-orange-400/70 font-bold uppercase">* Cảnh báo: Các lô này sẽ bị đánh dấu "Consumed" sau khi lưu.</p>
            </div>
          </div>
        </div>

        <!-- CỘT PHẢI: ĐẦU RA (OUTPUT) -->
        <div class="space-y-6">
          <div class="p-6 rounded-3xl border border-brand-500/20 bg-brand-500/5 space-y-5 shadow-2xl relative overflow-hidden h-full" data-aos="fade-left">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-brand-500/10 blur-3xl rounded-full pointer-events-none"></div>
            
            <div class="flex items-center gap-3 mb-2 relative z-10">
              <div class="w-10 h-10 rounded-xl bg-brand-500/20 flex items-center justify-center text-brand-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
              </div>
              <h2 class="text-sm font-black uppercase tracking-widest text-white/90">Sản phẩm đầu ra</h2>
            </div>

            <div class="relative z-10 space-y-4">
              <div class="relative group">
                <label :class="labelCls">Sản phẩm mới *</label>
                <div class="relative">
                  <select v-model="form.output_product_id" :class="inputCls" required>
                    <option value="" class="bg-[#0d1117]">/— Chọn sản phẩm thành phẩm —</option>
                    <option v-for="p in products" :key="p.id" :value="p.id" class="bg-[#0d1117]">
                      {{ p.name }} ({{ p.gtin ?? 'N/A' }})
                    </option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-white/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label :class="labelCls">Số lượng ra *</label>
                  <input v-model="form.output_quantity" type="number" step="0.001" :class="inputCls" placeholder="0.000" required />
                </div>
                <div>
                  <label :class="labelCls">Đơn vị tính *</label>
                  <input v-model="form.output_unit" type="text" :class="inputCls" placeholder="kg, bao..." required />
                </div>
              </div>

              <div class="relative group">
                <label :class="labelCls">Loại lô hàng mới</label>
                <div class="relative">
                  <select v-model="form.output_batch_type" :class="inputCls">
                    <option value="raw_material" class="bg-[#0d1117]">Nguyên liệu</option>
                    <option value="wip" class="bg-[#0d1117]">Bán thành phẩm</option>
                    <option value="finished" class="bg-[#0d1117]">Thành phẩm</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-white/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </div>
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
        </div>
      </div>

      <!-- Section 3: Thông tin sự kiện biến đổi -->
      <div class="p-6 rounded-3xl border border-white/5 bg-white/5 space-y-5 shadow-xl" data-aos="fade-up">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-brand-500/20 flex items-center justify-center text-brand-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h2 class="text-sm font-black uppercase tracking-widest text-white/90">Chi tiết công đoạn</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="relative group">
            <label :class="labelCls">Công đoạn thực hiện *</label>
            <div class="relative">
              <select v-model="form.cte_code" :class="inputCls" required>
                <option value="" class="bg-[#0d1117]">/— Chọn quy trình —</option>
                <option value="harvesting" class="bg-[#0d1117]">🚜 Thu hoạch</option>
                <option value="milling" class="bg-[#0d1117]">⚙️ Xay xát / Ép</option>
                <option value="processing" class="bg-[#0d1117]">🏭 Chế biến</option>
                <option value="packaging" class="bg-[#0d1117]">📦 Đóng gói</option>
                <option value="split" class="bg-[#0d1117]">✂️ Tách lô</option>
                <option value="merge" class="bg-[#0d1117]">🔀 Gộp lô</option>
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-white/30">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
            </div>
          </div>
          <div>
            <label :class="labelCls">Thời gian sự kiện *</label>
            <input v-model="form.event_time" type="datetime-local" :class="inputCls" required />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label :class="labelCls">Người chịu trách nhiệm *</label>
            <input v-model="form.who_name" type="text" :class="inputCls" placeholder="Tên cán bộ kỹ thuật" required />
          </div>
          <div class="relative group">
            <label :class="labelCls">Địa điểm thực hiện</label>
            <div class="relative">
              <select v-model="form.trace_location_id" :class="inputCls">
                <option value="" class="bg-[#0d1117]">/— Chọn nhà máy / ruộng —</option>
                <option v-for="loc in locations" :key="loc.id" :value="loc.id" class="bg-[#0d1117]">
                  {{ loc.name }} ({{ loc.gln }})
                </option>
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-white/30">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
            </div>
          </div>
        </div>

        <div>
          <label :class="labelCls">Ghi chú & Mô tả quy trình</label>
          <textarea v-model="form.note" :class="inputCls" rows="3" placeholder="Nhập chi tiết về cách chế biến, nhiệt độ, thông số kỹ thuật..."></textarea>
        </div>
      </div>

      <div class="flex items-center justify-end gap-4 pt-4 pb-12">
        <Link :href="route('batches.index')" class="px-8 py-3 rounded-2xl border border-white/10 text-white/40 font-black text-xs uppercase tracking-widest hover:bg-white/5 transition-all">Hủy</Link>
        <button type="submit" :disabled="form.processing"
          class="px-12 py-3 rounded-2xl bg-brand-500 hover:bg-brand-400 text-black font-black text-xs uppercase tracking-widest shadow-2xl shadow-brand-500/20 transition-all active:scale-95 disabled:opacity-50">
          {{ form.processing ? 'ĐANG LƯU...' : 'XÁC NHẬN CHẾ BIẾN' }}
        </button>
      </div>

    </form>
  </div>
</template>

<style>
.scrollbar-thin::-webkit-scrollbar { width: 4px; }
.scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>
