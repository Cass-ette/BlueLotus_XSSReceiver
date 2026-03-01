<script setup lang="ts">
defineProps<{
  title: string
  message: string
  confirmText?: string
  danger?: boolean
}>()

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="emit('cancel')">
    <div class="bg-white rounded-xl p-6 w-96 shadow-xl">
      <h3 class="text-lg font-semibold text-slate-800 mb-2">{{ title }}</h3>
      <p class="text-sm text-slate-600 mb-6">{{ message }}</p>
      <div class="flex justify-end gap-2">
        <button @click="emit('cancel')" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">
          取消
        </button>
        <button
          @click="emit('confirm')"
          class="px-4 py-2 text-sm text-white rounded-lg"
          :class="danger ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'"
        >
          {{ confirmText || '确认' }}
        </button>
      </div>
    </div>
  </div>
</template>
