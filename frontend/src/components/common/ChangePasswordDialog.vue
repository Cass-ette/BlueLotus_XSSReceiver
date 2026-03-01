<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth'

const emit = defineEmits<{ close: [] }>()
const auth = useAuthStore()

const oldPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const error = ref('')
const success = ref(false)
const loading = ref(false)

async function handleSubmit() {
  error.value = ''
  if (!oldPassword.value || !newPassword.value) {
    error.value = '请填写所有字段'
    return
  }
  if (newPassword.value !== confirmPassword.value) {
    error.value = '两次输入的新密码不一致'
    return
  }
  if (newPassword.value.length < 6) {
    error.value = '新密码长度至少 6 位'
    return
  }
  loading.value = true
  try {
    await auth.changePassword(oldPassword.value, newPassword.value)
    success.value = true
    setTimeout(() => emit('close'), 1500)
  } catch (e: any) {
    error.value = e.response?.data?.error || '修改失败'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="emit('close')">
    <div class="bg-white rounded-xl p-6 w-96 shadow-xl">
      <h3 class="text-lg font-semibold text-slate-800 mb-4">修改密码</h3>

      <div v-if="success" class="text-green-600 bg-green-50 p-3 rounded-lg text-sm mb-4">
        密码修改成功
      </div>

      <form v-else @submit.prevent="handleSubmit" class="space-y-3">
        <input
          v-model="oldPassword"
          type="password"
          placeholder="当前密码"
          class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 text-sm"
        />
        <input
          v-model="newPassword"
          type="password"
          placeholder="新密码"
          class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 text-sm"
        />
        <input
          v-model="confirmPassword"
          type="password"
          placeholder="确认新密码"
          class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 text-sm"
        />

        <div v-if="error" class="text-red-500 text-sm">{{ error }}</div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="emit('close')" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">
            取消
          </button>
          <button type="submit" :disabled="loading" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ loading ? '提交中...' : '确认修改' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
