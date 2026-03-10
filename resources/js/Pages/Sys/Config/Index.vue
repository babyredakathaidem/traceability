<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
    Settings, 
    Plus, 
    Edit2, 
    CheckCircle, 
    FileText,
    Tag,
    Workflow,
    ExternalLink
} from 'lucide-vue-next';

const props = defineProps({
    categories: Array,
    cte_templates: Array
});

const activeTab = ref('categories');
const editingTemplate = ref(null);

const catForm = useForm({
    code: '',
    name_vi: '',
    tcvn_ref: ''
});

const templateForm = useForm({
    name_vi: '',
    is_required: false,
    kde_schema: [],
    tcvn_note: ''
});

const submitCategory = () => {
    catForm.post(route('sys.config.categories.store'), {
        onSuccess: () => catForm.reset()
    });
};

const openEditTemplate = (tpl) => {
    editingTemplate.value = tpl;
    templateForm.name_vi = tpl.name_vi;
    templateForm.is_required = !!tpl.is_required;
    templateForm.kde_schema = [...tpl.kde_schema];
    templateForm.tcvn_note = tpl.tcvn_note || '';
};

const updateTemplate = () => {
    templateForm.put(route('sys.config.cte.update', editingTemplate.value.id), {
        onSuccess: () => editingTemplate.value = null
    });
};
</script>

