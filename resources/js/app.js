import './bootstrap'

import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createApp, h } from 'vue'

// Ziggy
import { ZiggyVue } from '../../vendor/tightenco/ziggy'

import AppLayout from '@/Layouts/AppLayout.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import SysLayout from '@/Layouts/SysLayout.vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'

createInertiaApp({
  title: (title) => (title ? `${title} — AGU Traceability` : 'AGU Traceability'),
  resolve: (name) => {
    const page = resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob('./Pages/**/*.vue')
    )

    page.then((module) => {
      const comp = module.default

      // Nếu page tự define layout thì không override
      if (comp.layout) return

      // Auth + Onboarding
      if (name.startsWith('Auth/') || name.startsWith('Onboarding/')) {
        comp.layout = AuthLayout
        return
      }

      // Sys admin
      if (name.startsWith('Sys/')) {
        comp.layout = SysLayout
        return
      }

      // Public pages (chưa login)
      if (name.startsWith('Public/') || name === 'Welcome') {
        comp.layout = GuestLayout
        return
      }

      // Default: app nội bộ (sau login)
      comp.layout = AppLayout
    })

    return page
  },
  setup({ el, App, props, plugin }) {
    const vueApp = createApp({ render: () => h(App, props) })
    vueApp.use(plugin)
    vueApp.use(ZiggyVue, window.Ziggy)
    vueApp.mount(el)
  },
})