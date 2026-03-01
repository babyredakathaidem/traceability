<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  staffList: Array,
  availablePermissions: Object,
})

// ── Add form ─────────────────────────────────────────────
const showAdd = ref(false)
const addForm = useForm({
  name: '',
  email: '',
  password: '',
  permissions: [],
})

function submitAdd() {
  addForm.post('/enterprise/users', {
    onSuccess: () => {
      addForm.reset()
      showAdd.value = false
    },
  })
}

// ── Edit form ─────────────────────────────────────────────
const editingUser = ref(null)
const editForm = useForm({ name: '', permissions: [] })

function openEdit(user) {
  editingUser.value = user
  editForm.name = user.name
  editForm.permissions = [...(user.permissions ?? [])]
}

function submitEdit() {
  editForm.put(`/enterprise/users/${editingUser.value.id}`, {
    onSuccess: () => { editingUser.value = null },
  })
}

// ── Delete ───────────────────────────────────────────────
function deleteUser(userId) {
  if (!confirm('Xóa nhân viên này?')) return
  router.delete(`/enterprise/users/${userId}`)
}

// ── Permission labels ────────────────────────────────────
function permLabel(key) {
  return props.availablePermissions[key] ?? key
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <div class="text-sm text-white/40 uppercase tracking-wider">Quản trị</div>
        <h1 class="text-2xl font-extrabold text-white/90 mt-1">Tài khoản nhân sự</h1>
      </div>
      <button
        @click="showAdd = !showAdd"
        class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-black font-bold rounded-xl text-sm transition"
      >
        + Thêm nhân viên
      </button>
    </div>

    <!-- Add form -->
    <div v-if="showAdd" class="bg-white/5 border border-glass rounded-2xl p-5 mb-6">
      <h3 class="text-white/80 font-bold mb-4">Thêm nhân viên mới</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="text-xs text-white/50 mb-1 block">Họ tên</label>
          <input v-model="addForm.name" type="text" placeholder="Nguyễn Văn A"
            class="w-full bg-white/5 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none focus:border-brand-500" />
          <div v-if="addForm.errors.name" class="text-red-400 text-xs mt-1">{{ addForm.errors.name }}</div>
        </div>
        <div>
          <label class="text-xs text-white/50 mb-1 block">Email</label>
          <input v-model="addForm.email" type="email" placeholder="nv@congty.com"
            class="w-full bg-white/5 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none focus:border-brand-500" />
          <div v-if="addForm.errors.email" class="text-red-400 text-xs mt-1">{{ addForm.errors.email }}</div>
        </div>
        <div>
          <label class="text-xs text-white/50 mb-1 block">Mật khẩu tạm</label>
          <input v-model="addForm.password" type="password" placeholder="••••••••"
            class="w-full bg-white/5 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none focus:border-brand-500" />
          <div v-if="addForm.errors.password" class="text-red-400 text-xs mt-1">{{ addForm.errors.password }}</div>
        </div>
      </div>

      <div class="mb-4">
        <label class="text-xs text-white/50 mb-2 block">Phân quyền</label>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
          <label v-for="(label, key) in availablePermissions" :key="key"
            class="flex items-center gap-2 cursor-pointer group">
            <input type="checkbox" :value="key" v-model="addForm.permissions"
              class="accent-brand-500" />
            <span class="text-xs text-white/70 group-hover:text-white/90">{{ label }}</span>
          </label>
        </div>
      </div>

      <div class="flex gap-2">
        <button @click="submitAdd" :disabled="addForm.processing"
          class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-black font-bold rounded-xl text-sm transition disabled:opacity-50">
          Lưu
        </button>
        <button @click="showAdd = false"
          class="px-4 py-2 border border-glass hover:bg-white/5 text-white/70 rounded-xl text-sm transition">
          Hủy
        </button>
      </div>
    </div>

    <!-- Staff list -->
    <div class="bg-white/5 border border-glass rounded-2xl overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-white/5 text-white/40 text-xs uppercase tracking-wider">
            <th class="px-4 py-3 text-left">Nhân viên</th>
            <th class="px-4 py-3 text-left">Quyền được gán</th>
            <th class="px-4 py-3 text-left">Ngày tạo</th>
            <th class="px-4 py-3 text-right">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="staffList.length === 0">
            <td colspan="4" class="px-4 py-8 text-center text-white/30">Chưa có nhân viên nào</td>
          </tr>
          <tr v-for="u in staffList" :key="u.id" class="border-b border-white/5 hover:bg-white/3 transition">
            <td class="px-4 py-3">
              <div class="font-semibold text-white/90">{{ u.name }}</div>
              <div class="text-xs text-white/40">{{ u.email }}</div>
            </td>
            <td class="px-4 py-3">
              <div v-if="u.permissions?.length" class="flex flex-wrap gap-1">
                <span v-for="p in u.permissions" :key="p"
                  class="px-2 py-0.5 bg-brand-500/15 text-brand-300 text-[10px] rounded-full border border-brand-500/30">
                  {{ permLabel(p) }}
                </span>
              </div>
              <span v-else class="text-white/30 text-xs">Không có quyền</span>
            </td>
            <td class="px-4 py-3 text-white/40 text-xs">{{ u.created_at?.slice(0, 10) }}</td>
            <td class="px-4 py-3 text-right">
              <div class="flex gap-2 justify-end">
                <button @click="openEdit(u)"
                  class="px-3 py-1.5 border border-glass hover:bg-white/5 text-white/70 rounded-lg text-xs transition">
                  Sửa
                </button>
                <button @click="deleteUser(u.id)"
                  class="px-3 py-1.5 border border-red-500/30 hover:bg-red-500/10 text-red-400 rounded-lg text-xs transition">
                  Xóa
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Edit modal -->
    <div v-if="editingUser"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
      @click.self="editingUser = null">
      <div class="bg-cosmic-900 border border-glass rounded-2xl p-6 w-full max-w-lg mx-4">
        <h3 class="text-white/90 font-bold mb-4">Sửa nhân viên: {{ editingUser.name }}</h3>

        <div class="mb-4">
          <label class="text-xs text-white/50 mb-1 block">Họ tên</label>
          <input v-model="editForm.name" type="text"
            class="w-full bg-white/5 border border-glass rounded-xl px-3 py-2 text-sm text-white/90 outline-none focus:border-brand-500" />
        </div>

        <div class="mb-5">
          <label class="text-xs text-white/50 mb-2 block">Phân quyền</label>
          <div class="grid grid-cols-2 gap-2">
            <label v-for="(label, key) in availablePermissions" :key="key"
              class="flex items-center gap-2 cursor-pointer group">
              <input type="checkbox" :value="key" v-model="editForm.permissions" class="accent-brand-500" />
              <span class="text-xs text-white/70 group-hover:text-white/90">{{ label }}</span>
            </label>
          </div>
        </div>

        <div class="flex gap-2">
          <button @click="submitEdit" :disabled="editForm.processing"
            class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-black font-bold rounded-xl text-sm transition disabled:opacity-50">
            Lưu
          </button>
          <button @click="editingUser = null"
            class="px-4 py-2 border border-glass hover:bg-white/5 text-white/70 rounded-xl text-sm transition">
            Hủy
          </button>
        </div>
      </div>
    </div>
  </div>
</template>