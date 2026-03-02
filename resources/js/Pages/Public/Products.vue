<script setup>
import { Head, router } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { ref, watch } from 'vue'
defineOptions({ layout: GuestLayout })

const props = defineProps({
  products:   { type: Object, default: () => ({}) },
  categories: { type: Array,  default: () => [] },
  provinces:  { type: Array,  default: () => [] },
  filters:    { type: Object, default: () => ({}) },
})

const q          = ref(props.filters.q ?? '')
const categoryId = ref(props.filters.category_id ?? '')
const province   = ref(props.filters.province ?? '')

function applyFilter() {
  router.get('/san-pham', {
    q:           q.value || undefined,
    category_id: categoryId.value || undefined,
    province:    province.value || undefined,
  }, { preserveState: false, replace: true })
}

function reset() {
  q.value = ''
  categoryId.value = ''
  province.value = ''
  router.get('/san-pham', {}, { preserveState: false, replace: true })
}

function goToVerify(gtin) {
  router.get('/verify', { query: gtin })
}

const imgSrc = (path) => {
  if (!path) return null
  return path.startsWith('http') ? path : `/storage/${path}`
}

const list      = props.products?.data ?? []
const paginator = props.products
</script>

<template>
  <Head title="Danh mục sản phẩm — AGU Traceability" />

  <div class="max-w-7xl mx-auto px-6 py-10">

    <!-- Header -->
    <div class="mb-8">
      <div class="text-xs text-white/40 uppercase tracking-widest">
        <a href="/" class="hover:text-brand-400">Trang chủ</a>
        <span class="mx-2">/</span>
        Danh mục sản phẩm
      </div>
      <h1 class="text-2xl font-extrabold text-white/90 mt-2">Danh mục sản phẩm</h1>
      <p class="text-white/40 text-sm mt-1">
        Sản phẩm đã được xác thực và có thể truy xuất nguồn gốc trên hệ thống.
      </p>
    </div>

    <!-- Filters -->
    <div class="bg-white/5 border border-glass rounded-2xl p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">

        <!-- Search -->
        <input
          v-model="q"
          @keyup.enter="applyFilter"
          placeholder="Tên sản phẩm, GTIN, doanh nghiệp..."
          class="md:col-span-2 rounded-xl border border-glass bg-black/30 px-4 py-2.5 text-white/90 placeholder:text-white/30 focus:outline-none focus:border-brand-500/60 text-sm"
        />

        <!-- Category -->
        <select
          v-model="categoryId"
          @change="applyFilter"
          class="rounded-xl border border-glass bg-black/30 px-3 py-2.5 text-sm text-white/80 focus:outline-none focus:border-brand-500/60"
        >
          <option value="">Tất cả loại sản phẩm</option>
          <option v-for="c in categories" :key="c.id" :value="String(c.id)">
            {{ c.icon }} {{ c.name_vi }}
          </option>
        </select>

        <!-- Province -->
        <select
          v-model="province"
          @change="applyFilter"
          class="rounded-xl border border-glass bg-black/30 px-3 py-2.5 text-sm text-white/80 focus:outline-none focus:border-brand-500/60"
        >
          <option value="">Tất cả tỉnh/thành</option>
          <option v-for="p in provinces" :key="p" :value="p">{{ p }}</option>
        </select>

      </div>

      <div class="flex justify-between items-center mt-3">
        <span class="text-xs text-white/30">
          Tìm thấy <span class="text-white/60 font-semibold">{{ paginator?.total ?? 0 }}</span> sản phẩm
        </span>
        <button
          v-if="q || categoryId || province"
          @click="reset"
          class="text-xs text-brand-400 hover:underline"
        >
          Xóa bộ lọc
        </button>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="list.length === 0" class="text-center py-20">
      <div class="text-white/20 text-sm">Không tìm thấy sản phẩm phù hợp.</div>
    </div>

    <!-- Product grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
      <div
        v-for="p in list"
        :key="p.id"
        class="bg-white/5 border border-glass rounded-2xl overflow-hidden hover:border-brand-500/30 hover:bg-white/8 transition cursor-pointer group"
        @click="p.gtin && goToVerify(p.gtin)"
      >
        <!-- Image -->
        <div class="h-44 bg-white/5 overflow-hidden">
          <img
            v-if="imgSrc(p.image_path)"
            :src="imgSrc(p.image_path)"
            :alt="p.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <div v-else class="w-full h-full flex items-center justify-center">
            <span class="text-4xl">{{ p.category?.icon ?? '📦' }}</span>
          </div>
        </div>

        <!-- Card body -->
        <div class="p-4 space-y-2">

          <!-- Province -->
          <div v-if="p.enterprise?.province" class="text-[11px] text-white/40 flex items-center gap-1">
            <span>●</span>
            <span>{{ p.enterprise.province }}</span>
          </div>

          <!-- Product name -->
          <div class="font-extrabold text-white/90 text-sm leading-snug line-clamp-2">
            {{ p.name }}
          </div>

          <!-- Enterprise -->
          <div v-if="p.enterprise?.name" class="text-xs text-white/50 truncate">
            {{ p.enterprise.name }}
          </div>

          <!-- GTIN -->
          <div v-if="p.gtin" class="flex items-center gap-1.5 text-xs">
            <span class="text-brand-400/80">Mã xác thực:</span>
            <span class="font-mono text-brand-300 truncate">{{ p.gtin }}</span>
          </div>

          <!-- Category tag -->
          <div v-if="p.category" class="pt-1">
            <span class="text-[10px] px-2 py-0.5 bg-white/8 border border-glass rounded-full text-white/50">
              {{ p.category.icon }} {{ p.category.name_vi }}
            </span>
          </div>

        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="paginator?.last_page > 1" class="flex justify-center gap-2 mt-10">
      <a
        v-if="paginator.prev_page_url"
        :href="paginator.prev_page_url"
        class="px-4 py-2 rounded-xl border border-glass text-sm text-white/60 hover:bg-white/5 transition"
      >
        Trước
      </a>

      <span class="px-4 py-2 text-sm text-white/40">
        Trang {{ paginator.current_page }} / {{ paginator.last_page }}
      </span>

      <a
        v-if="paginator.next_page_url"
        :href="paginator.next_page_url"
        class="px-4 py-2 rounded-xl border border-glass text-sm text-white/60 hover:bg-white/5 transition"
      >
        Sau
      </a>
    </div>

  </div>
</template>