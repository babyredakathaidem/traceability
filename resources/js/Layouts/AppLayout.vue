<script setup>
import { ref, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppSidebar   from '@/Components/nav/AppSidebar.vue'
import AppTopbar    from '@/Components/nav/AppTopbar.vue'
import UiToastHost  from '@/Components/ui/UiToastHost.vue'

const toastRef = ref(null)
const page     = usePage()

router.on('navigate', () => {
  const flash = page.props?.flash
  if (!flash) return
  if (flash.success) toastRef.value?.push('✅ ' + flash.success, 'success')
  if (flash.error)   toastRef.value?.push('❌ ' + flash.error,   'danger')
})
</script>

<template>
  <div class="min-h-screen bg-cosmic text-white/90">
    <div class="flex">
      <AppSidebar />
      <div class="flex-1 min-w-0">
        <AppTopbar />
        <main class="p-6">
          <div class="bg-white/5 border border-glass rounded-2xl backdrop-blur-md shadow-glow-orange p-5">
            <slot />
          </div>
        </main>
      </div>
    </div>

    <!-- Toast global — tự động hiện flash từ Laravel -->
    <UiToastHost ref="toastRef" />
  </div>
</template>