<script setup>
import { Head, router } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { ref } from 'vue'
defineOptions({ layout: GuestLayout })

const props = defineProps({
  query:   { type: String, default: '' },
  results: { type: Array,  default: () => [] },
  error:   { type: String, default: null },
})

const q = ref(props.query)
const search = () => {
  if (q.value.trim()) {
    router.get('/verify', { query: q.value.trim() }, { preserveState: false })
  }
}
</script>

<template>
  <Head :title="query ? `Tra cứu: ${query} — AGU Traceability` : 'Tra cứu nguồn gốc — AGU Traceability'" />

  <div class="max-w-4xl mx-auto px-6 py-12">

    <!-- Header + search lại -->
    <div class="mb-8">
      <a href="/" class="text-xs text-brand-400 hover:underline">Trang chủ</a>
      <span class="text-white/20 mx-2">/</span>
      <span class="text-xs text-white/40">Tra cứu</span>

      <h1 class="text-2xl font-extrabold text-white/90 mt-3">Tra cứu nguồn gốc sản phẩm</h1>

      <div class="mt-4 flex gap-2">
        <input
          v-model="q"
          @keyup.enter="search"
          placeholder="Nhập mã lô hoặc GTIN..."
          class="flex-1 rounded-xl border border-glass bg-black/30 px-4 py-2.5 text-white/90 placeholder:text-white/30 focus:outline-none focus:border-brand-500/60 text-sm"
        />
        <button
          @click="search"
          class="px-5 py-2.5 rounded-xl bg-brand-500 text-cosmic-950 font-extrabold hover:bg-brand-600 transition text-sm"
        >
          Tìm
        </button>
      </div>
    </div>

    <!-- Chưa nhập gì -->
    <div v-if="!query" class="text-center py-16">
      <div class="text-white/20 text-sm">Nhập mã lô (VD: LG01001) hoặc mã GTIN sản phẩm để tra cứu.</div>
    </div>

    <!-- Không có kết quả -->
    <div v-else-if="error" class="bg-white/5 border border-glass rounded-2xl p-8 text-center">
      <div class="text-white/50 text-sm">{{ error }}</div>
      <p class="text-xs text-white/30 mt-3">Gợi ý: Kiểm tra lại mã lô trên bao bì sản phẩm hoặc liên hệ nhà sản xuất.</p>
    </div>

    <!-- Kết quả -->
    <div v-else class="space-y-4">
      <div class="text-xs text-white/40 mb-4">
        Tìm thấy <span class="text-white/70 font-semibold">{{ results.length }}</span> kết quả cho "<span class="text-brand-400">{{ query }}</span>"
      </div>

      <div
        v-for="b in results"
        :key="b.id"
        class="bg-white/5 border border-glass rounded-2xl p-6 hover:bg-white/8 transition"
      >
        <!-- Top: product info -->
        <div class="flex items-start gap-4">
          <div v-if="b.product?.category" class="text-3xl shrink-0">{{ b.product.category.icon }}</div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-extrabold text-white/90">{{ b.product?.name ?? '—' }}</span>
              <!-- Trạng thái lô -->
              <span
                :class="b.status === 'recalled'
                  ? 'text-red-400 bg-red-400/10 border-red-400/20'
                  : 'text-green-400 bg-green-400/10 border-green-400/20'"
                class="text-[10px] px-2 py-0.5 rounded border font-semibold"
              >
                {{ b.status === 'recalled' ? 'Đã thu hồi' : 'Hợp lệ' }}
              </span>
            </div>

            <div v-if="b.product?.category" class="text-xs text-white/40 mt-0.5">
              {{ b.product.category.name_vi }}
            </div>
          </div>
        </div>

        <!-- Divider -->
        <div class="border-t border-white/5 my-4"></div>

        <!-- Info grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
          <div>
            <div class="text-[10px] text-white/30 uppercase tracking-wider">Mã lô</div>
            <div class="font-mono text-white/80 mt-0.5">{{ b.code }}</div>
          </div>
          <div v-if="b.product?.gtin">
            <div class="text-[10px] text-white/30 uppercase tracking-wider">GTIN</div>
            <div class="font-mono text-white/80 mt-0.5">{{ b.product.gtin }}</div>
          </div>
          <div v-if="b.production_date">
            <div class="text-[10px] text-white/30 uppercase tracking-wider">Ngày sản xuất</div>
            <div class="text-white/80 mt-0.5">{{ b.production_date }}</div>
          </div>
          <div v-if="b.expiry_date">
            <div class="text-[10px] text-white/30 uppercase tracking-wider">Hạn sử dụng</div>
            <div class="text-white/80 mt-0.5">{{ b.expiry_date }}</div>
          </div>
          <div v-if="b.quantity">
            <div class="text-[10px] text-white/30 uppercase tracking-wider">Số lượng</div>
            <div class="text-white/80 mt-0.5">{{ b.quantity }} {{ b.unit }}</div>
          </div>
          <div v-if="b.enterprise">
            <div class="text-[10px] text-white/30 uppercase tracking-wider">Doanh nghiệp</div>
            <div class="text-white/80 mt-0.5 truncate">{{ b.enterprise.name }}</div>
            <div v-if="b.enterprise.province" class="text-[10px] text-white/30">{{ b.enterprise.province }}</div>
          </div>
          <div>
            <div class="text-[10px] text-white/30 uppercase tracking-wider">Sự kiện đã ghi</div>
            <div class="text-brand-400 font-semibold mt-0.5">{{ b.event_count }} sự kiện</div>
          </div>
        </div>

        <!-- Cảnh báo thu hồi -->
        <div v-if="b.status === 'recalled'"
          class="mt-4 px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-xl text-xs text-red-300">
          Lô hàng này đã bị nhà sản xuất phát lệnh thu hồi. Vui lòng không sử dụng và liên hệ nơi mua hàng.
        </div>

        <!-- Note: cần QR để xem trace đầy đủ -->
        <div class="mt-4 pt-4 border-t border-white/5 text-xs text-white/30">
          Để xem toàn bộ chuỗi truy xuất, hãy quét mã QR trên bao bì sản phẩm.
        </div>
      </div>
    </div>
  </div>
</template>