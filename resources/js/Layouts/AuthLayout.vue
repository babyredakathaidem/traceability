<script setup>
import { Head } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const props = defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  bannerUrl: { type: String, default: '/brand/auth-banner.jpg' },
})
</script>

<template>
  <Head :title="props.title || page.props?.title || 'AGU Traceability'" />

  <div class="min-h-screen bg-cosmic text-white/90">
    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-3">
      <!-- LEFT -->
      <aside class="relative hidden lg:block border-r border-glass overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center" :style="`background-image:url('${props.bannerUrl}')`" />
        <div class="absolute inset-0 bg-black/60" />
        <div class="absolute -top-24 -left-24 h-80 w-80 rounded-full bg-brand-500/20 blur-3xl" />
        <div class="absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-brand-500/10 blur-3xl" />
        <div class="relative h-full p-10 flex flex-col">
          <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-2xl bg-brand-500 text-cosmic-950 flex items-center justify-center font-extrabold overflow-hidden">
              <img
                src="/brand/logo-agu.png"
                alt="AGU"
                class="h-11 w-11 object-cover"
                onerror="this.style.display='none';"
              />
              <span class="hidden">AGU</span>
            </div>
            <div>
              <div class="font-extrabold leading-tight text-white">AGU Traceability</div>
              <div class="text-xs text-white/70">Truy xuất nguồn gốc • Chống giả</div>
            </div>
          </div>

          <div class="mt-10">
            <div class="text-2xl font-black leading-snug text-white">
              Quản lý lô • Sự kiện • QR • Cảnh báo
            </div>
            <div class="mt-3 text-sm text-white/70">
              Multi-tenant: cách ly dữ liệu theo doanh nghiệp. Truy xuất công khai bằng QR.
            </div>
          </div>

          <div class="mt-auto pt-10 text-xs text-white/50">
            © {{ new Date().getFullYear() }} AGU Traceability
          </div>
        </div>
      </aside>

      <!-- RIGHT -->
      <main class="lg:col-span-2">
        <div class="min-h-screen flex items-center justify-center p-6 relative">
          <div class="pointer-events-none absolute inset-0">
            <div class="absolute left-1/2 top-20 h-72 w-72 -translate-x-1/2 rounded-full bg-brand-500/10 blur-3xl" />
          </div>

          <div class="w-full max-w-md relative">
            <!-- ✅ Title only once -->
            <div class="mb-5">
              <h1 class="text-3xl font-extrabold text-brand-300">
                {{ props.title || 'Xác thực' }}
              </h1>
              <p v-if="props.subtitle" class="mt-2 text-sm text-white/60">
                {{ props.subtitle }}
              </p>
            </div>

            <!-- ✅ Card just for form -->
            <div class="bg-white/5 border border-glass rounded-2xl backdrop-blur-md shadow-glow-orange p-6">
              <slot />
            </div>

            <div class="mt-4 text-center text-xs text-white/50">
              © {{ new Date().getFullYear() }} AGU Traceability
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>