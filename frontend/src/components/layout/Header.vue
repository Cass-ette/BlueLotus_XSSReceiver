<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import ChangePasswordDialog from '../common/ChangePasswordDialog.vue'

const auth = useAuthStore()
const router = useRouter()
const showMenu = ref(false)
const showPasswordDialog = ref(false)

function handleLogout() {
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <header class="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0">
    <div class="text-sm text-slate-600">
      XSS 数据接收平台
    </div>

    <div class="relative">
      <button
        @click="showMenu = !showMenu"
        class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 transition"
      >
        <span class="w-7 h-7 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-medium">
          {{ auth.username.charAt(0).toUpperCase() }}
        </span>
        {{ auth.username }}
      </button>

      <div
        v-if="showMenu"
        class="absolute right-0 top-full mt-1 w-40 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-50"
        @click="showMenu = false"
      >
        <button
          @click="showPasswordDialog = true"
          class="w-full text-left px-4 py-2 text-sm text-slate-600 hover:bg-slate-50"
        >
          修改密码
        </button>
        <button
          @click="handleLogout"
          class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
        >
          退出登录
        </button>
      </div>
    </div>

    <ChangePasswordDialog v-if="showPasswordDialog" @close="showPasswordDialog = false" />
  </header>
</template>
