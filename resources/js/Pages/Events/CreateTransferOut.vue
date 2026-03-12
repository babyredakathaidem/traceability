<script setup>
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  enterprises: Array,
  batches: Array,
})

const form = useForm({
  input_batch_ids: [],
  to_enterprise_id: '',
  event_time: new Date().toISOString().slice(0, 16),
  who_name: '',
  kde_data: {},
  note: '',
  gs1_document_ref: '',
})

function submit() {
  form.post(route('events.transfer.out'))
}

const inputCls = 'w-full bg-[#0d1117] border border-white/10 rounded-xl px-4 py-3 text-sm text-white/90 placeholder:text-white/20 outline-none focus:border-purple-500/50 focus:ring-1 focus:ring-purple-500/20 transition-all shadow-inner appearance-none'
const labelCls = 'block text-xs font-bold text-white/40 uppercase tracking-widest mb-2'
</script>

<template>
  <Head title="Chuyển giao hàng hóa" />

  <div class="max-w-3xl mx-auto py-8 px-4 font-sans text-white">
    <div class="mb-8 flex items-center justify-between" data-aos="fade-down">
      <div>
        <h1 class="text-2xl font-black tracking-tight text-white">Chuyển giao hàng hóa</h1>
        <p class="text-white/40 text-sm mt-1 font-medium italic">Gửi lô hàng cho đối tác / doanh nghiệp khác.</p>
      </div>
      <Link :href="route('dashboard')" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/30 hover:text-white hover:bg-white/10 transition-all text-xl">✕</Link>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      
      <!-- Section 1: Thông tin đối tác -->
      <div class="p-6 rounded-3xl border border-white/5 bg-white/5 space-y-5 shadow-2xl" data-aos="fade-up">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-purple-500/20 flex items-center justify-center text-purple-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <h2 class="text-sm font-black uppercase tracking-widest text-white/90">Thông tin đối tác nhận</h2>
        </div>

        <div class="relative group">
          <label :class="labelCls">Doanh nghiệp nhận hàng *</label>
          <div class="relative">
            <select v-model="form.to_enterprise_id" :class="inputCls" required>
              <option value="" class="bg-[#0d1117]">/— Chọn doanh nghiệp trong hệ thống —</option>
              <option v-for="ent in enterprises" :key="ent.id" :value="ent.id" class="bg-[#0d1117]">
                {{ ent.name }} ({{ ent.code }})
              </option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-white/30 group-focus-within:text-purple-500 transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label :class="labelCls">Thời gian gửi *</label>
            <input v-model="form.event_time" type="datetime-local" :class="inputCls" required />
          </div>
          <div>
            <label :class="labelCls">Nhân viên bàn giao *</label>
            <input v-model="form.who_name" type="text" :class="inputCls" placeholder="Tên người thực hiện" required />
          </div>
        </div>
      </div>

      <!-- Section 2: Chọn lô hàng cần chuyển -->
      <div class="p-6 rounded-3xl border border-white/5 bg-white/5 space-y-5 shadow-2xl" data-aos="fade-up" data-aos-delay="100">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-brand-500/20 flex items-center justify-center text-brand-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
          </div>
          <h2 class="text-sm font-black uppercase tracking-widest text-white/90">Lô hàng vận chuyển</h2>
        </div>

        <div>
          <label :class="labelCls">Chọn (các) lô hàng gửi đi *</label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-72 overflow-y-auto p-3 bg-black/40 rounded-2xl border border-white/5 shadow-inner scrollbar-thin scrollbar-thumb-white/10">
            <label v-for="b in batches" :key="b.id" 
              class="flex items-center gap-4 p-4 rounded-xl border transition-all cursor-pointer group"
              :class="form.input_batch_ids.includes(b.id) ? 'bg-purple-500/10 border-purple-500/40' : 'bg-white/5 border-white/5 hover:bg-white/10'">
              <div class="relative flex items-center">
                <input type="checkbox" :value="b.id" v-model="form.input_batch_ids" 
                  class="w-5 h-5 rounded border-white/20 bg-transparent text-purple-500 focus:ring-purple-500/20 transition-all cursor-pointer" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-xs font-mono font-black tracking-tighter" :class="form.input_batch_ids.includes(b.id) ? 'text-purple-300' : 'text-white/70'">{{ b.code }}</div>
                <div class="text-[10px] uppercase font-bold opacity-40 mt-0.5 truncate">{{ b.product_name }} · {{ b.current_quantity }} {{ b.unit }}</div>
              </div>
            </label>
          </div>
          <p v-if="form.input_batch_ids.length" class="text-[10px] text-purple-400 font-bold uppercase mt-3 animate-pulse">
            🚚 Đang chuẩn bị gửi đi {{ form.input_batch_ids.length }} lô hàng...
          </p>
        </div>
      </div>

      <!-- Section 3: Vận chuyển & Chứng từ -->
      <div class="p-6 rounded-3xl border border-white/5 bg-white/5 space-y-5 shadow-2xl" data-aos="fade-up" data-aos-delay="200">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-white/60">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a2 2 0 01-2 2h-1m-9.688-2A5.002 5.002 0 0113 13.017V15H9v-1.017c0-.69-.17-1.34-.472-1.912" />
            </svg>
          </div>
          <h2 class="text-sm font-black uppercase tracking-widest text-white/90">Vận chuyển & Chứng từ</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label :class="labelCls">Số vận đơn / Hợp đồng</label>
            <input v-model="form.gs1_document_ref" type="text" :class="inputCls" placeholder="(400) SHIP-2026..." />
          </div>
          <div>
            <label :class="labelCls">Phương tiện vận chuyển</label>
            <input v-model="form.kde_data.vehicle" type="text" :class="inputCls" placeholder="Số xe, loại xe..." />
          </div>
        </div>

        <div>
          <label :class="labelCls">Ghi chú chi tiết</label>
          <textarea v-model="form.note" :class="inputCls" rows="3" placeholder="Nhập chi tiết về điều kiện vận chuyển, bàn giao..."></textarea>
        </div>
      </div>

      <div class="flex items-center justify-end gap-4 pt-4 pb-12">
        <Link :href="route('dashboard')" class="px-8 py-3 rounded-2xl border border-white/10 text-white/40 font-black text-xs uppercase tracking-widest hover:bg-white/5 transition-all">Hủy</Link>
        <button type="submit" :disabled="form.processing"
          class="px-12 py-3 rounded-2xl bg-purple-600 hover:bg-purple-500 text-white font-black text-xs uppercase tracking-widest shadow-2xl shadow-purple-500/20 transition-all active:scale-95 disabled:opacity-50">
          {{ form.processing ? 'ĐANG GỬI...' : 'XÁC NHẬN CHUYỂN GIAO' }}
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