<template>
    <Head title="Cấu hình hệ thống" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Cấu hình tiêu chuẩn TRACE (Thông tư 02)
                </h2>
                <div class="flex bg-gray-100 p-1 rounded-lg">
                    <button @click="activeTab = 'categories'" 
                            :class="[activeTab === 'categories' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700']"
                            class="px-4 py-1.5 rounded-md text-sm font-medium transition-all duration-200">
                        Danh mục SP
                    </button>
                    <button @click="activeTab = 'templates'" 
                            :class="[activeTab === 'templates' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700']"
                            class="px-4 py-1.5 rounded-md text-sm font-medium transition-all duration-200">
                        Sự kiện chuẩn (CTE)
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Category Section -->
                <div v-if="activeTab === 'categories'" class="space-y-6">
                    <div class="bg-white p-6 shadow-sm rounded-2xl border border-gray-100 mb-8 animate-slide-in">
                        <h3 class="text-lg font-bold mb-4 flex items-center">
                            <Plus class="w-5 h-5 mr-2 text-blue-500" />
                            Thêm loại sản phẩm mới
                        </h3>
                        <form @submit.prevent="submitCategory" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Mã (code)</label>
                                <input v-model="catForm.code" type="text" class="w-full rounded-xl border-gray-200 focus:ring-blue-500" placeholder="rau_qua" required />
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Tên hiển thị</label>
                                <input v-model="catForm.name_vi" type="text" class="w-full rounded-xl border-gray-200 focus:ring-blue-500" placeholder="Rau quả tươi" required />
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-500 mb-1">TCVN áp dụng</label>
                                <input v-model="catForm.tcvn_ref" type="text" class="w-full rounded-xl border-gray-200 focus:ring-blue-500" placeholder="TCVN 12827:2023" />
                            </div>
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all flex items-center justify-center">
                                <CheckCircle class="w-4 h-4 mr-2" /> Lưu danh mục
                            </button>
                        </form>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div v-for="cat in categories" :key="cat.id" 
                             class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all group">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center group-hover:bg-blue-50 transition-colors">
                                    <Tag class="w-6 h-6 text-gray-400 group-hover:text-blue-500" />
                                </div>
                                <span class="text-xs font-bold bg-gray-100 px-2 py-1 rounded">{{ cat.code }}</span>
                            </div>
                            <h4 class="text-lg font-bold text-gray-800">{{ cat.name_vi }}</h4>
                            <p class="text-sm text-gray-500 mt-1">{{ cat.tcvn_ref || 'Chưa áp dụng tiêu chuẩn' }}</p>
                            <div class="mt-4 pt-4 border-t border-gray-50 flex items-center justify-between">
                                <span class="text-xs text-gray-400">{{ cat.batches_count }} lô hàng đã áp dụng</span>
                                <button class="text-blue-600 hover:text-blue-800"><Edit2 class="w-4 h-4" /></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTE Template Section -->
                <div v-if="activeTab === 'templates'" class="animate-slide-in">
                    <div class="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Sự kiện (CTE)</th>
                                    <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Loại SP</th>
                                    <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Dữ liệu KDE</th>
                                    <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Bắt buộc</th>
                                    <th class="px-6 py-4 text-right"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="tpl in cte_templates" :key="tpl.id" class="hover:bg-gray-50/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center mr-3 font-bold text-xs">
                                                {{ tpl.step_order }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900">{{ tpl.name_vi }}</p>
                                                <p class="text-xs text-gray-400">{{ tpl.code }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ tpl.category?.name_vi }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex -space-x-2">
                                            <div v-for="field in tpl.kde_schema.slice(0, 3)" :key="field.key" 
                                                 class="w-8 h-8 rounded-full border-2 border-white bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-600 uppercase"
                                                 :title="field.label">
                                                {{ field.w[0] }}
                                            </div>
                                            <div v-if="tpl.kde_schema.length > 3" class="w-8 h-8 rounded-full border-2 border-white bg-blue-100 flex items-center justify-center text-[10px] font-bold text-blue-600">
                                                +{{ tpl.kde_schema.length - 3 }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="tpl.is_required" class="bg-red-50 text-red-600 px-2 py-1 rounded text-xs font-bold">Bắt buộc</span>
                                        <span v-else class="text-gray-400 text-xs">Tùy chọn</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="openEditTemplate(tpl)" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                                            <Settings class="w-5 h-5" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Edit Template Modal -->
        <div v-if="editingTemplate" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden animate-scale-in">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center">
                        <Workflow class="w-6 h-6 mr-2 text-indigo-600" />
                        Cấu hình sự kiện: {{ editingTemplate.name_vi }}
                    </h3>
                    <button @click="editingTemplate = null" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="p-8 max-h-[70vh] overflow-y-auto">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Tên sự kiện</label>
                            <input v-model="templateForm.name_vi" type="text" class="w-full rounded-xl border-gray-200" />
                        </div>
                        <div class="flex items-center">
                            <input v-model="templateForm.is_required" type="checkbox" id="req" class="rounded text-blue-600" />
                            <label for="req" class="ml-2 text-sm font-medium text-gray-700 text-red-500 font-bold">Bắt buộc phải có trong quy trình</label>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Cấu trúc dữ liệu (KDE Schema)</label>
                            <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 space-y-3">
                                <div v-for="(field, idx) in templateForm.kde_schema" :key="idx" class="flex gap-2 items-center">
                                    <select v-model="field.w" class="text-xs rounded-lg border-gray-200">
                                        <option value="WHO">WHO</option>
                                        <option value="WHAT">WHAT</option>
                                        <option value="WHERE">WHERE</option>
                                        <option value="WHEN">WHEN</option>
                                        <option value="WHY">WHY</option>
                                    </select>
                                    <input v-model="field.label" type="text" class="flex-1 text-xs rounded-lg border-gray-200" placeholder="Nhãn hiển thị" />
                                    <button @click="templateForm.kde_schema.splice(idx, 1)" class="text-red-400 hover:text-red-600">&times;</button>
                                </div>
                                <button @click="templateForm.kde_schema.push({w: 'WHAT', label: '', key: '', type: 'text'})" 
                                        class="w-full py-2 border-2 border-dashed border-gray-200 rounded-xl text-sm text-gray-400 hover:border-blue-300 hover:text-blue-500 transition-all">
                                    + Thêm trường dữ liệu
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2 text-blue-600 flex items-center">
                                <ExternalLink class="w-4 h-4 mr-1" /> Ghi chú TCVN
                            </label>
                            <textarea v-model="templateForm.tcvn_note" class="w-full rounded-xl border-gray-200 text-sm" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="p-6 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button @click="editingTemplate = null" class="px-6 py-2 text-gray-500 font-bold">Hủy</button>
                    <button @click="updateTemplate" class="px-8 py-2 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200">
                        Cập nhật tiêu chuẩn
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-slide-in {
    animation: slideIn 0.5s ease-out both;
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out both;
}
.animate-scale-in {
    animation: scaleIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>
