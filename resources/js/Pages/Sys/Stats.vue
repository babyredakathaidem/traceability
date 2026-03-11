<script setup>
import { Head } from '@inertiajs/vue3'
import UiCard from '@/Components/ui/UiCard.vue'
import UiBadge from '@/Components/ui/UiBadge.vue'
import {
    BuildingOffice2Icon,
    ArchiveBoxIcon,
    ClipboardDocumentListIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    total_enterprises: Number,
    total_batches: Number,
    total_events: Number,
    recent_recalls: Array,
})

const stats = [
    {
        label: 'Doanh nghiệp',
        value: props.total_enterprises,
        icon: BuildingOffice2Icon,
        color: 'text-brand-400',
        bg: 'bg-brand-500/10',
    },
    {
        label: 'Lô hàng',
        value: props.total_batches,
        icon: ArchiveBoxIcon,
        color: 'text-emerald-400',
        bg: 'bg-emerald-500/10',
    },
    {
        label: 'Sự kiện TXNG',
        value: props.total_events,
        icon: ClipboardDocumentListIcon,
        color: 'text-sky-400',
        bg: 'bg-sky-500/10',
    },
    {
        label: 'Thu hồi gần đây',
        value: props.recent_recalls?.length ?? 0,
        icon: ExclamationTriangleIcon,
        color: 'text-red-400',
        bg: 'bg-red-500/10',
    },
]

const recallBadge = (s) => {
    if (s === 'active') return 'red'
    if (s === 'resolved') return 'green'
    return 'gray'
}
</script>

<template>

    <Head title="Thống kê hệ thống" />

    <div class="space-y-6">

        <!-- Header -->
        <div class="rounded-2xl border border-glass bg-black/40 p-6" data-aos="fade-right">
            <div class="text-brand-300 text-sm font-semibold">Hệ thống</div>
            <div class="text-2xl font-bold mt-1 text-white/90">Thống kê tổng quát</div>
            <div class="text-white/50 text-sm mt-1">Tổng quan toàn bộ hoạt động trên nền tảng.</div>
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" data-aos="fade-up">
            <div v-for="s in stats" :key="s.label"
                class="rounded-2xl border border-glass bg-black/30 p-5 flex items-center gap-4">
                <div :class="[s.bg, 'p-3 rounded-xl shrink-0']">
                    <component :is="s.icon" :class="[s.color, 'w-6 h-6']" />
                </div>
                <div>
                    <div class="text-2xl font-extrabold text-white/90">{{ s.value?.toLocaleString() }}</div>
                    <div class="text-xs text-white/40 mt-0.5">{{ s.label }}</div>
                </div>
            </div>
        </div>

        <!-- Recent recalls -->
        <UiCard title="Thu hồi gần đây" subtitle="5 lệnh thu hồi mới nhất" data-aos="fade-up">
            <div v-if="recent_recalls?.length" class="divide-y divide-white/5">
                <div v-for="r in recent_recalls" :key="r.id" class="flex items-center justify-between py-3 px-1">
                    <div class="space-y-0.5">
                        <div class="text-sm font-bold text-white/90">
                            Lô: <span class="text-brand-300 font-mono">{{ r.batch?.code ?? '—' }}</span>
                        </div>
                        <div class="text-xs text-white/40 truncate max-w-sm">{{ r.reason }}</div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="text-xs text-white/30">
                            {{ r.recalled_at ? new Date(r.recalled_at).toLocaleDateString('vi-VN') : '—' }}
                        </span>
                        <UiBadge :variant="recallBadge(r.status)">{{ r.status }}</UiBadge>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-10 text-white/30 text-sm">
                Chưa có lệnh thu hồi nào.
            </div>
        </UiCard>

    </div>
</template>