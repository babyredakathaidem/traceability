<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
    Users, 
    Layers, 
    Activity, 
    AlertTriangle, 
    ChevronRight,
    ArrowUpRight,
    Database
} from 'lucide-vue-next';

const props = defineProps({
    total_enterprises: Number,
    total_batches: Number,
    total_events: Number,
    recent_recalls: Array
});

const stats = [
    { name: 'Doanh nghiệp', value: props.total_enterprises, icon: Users, color: 'text-blue-600', bg: 'bg-blue-100' },
    { name: 'Lô hàng (Batches)', value: props.total_batches, icon: Layers, color: 'text-indigo-600', bg: 'bg-indigo-100' },
    { name: 'Sự kiện TXNG', value: props.total_events, icon: Activity, color: 'text-emerald-600', bg: 'bg-emerald-100' },
    { name: 'Đồng bộ Blockchain', value: props.total_events, icon: Database, color: 'text-amber-600', bg: 'bg-amber-100' },
];
</script>

<template>
    <Head title="Thống kê hệ thống" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Tổng quan hệ thống Traceability
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div v-for="(item, index) in stats" :key="item.name" 
                         class="bg-white overflow-hidden shadow-sm sm:rounded-2xl p-6 border border-gray-100 transform hover:-translate-y-2 transition-all duration-500 ease-out hover:shadow-xl group"
                         :style="{ transitionDelay: (index * 100) + 'ms' }">
                        <div class="flex items-center justify-between">
                            <div :class="[item.bg, item.color, 'p-3 rounded-xl transition-transform duration-500 group-hover:scale-110']">
                                <component :is="item.icon" class="w-6 h-6" />
                            </div>
                            <div class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full flex items-center">
                                <ArrowUpRight class="w-3 h-3 mr-1" />
                                Realtime
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-sm font-medium text-gray-500">{{ item.name }}</p>
                            <h3 class="text-3xl font-bold text-gray-900 mt-1 tracking-tight">
                                {{ item.value.toLocaleString() }}
                            </h3>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-12">
                    <!-- Recent Recalls - Cảnh báo gắt -->
                    <div class="lg:col-span-2 bg-white shadow-sm sm:rounded-2xl border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                                <AlertTriangle class="w-5 h-5 mr-2 text-red-500 animate-pulse" />
                                Cảnh báo thu hồi gần đây
                            </h3>
                            <Link href="#" class="text-sm text-blue-600 hover:underline">Xem tất cả</Link>
                        </div>
                        <div class="divide-y divide-gray-50">
                            <div v-for="recall in recent_recalls" :key="recall.id" 
                                 class="p-4 hover:bg-red-50/50 transition-colors duration-300 flex items-center justify-between group">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold">
                                        !
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">{{ recall.batch?.code }}</p>
                                        <p class="text-sm text-gray-500">{{ recall.reason }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-400">{{ recall.created_at }}</p>
                                    <span class="inline-block px-2 py-1 text-xs font-medium bg-red-100 text-red-700 rounded-md mt-1 capitalize">
                                        {{ recall.status }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="recent_recalls.length === 0" class="p-12 text-center text-gray-400">
                                <Activity class="w-12 h-12 mx-auto mb-4 opacity-20" />
                                <p>Hiện không có lệnh thu hồi nào đang hoạt động.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-gradient-to-br from-indigo-600 to-blue-700 sm:rounded-2xl p-8 text-white shadow-xl relative overflow-hidden">
                        <div class="relative z-10">
                            <h3 class="text-xl font-bold mb-4">Thao tác nhanh</h3>
                            <div class="space-y-4">
                                <Link :href="route('sys.config.index')" class="flex items-center justify-between p-4 bg-white/10 rounded-xl hover:bg-white/20 transition-all duration-300 group">
                                    <span>Cấu hình TCVN & CTE</span>
                                    <ChevronRight class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                                </Link>
                                <Link :href="route('sys.enterprises.index')" class="flex items-center justify-between p-4 bg-white/10 rounded-xl hover:bg-white/20 transition-all duration-300 group">
                                    <span>Duyệt doanh nghiệp mới</span>
                                    <ChevronRight class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                                </Link>
                            </div>
                        </div>
                        <!-- Background Decoration -->
                        <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                        <div class="absolute -left-10 -top-10 w-32 h-32 bg-indigo-400/20 rounded-full blur-2xl"></div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes entry {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.grid > div {
    animation: entry 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
}
</style>
