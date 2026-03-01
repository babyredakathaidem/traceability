<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { computed, onMounted, ref, watch } from 'vue'
//import AuthLayout from '@/Layouts/AuthLayout.vue'

const API_BASE = 'https://provinces.open-api.vn/api/v2'
const provinces = ref([])
const wards = ref([])
const loadingProvinces = ref(false)
const loadingWards = ref(false)

const form = useForm({
  name:'',
  business_code:'',
  business_code_issued_at:'',
  business_cert_no:'',
  business_cert_issued_place:'',
  business_license_no:'',
  business_license_issued_place:'',
  province_code:null,
  province:'',
  ward_code:null,
  ward:'',
  district:'', // map ward -> district
  address_detail:'',
  phone:'',
  email:'',
  representative_name:'',
  representative_id:'',
  business_cert_file:null,
  accept_terms:false,
})

const inputCls = 'mt-2 w-full rounded-lg border border-glass bg-black/25 px-4 py-2.5 text-white/90 placeholder:text-white/40 focus:border-brand-500/60 focus:ring-brand-500/20 hover:border-brand-500/30 transition'
const selectCls = 'mt-2 w-full rounded-lg border border-glass bg-black/25 px-4 py-2.5 text-white/90 focus:border-brand-500/60 focus:ring-brand-500/20 hover:border-brand-500/30 transition cursor-pointer'

async function loadProvinces(){
  loadingProvinces.value = true
  try{
    const res = await fetch(`${API_BASE}/p/`)
    provinces.value = await res.json()
  } finally { loadingProvinces.value = false }
}

async function loadWardsByProvinceCode(provinceCode){
  wards.value = []
  if(!provinceCode) return
  loadingWards.value = true
  try{
    const res = await fetch(`${API_BASE}/p/${provinceCode}?depth=2`)
    const json = await res.json()
    wards.value = Array.isArray(json?.wards) ? json.wards : []
  } finally { loadingWards.value = false }
}

watch(()=>form.province_code, async(code)=>{
  form.ward_code = null
  form.ward = ''
  form.district = ''
  wards.value = []
  const p = provinces.value.find(x=>x.code===code)
  form.province = p?.name ?? ''
  await loadWardsByProvinceCode(code)
})

watch(()=>form.ward_code, (code)=>{
  const w = wards.value.find(x=>x.code===code)
  form.ward = w?.name ?? ''
  form.district = form.ward
})

onMounted(loadProvinces)

const onFileChange = (e)=>{ form.business_cert_file = e.target.files?.[0] ?? null }

const canSubmit = computed(()=> Boolean(
  form.name && form.business_code && form.province && form.ward && form.phone && form.email && form.business_cert_file && form.accept_terms && !form.processing
))

const submit = ()=> form.post('/onboarding/enterprise', { forceFormData:true, preserveScroll:true })
</script>

