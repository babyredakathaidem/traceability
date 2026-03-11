<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import UiCard from '@/Components/ui/UiCard.vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiBadge from '@/Components/ui/UiBadge.vue'
import UiModal from '@/Components/ui/UiModal.vue'
import UiInput from '@/Components/ui/UiInput.vue'
import UiTable from '@/Components/ui/UiTable.vue'

const props = defineProps({
    categories: Array,
    cte_templates: Array,
})

// ── Tab ───────────────────────────────────────────────────
const activeTab = ref('cte')

// ── Filter CTE theo category ──────────────────────────────
const filterCatId = ref(null)
const filteredCte = computed(() => {
    if (!filterCatId.value) return props.cte_templates
    return props.cte_templates.filter(t => t.category_id === filterCatId.value)
})

// ── Thêm category mới ─────────────────────────────────────
const showAddCat = ref(false)
const catForm = useForm({ code: '', name_vi: '', tcvn_ref: '' })
const submitCat = () => {
    catForm.post(route('sys.config.categories.store'), {
        onSuccess: () => { showAddCat.value = false; catForm.reset() },
    })
}

// ── Chỉnh sửa CTE template ────────────────────────────────
const editingCte = ref(null)
const cteForm = useForm({ name_vi: '', is_required: false, tcvn_note: '' })

function openEditCte(t) {
    editingCte.value = t
    cteForm.name_vi = t.name_vi
    cteForm.is_required = t.is_required
    cteForm.tcvn_note = t.tcvn_note ?? ''
}

function submitEditCte() {
    cteForm.put(route('sys.config.cte.update', editingCte.value.id), {
        onSuccess: () => { editingCte.value = null },
    })
}

// ── Helpers ───────────────────────────────────────────────
const catName = (id) => props.categories.find(c => c.id === id)?.name_vi ?? '—'

const cteHeaders = [
    { key: 'step', label: 'Bước' },
    { key: 'code', label: 'CTE Code' },
    { key: 'name', label: 'Tên sự kiện' },
    { key: 'category', label: 'Danh mục' },
    { key: 'required', label: 'Bắt buộc' },
    { key: 'tcvn', label: 'TCVN' },
    { key: 'actions', label: '' },
]

const catHeaders = [
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Tên danh mục' },
    { key: 'tcvn', label: 'TCVN tham chiếu' },
    { key: 'batches', label: 'Số lô' },
]
</script>

