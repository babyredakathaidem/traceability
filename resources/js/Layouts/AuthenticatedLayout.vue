<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { 
    LayoutDashboard, 
    Box, 
    Package, 
    Activity, 
    Settings, 
    Users, 
    ShieldCheck, 
    BarChart3, 
    LogOut, 
    Menu, 
    X,
    Building2,
    CheckCircle2,
    FlaskConical
} from 'lucide-vue-next'

const page = usePage()
const isSidebarOpen = ref(true)
const user = computed(() => page.props.auth?.user)
const currentPath = ref(typeof window !== 'undefined' ? window.location.pathname : '/')

// Cập nhật currentPath khi Inertia navigate
router.on('navigate', () => {
    currentPath.value = window.location.pathname
})

// Logic nhận diện Super Admin "Thần thánh"
const isSuperAdmin = computed(() => {
    const u = user.value;
    const isAtSysRoute = currentPath.value.includes('/sys');
    const isSuperField = u?.is_super_admin === true || u?.is_super_admin === 1 || u?.is_super_admin === '1';
    return isSuperField || isAtSysRoute;
})

onMounted(() => {
    console.log('--- SIDEBAR DIAGNOSTIC ---');
    console.log('User:', user.value);
    console.log('Is Super Admin:', isSuperAdmin.value);
})

const logout = () => router.post('/logout')

// Helper để lấy route an toàn, không lo lỗi Ziggy
const safeRoute = (name, fallback) => {
    try {
        return route(name);
    } catch (e) {
        console.warn(`Ziggy Route [${name}] not found, using fallback.`);
        return fallback;
    }
}

const navItems = computed(() => {
    const items = [
        { name: 'Dashboard', href: safeRoute('dashboard', '/dashboard'), icon: LayoutDashboard },
        { name: 'Sản phẩm', href: safeRoute('products.index', '/products'), icon: Box },
        { name: 'Lô hàng', href: safeRoute('batches.index', '/batches'), icon: Package },
        { name: 'Sự kiện TRACE', href: safeRoute('events.index', '/events'), icon: Activity },
        { name: 'Chế biến', href: safeRoute('batches.transform.show', '/batches/transform'), icon: FlaskConical },
    ]

    // Menu cho DN
    if (!isSuperAdmin.value) {
        items.push(
            { type: 'divider', label: 'Quản trị nội bộ' },
            { name: 'Nhân sự DN', href: safeRoute('enterprise.users.index', '/enterprise/users'), icon: Users },
            { name: 'Cài đặt DN', href: safeRoute('enterprise.settings.show', '/enterprise/settings'), icon: Settings },
        )
    }
    
    // Menu cho SuperAdmin (Hệ thống)
    if (isSuperAdmin.value) {
        items.push(
            { type: 'divider', label: 'Hệ thống (Super)' },
            { name: 'Thống kê tổng quát', href: safeRoute('sys.stats', '/sys/stats'), icon: BarChart3 },
            { name: 'Duyệt doanh nghiệp', href: safeRoute('sys.enterprises.index', '/sys/enterprises'), icon: Building2 },
            { name: 'Cấu hình TCVN/CTE', href: safeRoute('sys.config.index', '/sys/config'), icon: Settings },
            { name: 'Quản lý tài khoản', href: safeRoute('sys.users.index', '/sys/users'), icon: ShieldCheck },
        )
    }

    return items
})
</script>

