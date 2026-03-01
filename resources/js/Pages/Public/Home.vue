<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { ref } from 'vue'
defineOptions({ layout: GuestLayout })

const q = ref('')
const go = () => { window.location.href = `/verify?query=${encodeURIComponent(q.value)}` }
</script>

<template>
  <section class="bg-transparent">
    <div class="max-w-7xl mx-auto px-6 py-14 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
      <div>
        <div class="flex items-center gap-3 mb-6">
          <div class="h-9 w-9 rounded-full bg-brand-500 text-cosmic-950 flex items-center justify-center font-extrabold">✓</div>
          <div class="text-xl font-semibold">AGU Traceability</div>
        </div>

        <div class="text-white/60 text-sm font-semibold tracking-wide">TRA CỨU NHANH</div>
        <h1 class="text-4xl font-extrabold mt-2 text-brand-200">XÁC THỰC SẢN PHẨM</h1>

        <p class="text-white/60 mt-4 leading-relaxed">
          Xác thực và truy vết nguồn gốc sản phẩm, góp phần ngăn chặn hàng giả.
        </p>

        <div class="mt-8 flex gap-3">
          <input
            v-model="q"
            class="w-full max-w-xl rounded-lg border border-glass bg-black/20 px-4 py-3 text-white/90 placeholder:text-white/40 focus:border-brand-500/60 focus:ring-brand-500/20"
            placeholder="Nhập mã xác thực, GTIN, số lô…"
          />
          <button class="px-6 rounded-lg bg-brand-500 text-cosmic-950 font-extrabold hover:bg-brand-600 transition" @click="go">
            Tra cứu ngay
          </button>
        </div>
      </div>

      <div class="flex justify-center">
        <img src="/brand/hero-phone.png" class="max-w-md w-full object-contain" onerror="this.style.display='none';" />
      </div>
    </div>
  </section>

  <section class="bg-black/25 border-y border-glass">
    <div class="max-w-7xl mx-auto px-6 py-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
      <div>
        <div class="text-sm font-semibold text-white/70">ĐĂNG KÝ XÁC THỰC</div>
        <div class="text-3xl font-extrabold mt-2 text-white/90">4 BƯỚC – DỄ DÀNG VÀ NHANH CHÓNG</div>
      </div>
      <div class="text-white/70 max-w-xl text-sm leading-relaxed">
        Doanh nghiệp đăng ký tài khoản, khai báo sản phẩm/lô, in QR và ghi nhận sự kiện truy xuất.
      </div>
    </div>
  </section>

  <section class="bg-transparent">
    <div class="max-w-7xl mx-auto px-6 py-14">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm font-semibold text-white/60">DANH MỤC</div>
          <div class="text-2xl font-extrabold mt-1 text-brand-200">SẢN PHẨM ĐÁNG CHÚ Ý</div>
        </div>
        <a href="/categories" class="px-5 py-2 rounded-full bg-brand-500 text-cosmic-950 font-extrabold hover:bg-brand-600 transition">
          Xem thêm
        </a>
      </div>

      <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        <a class="bg-white/5 border border-glass rounded-2xl overflow-hidden hover:shadow-glow-orange transition" href="#">
          <img class="h-44 w-full object-cover" src="/brand/cat1.jpg" onerror="this.style.display='none';" />
          <div class="p-4 flex items-center justify-between">
            <div class="font-semibold text-white/90">Sản phẩm</div>
            <span class="text-brand-300">→</span>
          </div>
        </a>
        <a class="bg-white/5 border border-glass rounded-2xl overflow-hidden hover:shadow-glow-orange transition" href="#">
          <img class="h-44 w-full object-cover" src="/brand/cat2.jpg" onerror="this.style.display='none';" />
          <div class="p-4 flex items-center justify-between">
            <div class="font-semibold text-white/90">Nguyên liệu</div>
            <span class="text-brand-300">→</span>
          </div>
        </a>
        <a class="bg-white/5 border border-glass rounded-2xl overflow-hidden hover:shadow-glow-orange transition" href="#">
          <img class="h-44 w-full object-cover" src="/brand/cat3.jpg" onerror="this.style.display='none';" />
          <div class="p-4 flex items-center justify-between">
            <div class="font-semibold text-white/90">Hóa chất</div>
            <span class="text-brand-300">→</span>
          </div>
        </a>
      </div>
    </div>
  </section>
</template>