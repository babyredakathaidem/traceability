<script setup>
import { Head } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { ref } from 'vue'
defineOptions({ layout: GuestLayout })

const props = defineProps({
  categories: { type: Array, default: () => [] },
})

const q = ref('')
const go = () => {
  if (q.value.trim()) window.location.href = `/verify?query=${encodeURIComponent(q.value.trim())}`
}
const onEnter = (e) => { if (e.key === 'Enter') go() }
</script>

<template>
  <Head title="Trang chủ — AGU Traceability" />

  <!-- Hero -->
  <section data-aos="fade-up">
    <div class="max-w-7xl mx-auto px-6 py-16 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
      <div data-aos="fade-right" data-aos-delay="200">
        <div class="text-white/50 text-xs font-bold tracking-widest uppercase mb-3">Hệ thống truy xuất nguồn gốc</div>
        <h1 class="text-4xl lg:text-5xl font-extrabold leading-tight text-white/90">
          Xác thực <span class="text-brand-400">nguồn gốc</span><br>sản phẩm
        </h1>
        <p class="text-white/50 mt-5 leading-relaxed max-w-md">
          Tra cứu thông tin lô hàng, chuỗi sự kiện sản xuất và xác minh tính toàn vẹn dữ liệu trên IPFS theo chuẩn TCVN 12850:2019.
        </p>

        <!-- Search bar -->
        <div class="mt-8 flex gap-2">
          <input
            v-model="q"
            @keyup="onEnter"
            placeholder="Nhập mã lô hoặc mã GTIN sản phẩm..."
            class="flex-1 rounded-xl border border-glass bg-black/30 px-4 py-3 text-white/90 placeholder:text-white/30 focus:outline-none focus:border-brand-500/60 text-sm"
          />
          <button
            @click="go"
            class="px-6 py-3 rounded-xl bg-brand-500 text-cosmic-950 font-extrabold hover:bg-brand-600 transition text-sm whitespace-nowrap"
          >
            Tra cứu
          </button>
        </div>
        <p class="text-xs text-white/30 mt-2">Ví dụ: LG01001, 8934563000012</p>
      </div>

      <div class="hidden lg:flex justify-center">
        <div class="w-72 h-72 rounded-3xl bg-brand-500/10 border border-brand-500/20 flex items-center justify-center">
          <div class="text-center">
            <div class="text-6xl font-extrabold text-brand-400/60">AGU</div>
            <div class="text-sm text-white/30 mt-2">Traceability</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Steps -->
  <section class="border-y border-glass bg-black/20 overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 py-12">
      <div class="text-xs text-white/40 uppercase tracking-widest text-center mb-8" data-aos="fade-up">Quy trình</div>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <div v-for="(step, i) in [
          { num: '01', title: 'Đăng ký doanh nghiệp', desc: 'Khai báo thông tin, upload giấy phép kinh doanh.' },
          { num: '02', title: 'Khai báo sản phẩm & lô', desc: 'Tạo sản phẩm, sinh mã lô tự động theo chuẩn.' },
          { num: '03', title: 'Ghi nhận sự kiện', desc: 'Ghi dữ liệu 5W theo từng bước chuỗi cung ứng.' },
          { num: '04', title: 'Publish & In QR', desc: 'Dữ liệu lên IPFS bất biến, in QR cho người tiêu dùng quét.' },
        ]" :key="i"
          class="bg-white/5 border border-glass rounded-2xl p-5 hover:-translate-y-1 hover:bg-white/10 transition-all duration-300"
          data-aos="fade-up" :data-aos-delay="i * 150">
          <div class="text-2xl font-extrabold text-brand-500/50">{{ step.num }}</div>
          <div class="font-semibold text-white/80 mt-3 text-sm">{{ step.title }}</div>
          <div class="text-xs text-white/40 mt-2 leading-relaxed">{{ step.desc }}</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Danh mục sản phẩm -->
  <section>
    <div class="max-w-7xl mx-auto px-6 py-14">
      <div class="flex items-center justify-between mb-8">
        <div>
          <div class="text-xs text-white/40 uppercase tracking-widest">Danh mục</div>
          <div class="text-2xl font-extrabold mt-1 text-white/90">Nhóm sản phẩm được hỗ trợ</div>
        </div>
        <a href="/san-pham" class="text-sm text-brand-400 hover:underline">Xem tất cả</a>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <a
          v-for="(cat, i) in categories"
          :key="cat.code"
          href="/san-pham"
          class="bg-white/5 border border-glass rounded-2xl p-4 text-center hover:bg-white/8 hover:border-brand-500/30 hover:-translate-y-1 transition-all duration-300 block"
          data-aos="zoom-in" :data-aos-delay="i * 100"
        >
          <div class="text-3xl">{{ cat.icon }}</div>
          <div class="text-sm text-white/70 font-semibold mt-2 leading-snug">{{ cat.name_vi }}</div>
          <div class="text-[10px] text-white/30 mt-1">{{ cat.tcvn_ref }}</div>
        </a>
      </div>
    </div>
  </section>
</template>