<template>
    <div class="min-h-screen bg-[#050505] text-white/90 flex overflow-hidden font-sans">
        <!-- Sidebar -->
        <aside 
            :class="[isSidebarOpen ? 'w-72' : 'w-24']" 
            class="bg-black/40 backdrop-blur-3xl border-r border-white/5 transition-all duration-500 ease-in-out flex flex-col z-50 relative overflow-hidden shrink-0"
        >
            <div class="absolute -left-20 -top-20 w-40 h-40 bg-orange-500/10 blur-[80px] rounded-full"></div>

            <!-- Logo -->
            <div class="h-20 flex items-center px-8 border-b border-white/5 overflow-hidden relative z-10">
                <div class="flex items-center gap-4">
                    <div class="h-10 w-10 min-w-[40px] rounded-2xl bg-orange-500 text-black flex items-center justify-center font-black shadow-lg shadow-orange-500/20">
                        <CheckCircle2 class="w-6 h-6" />
                    </div>
                    <span v-if="isSidebarOpen" class="font-black text-2xl tracking-tighter text-white whitespace-nowrap uppercase italic">AGU <span class="text-orange-400 font-medium lowercase">Trace</span></span>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto py-8 px-4 space-y-2 relative z-10 custom-scrollbar">
                <template v-for="(item, index) in navItems" :key="index">
                    <div v-if="item.type === 'divider'" class="mt-8 mb-4 px-4">
                        <span v-if="isSidebarOpen" class="text-[10px] font-black uppercase text-white/20 tracking-[0.3em]">{{ item.label }}</span>
                        <div v-else class="h-px bg-white/5 w-full"></div>
                    </div>

                    <Link v-else :href="item.href" 
                          :class="[item.href && currentPath.includes(item.href.split('/').pop()) ? 'bg-orange-500/10 text-orange-400 border-l-4 border-orange-500 shadow-[0_0_20px_rgba(255,165,0,0.1)]' : 'text-white/40 hover:bg-white/5 hover:text-white border-l-4 border-transparent']"
                          class="flex items-center gap-4 px-4 py-4 rounded-r-2xl transition-all group">
                        <component :is="item.icon" class="w-6 h-6 min-w-[24px] group-hover:scale-110 transition-transform duration-300" />
                        <span v-if="isSidebarOpen" class="text-sm font-black uppercase tracking-[0.1em] truncate">{{ item.name }}</span>
                    </Link>
                </template>
            </nav>

            <!-- Bottom -->
            <div class="p-6 bg-black/40 border-t border-white/5 relative z-10">
                <div v-if="isSidebarOpen" class="flex items-center gap-4 mb-6 p-4 bg-white/5 rounded-2xl border border-white/5">
                    <div class="h-12 w-12 rounded-2xl bg-orange-500 flex items-center justify-center font-black text-black uppercase shadow-inner">{{ user?.name?.[0] || '?' }}</div>
                    <div class="overflow-hidden">
                        <p class="text-sm font-black text-white truncate">{{ user?.name || 'Guest' }}</p>
                        <p class="text-[10px] font-bold text-orange-400 uppercase tracking-widest truncate">{{ isSuperAdmin ? 'Master Admin' : 'Entity Admin' }}</p>
                    </div>
                </div>
                <button @click="logout" class="w-full flex items-center gap-4 px-4 py-3 text-white/40 hover:text-red-400 hover:bg-red-400/10 rounded-2xl transition-all group">
                    <LogOut class="w-6 h-6 group-hover:-translate-x-1 transition-transform" />
                    <span v-if="isSidebarOpen" class="text-sm font-black uppercase tracking-widest">Sign Out</span>
                </button>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
            <div class="absolute -right-40 -bottom-40 w-[500px] h-[500px] bg-orange-500/5 blur-[120px] rounded-full pointer-events-none"></div>
            <header class="h-20 bg-black/10 backdrop-blur-md border-b border-white/5 flex items-center justify-between px-10 z-40">
                <div class="flex items-center gap-6">
                    <button @click="isSidebarOpen = !isSidebarOpen" class="p-3 text-white/40 hover:text-white hover:bg-white/5 rounded-2xl border border-white/5 transition-all">
                        <Menu v-if="!isSidebarOpen" class="w-6 h-6" />
                        <X v-else class="w-6 h-6" />
                    </button>
                    <slot name="header" />
                </div>
                <div class="flex items-center gap-6">
                    <div v-if="isSuperAdmin" class="bg-orange-500/10 text-orange-400 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.2em] border border-orange-500/20 shadow-[0_0_15px_rgba(255,165,0,0.1)]">Super Active</div>
                    <div class="text-[10px] font-black text-white/20 uppercase tracking-[0.3em]">v1.0.4-stable</div>
                </div>
            </header>
            <main class="flex-1 overflow-y-auto custom-scrollbar p-10 relative z-10">
                <slot />
            </main>
        </div>
    </div>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 165, 0, 0.1); border-radius: 10px; }
</style>
