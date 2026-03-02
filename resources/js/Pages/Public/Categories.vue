<script setup>
import { Head } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
defineOptions({ layout: GuestLayout })

defineProps({
  categories: { type: Array, default: () => [] },
})
</script>

<template>
  <Head title="Danh mục sản phẩm — AGU Traceability" />

  <div class="max-w-7xl mx-auto px-6 py-12">
    <!-- Header -->
    <div class="mb-10">
      <div class="text-xs text-white/40 uppercase tracking-widest">Danh mục</div>
      <h1 class="text-3xl font-extrabold text-white/90 mt-2">Nhóm sản phẩm được hỗ trợ</h1>
      <p class="text-white/50 text-sm mt-2 max-w-xl">
        Hệ thống AGU Traceability hỗ trợ truy xuất nguồn gốc cho các nhóm nông sản, thực phẩm theo chuẩn TCVN 12850:2019 và GS1.
      </p>
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="cat in categories"
        :key="cat.code"
        class="bg-white/5 border border-glass rounded-2xl p-6 hover:bg-white/8 transition"
      >
        <div class="flex items-start gap-4">
          <div class="text-4xl shrink-0">{{ cat.icon }}</div>
          <div class="flex-1 min-w-0">
            <div class="font-extrabold text-white/90">{{ cat.name_vi }}</div>
            <div class="text-xs text-white/40 mt-1 font-mono">{{ cat.tcvn_ref }}</div>
          </div>
        </div>

        <div class="mt-4 pt-4 border-t border-white/5">
          <p class="text-xs text-white/50 leading-relaxed">
            Truy xuất chuỗi cung ứng từ sản xuất đến phân phối, ghi nhận đầy đủ dữ liệu 5W (Who, What, Where, When, Why) theo chuẩn quốc gia.
          </p>
        </div>
      </div>
    </div>

    <!-- CTA -->
    <div class="mt-12 bg-brand-500/10 border border-brand-500/20 rounded-2xl p-8 text-center">
      <div class="text-white/90 font-extrabold text-xl">Doanh nghiệp của bạn chưa có tài khoản?</div>
      <p class="text-white/50 text-sm mt-2 mb-6">Đăng ký miễn phí, bắt đầu truy xuất nguồn gốc sản phẩm ngay hôm nay.</p>
      <a href="/onboarding/enterprise"
        class="inline-block px-8 py-3 rounded-xl bg-brand-500 text-cosmic-950 font-extrabold hover:bg-brand-600 transition text-sm">
        Đăng ký doanh nghiệp
      </a>
    </div>
  </div>
</template>