<template>
  <Head title="Đăng ký tài khoản" />
  <AuthLayout title="Đăng ký tài khoản" subtitle="Dành cho Doanh nghiệp, thương nhân, tổ chức, hộ kinh doanh">
    <form class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5" @submit.prevent="submit">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-white/70">Tên doanh nghiệp <span class="text-red-400">*</span></label>
        <input v-model="form.name" :class="inputCls" placeholder="Nhập tên đầy đủ của doanh nghiệp" />
        <div v-if="form.errors.name" class="text-sm text-red-400 mt-1">{{ form.errors.name }}</div>
      </div>

      <div>
        <label class="block text-sm font-medium text-white/70">Mã số doanh nghiệp <span class="text-red-400">*</span></label>
        <input v-model="form.business_code" :class="inputCls" placeholder="Nhập mã số doanh nghiệp" />
        <div v-if="form.errors.business_code" class="text-sm text-red-400 mt-1">{{ form.errors.business_code }}</div>
      </div>

      <div>
        <label class="block text-sm font-medium text-white/70">Ngày cấp mã số doanh nghiệp</label>
        <input v-model="form.business_code_issued_at" type="date" :class="inputCls" />
      </div>

      <div>
        <label class="block text-sm font-medium text-white/70">Số giấy chứng nhận đăng ký DN</label>
        <input v-model="form.business_cert_no" :class="inputCls" placeholder="Nhập số giấy chứng nhận" />
      </div>

      <div>
        <label class="block text-sm font-medium text-white/70">Nơi cấp giấy chứng nhận đăng ký DN</label>
        <input v-model="form.business_cert_issued_place" :class="inputCls" placeholder="Nhập nơi cấp" />
      </div>

      <div>
        <label class="block text-sm font-medium text-white/70">Số giấy phép kinh doanh</label>
        <input v-model="form.business_license_no" :class="inputCls" placeholder="Nhập số giấy phép" />
      </div>

      <div>
        <label class="block text-sm font-medium text-white/70">Nơi cấp giấy phép doanh nghiệp</label>
        <input v-model="form.business_license_issued_place" :class="inputCls" placeholder="Nhập nơi cấp" />
      </div>

      <div>
        <label class="block text-sm font-medium text-white/70">Tỉnh/Thành phố <span class="text-red-400">*</span></label>
        <select v-model.number="form.province_code" :class="selectCls" :disabled="loadingProvinces">
          <option :value="null">{{ loadingProvinces ? 'Đang tải...' : 'Chọn tỉnh/thành phố' }}</option>
          <option v-for="p in provinces" :key="p.code" :value="Number(p.code)">{{ p.name }}</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-white/70">Xã/Phường/Thị trấn <span class="text-red-400">*</span></label>
        <select v-model.number="form.ward_code" :class="selectCls" :disabled="!form.province_code || loadingWards">
          <option :value="null">{{ !form.province_code ? 'Chọn tỉnh trước' : (loadingWards ? 'Đang tải...' : 'Chọn xã/phường/thị trấn') }}</option>
          <option v-for="w in wards" :key="w.code" :value="Number(w.code)">{{ w.name }}</option>
        </select>
        <div v-if="form.errors.district" class="text-sm text-red-400 mt-1">{{ form.errors.district }}</div>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-white/70">Địa chỉ cụ thể</label>
        <input v-model="form.address_detail" :class="inputCls" placeholder="Số nhà, tên đường, ấp/khu phố..." />
      </div>

      <div>
        <label class="block text-sm font-medium text-white/70">Số điện thoại <span class="text-red-400">*</span></label>
        <input v-model="form.phone" :class="inputCls" placeholder="Nhập số điện thoại" />
      </div>

      <div>
        <label class="block text-sm font-medium text-white/70">Email doanh nghiệp <span class="text-red-400">*</span></label>
        <input v-model="form.email" type="email" :class="inputCls" placeholder="Nhập email doanh nghiệp" />
      </div>

      <div>
        <label class="block text-sm font-medium text-white/70">Họ tên người đại diện</label>
        <input v-model="form.representative_name" :class="inputCls" placeholder="Nhập họ tên đại diện" />
      </div>

      <div>
        <label class="block text-sm font-medium text-white/70">Số CCCD/Hộ chiếu</label>
        <input v-model="form.representative_id" :class="inputCls" placeholder="Nhập số CCCD/Hộ chiếu" />
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-white/70">Tải GCN đăng ký doanh nghiệp <span class="text-red-400">*</span></label>
        <input type="file" accept=".pdf,.jpg,.jpeg,.png"
          class="mt-2 block w-full text-sm text-white/70 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-500 file:px-4 file:py-2 file:font-extrabold file:text-cosmic-950 hover:file:bg-brand-600 transition"
          @change="onFileChange" />
        <div class="text-xs text-white/50 mt-1">pdf/jpg/png, tối đa 20MB</div>
        <div v-if="form.errors.business_cert_file" class="text-sm text-red-400 mt-1">{{ form.errors.business_cert_file }}</div>
      </div>

      <div class="md:col-span-2 flex items-start gap-3">
        <input type="checkbox" v-model="form.accept_terms" class="mt-1 rounded border-glass bg-black/30" />
        <div class="text-sm text-white/70">
          Tôi đã đọc và đồng ý với <a class="text-brand-300 hover:underline" href="#">Chính sách</a> &
          <a class="text-brand-300 hover:underline" href="#">điều khoản sử dụng</a>
          <div v-if="form.errors.accept_terms" class="text-sm text-red-400 mt-1">{{ form.errors.accept_terms }}</div>
        </div>
      </div>

      <div class="md:col-span-2">
        <button type="submit" :disabled="!canSubmit"
          class="w-full rounded-lg bg-brand-500 text-cosmic-950 font-extrabold py-2.5 hover:bg-brand-600 transition disabled:opacity-50 disabled:cursor-not-allowed">
          {{ form.processing ? 'Đang đăng ký...' : 'ĐĂNG KÝ' }}
        </button>
        <div class="mt-3 text-center text-sm text-white/60">
          Bạn đã có tài khoản? <a href="/login" class="text-brand-300 hover:underline font-semibold">Đăng nhập</a>
        </div>
      </div>
    </form>
  </AuthLayout>
</template>