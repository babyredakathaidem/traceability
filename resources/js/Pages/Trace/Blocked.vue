<script setup>
import { Head } from "@inertiajs/vue3";
defineProps({ title: String, message: String, distance_m: Number, radius_m: Number });
</script>

<template>
  <Head :title="title" />
  <div class="min-h-screen flex items-center justify-center p-6">
    <div class="bg-white border rounded-2xl p-6 max-w-md w-full space-y-3">
      <h1 class="text-lg font-semibold">{{ title }}</h1>
      <p class="text-sm text-gray-700">{{ message }}</p>

      <div v-if="distance_m !== undefined && radius_m !== undefined" class="text-sm text-gray-600">
        Khoảng cách: <b>{{ distance_m }}m</b> — Bán kính cho phép: <b>{{ radius_m }}m</b>
      </div>
    </div>
  </div>
</template>