<template>

    <Head title="Cấu hình CTE / TCVN" />

    <div class="space-y-6">

        <!-- Header -->
        <div class="rounded-2xl border border-glass bg-black/40 p-6 flex items-center justify-between"
            data-aos="fade-right">
            <div>
                <div class="text-brand-300 text-sm font-semibold">Hệ thống</div>
                <div class="text-2xl font-bold mt-1 text-white/90">Cấu hình CTE / TCVN</div>
                <div class="text-white/50 text-sm mt-1">Quản lý danh mục sản phẩm và các bước truy xuất chuẩn quốc gia.
                </div>
            </div>
            <UiButton @click="showAddCat = true">+ Thêm danh mục</UiButton>
        </div>

        <!-- Tabs -->
        <div class="flex gap-2">
            <button v-for="t in [{ key: 'cte', label: 'CTE Templates' }, { key: 'cat', label: 'Danh mục sản phẩm' }]"
                :key="t.key" @click="activeTab = t.key" :class="activeTab === t.key
                    ? 'border-brand-500/50 bg-brand-500/10 text-brand-200'
                    : 'border-glass bg-black/10 text-white/60 hover:bg-white/5'"
                class="px-4 py-2 rounded-xl text-sm font-bold border transition">
                {{ t.label }}
            </button>
        </div>

        <!-- CTE Templates tab -->
        <div v-if="activeTab === 'cte'" data-aos="fade-up">

            <!-- Filter theo category -->
            <div class="flex gap-2 flex-wrap mb-4">
                <button @click="filterCatId = null"
                    :class="!filterCatId ? 'bg-brand-500/15 text-brand-300 border-brand-500/30' : 'text-white/50 border-glass hover:bg-white/5'"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold border transition">
                    Tất cả ({{ cte_templates.length }})
                </button>
                <button v-for="c in categories" :key="c.id" @click="filterCatId = c.id"
                    :class="filterCatId === c.id ? 'bg-brand-500/15 text-brand-300 border-brand-500/30' : 'text-white/50 border-glass hover:bg-white/5'"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold border transition">
                    {{ c.name_vi }} ({{cte_templates.filter(t => t.category_id === c.id).length}})
                </button>
            </div>

            <UiCard :subtitle="`${filteredCte.length} templates`">
                <UiTable :headers="cteHeaders">
                    <tr v-for="t in filteredCte" :key="t.id" class="hover:bg-white/5 transition">
                        <td class="px-4 py-3 text-white/60 text-xs font-mono">{{ t.step_order }}</td>
                        <td class="px-4 py-3">
                            <code class="text-brand-300 text-xs bg-brand-500/10 px-2 py-0.5 rounded">{{ t.code }}</code>
                        </td>
                        <td class="px-4 py-3 font-semibold text-white/90 text-sm">{{ t.name_vi }}</td>
                        <td class="px-4 py-3 text-white/60 text-xs">{{ catName(t.category_id) }}</td>
                        <td class="px-4 py-3">
                            <UiBadge :variant="t.is_required ? 'orange' : 'gray'">
                                {{ t.is_required ? 'Bắt buộc' : 'Tùy chọn' }}
                            </UiBadge>
                        </td>
                        <td class="px-4 py-3 text-white/40 text-xs">{{ t.tcvn_note ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <UiButton variant="outline" size="sm" @click="openEditCte(t)">Sửa</UiButton>
                        </td>
                    </tr>
                </UiTable>
            </UiCard>
        </div>

        <!-- Danh mục tab -->
        <div v-if="activeTab === 'cat'" data-aos="fade-up">
            <UiCard :subtitle="`${categories.length} danh mục`">
                <UiTable :headers="catHeaders">
                    <tr v-for="c in categories" :key="c.id" class="hover:bg-white/5 transition">
                        <td class="px-4 py-3">
                            <code class="text-brand-300 text-xs bg-brand-500/10 px-2 py-0.5 rounded">{{ c.code }}</code>
                        </td>
                        <td class="px-4 py-3 font-semibold text-white/90">{{ c.name_vi }}</td>
                        <td class="px-4 py-3 text-white/50 text-xs">{{ c.tcvn_ref ?? '—' }}</td>
                        <td class="px-4 py-3 text-white/70 text-sm">{{ c.batches_count ?? 0 }}</td>
                    </tr>
                </UiTable>
            </UiCard>
        </div>

    </div>

    <!-- Modal thêm danh mục -->
    <UiModal :show="showAddCat" title="Thêm danh mục sản phẩm" @close="showAddCat = false">
        <div class="space-y-4">
            <UiInput label="Code (VD: lua_gao)" v-model="catForm.code" placeholder="lua_gao" />
            <UiInput label="Tên tiếng Việt" v-model="catForm.name_vi" placeholder="Lúa gạo" />
            <UiInput label="TCVN tham chiếu" v-model="catForm.tcvn_ref" placeholder="TCVN 12850:2019" />
        </div>
        <template #actions>
            <UiButton variant="outline" size="sm" @click="showAddCat = false">Hủy</UiButton>
            <UiButton size="sm" @click="submitCat" :disabled="catForm.processing">Tạo danh mục</UiButton>
        </template>
    </UiModal>

    <!-- Modal sửa CTE -->
    <UiModal :show="!!editingCte" :title="`Sửa CTE: ${editingCte?.code}`" @close="editingCte = null">
        <div class="space-y-4">
            <UiInput label="Tên sự kiện (tiếng Việt)" v-model="cteForm.name_vi" />
            <UiInput label="Ghi chú TCVN" v-model="cteForm.tcvn_note" placeholder="VD: TCVN 12850:2019 Điều 5.2" />
            <div class="flex items-center gap-3">
                <input type="checkbox" id="is_required" v-model="cteForm.is_required"
                    class="w-4 h-4 accent-brand-500" />
                <label for="is_required" class="text-sm text-white/70">Bắt buộc theo tiêu chuẩn</label>
            </div>
        </div>
        <template #actions>
            <UiButton variant="outline" size="sm" @click="editingCte = null">Hủy</UiButton>
            <UiButton size="sm" @click="submitEditCte" :disabled="cteForm.processing">Lưu thay đổi</UiButton>
        </template>
    </UiModal>
</template>