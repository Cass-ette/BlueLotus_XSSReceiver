<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useScriptsStore } from '../../stores/scripts'
import ConfirmDialog from '../common/ConfirmDialog.vue'

const store = useScriptsStore()
const showNewDialog = ref(false)
const showClearDialog = ref(false)
const newName = ref('')
const newDesc = ref('')

onMounted(() => {
  store.fetchScripts()
})

function handleNew() {
  if (!newName.value.trim()) return
  store.createScript({
    name: newName.value.trim(),
    description: newDesc.value,
    content: '',
  })
  newName.value = ''
  newDesc.value = ''
  showNewDialog.value = false
}

function handleClear() {
  store.clearScripts()
  showClearDialog.value = false
}
</script>

<template>
  <div class="bg-white rounded-xl border border-slate-200 flex flex-col h-full">
    <!-- Type tabs -->
    <div class="flex border-b border-slate-200">
      <button
        @click="store.setType('myjs')"
        class="flex-1 py-3 text-sm font-medium transition border-b-2"
        :class="store.activeType === 'myjs'
          ? 'text-blue-600 border-blue-600'
          : 'text-slate-500 border-transparent hover:text-slate-700'"
      >
        我的 JS
      </button>
      <button
        @click="store.setType('template')"
        class="flex-1 py-3 text-sm font-medium transition border-b-2"
        :class="store.activeType === 'template'
          ? 'text-blue-600 border-blue-600'
          : 'text-slate-500 border-transparent hover:text-slate-700'"
      >
        JS 模板
      </button>
    </div>

    <!-- Toolbar -->
    <div class="flex items-center gap-2 p-3 border-b border-slate-100">
      <button @click="showNewDialog = true" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        新建
      </button>
      <button @click="store.fetchScripts()" class="px-3 py-1.5 text-xs bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200">
        刷新
      </button>
      <button
        @click="showClearDialog = true"
        class="px-3 py-1.5 text-xs bg-red-50 text-red-600 rounded-lg hover:bg-red-100"
        :disabled="store.scripts.length === 0"
      >
        清空
      </button>
    </div>

    <!-- List -->
    <div class="flex-1 overflow-auto">
      <div v-if="store.loading" class="p-6 text-center text-slate-400 text-sm">加载中...</div>
      <div v-else-if="store.scripts.length === 0" class="p-6 text-center text-slate-400 text-sm">暂无脚本</div>
      <button
        v-for="script in store.scripts"
        :key="script.id"
        @click="store.loadScript(script.id)"
        class="w-full text-left px-4 py-3 border-b border-slate-50 hover:bg-blue-50/50 transition"
        :class="store.currentScript?.id === script.id ? 'bg-blue-50' : ''"
      >
        <div class="text-sm font-medium text-slate-800 truncate">{{ script.name }}</div>
        <div class="text-xs text-slate-500 truncate mt-0.5">{{ script.description || '无描述' }}</div>
      </button>
    </div>

    <!-- New script dialog -->
    <div v-if="showNewDialog" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showNewDialog = false">
      <div class="bg-white rounded-xl p-6 w-96 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-800 mb-4">新建脚本</h3>
        <form @submit.prevent="handleNew" class="space-y-3">
          <input
            v-model="newName"
            type="text"
            placeholder="脚本名称"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 text-sm"
            autofocus
          />
          <input
            v-model="newDesc"
            type="text"
            placeholder="描述（可选）"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 text-sm"
          />
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showNewDialog = false" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">
              取消
            </button>
            <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              创建
            </button>
          </div>
        </form>
      </div>
    </div>

    <ConfirmDialog
      v-if="showClearDialog"
      title="确认清空"
      :message="`确定要清空所有${store.activeType === 'myjs' ? '我的 JS' : 'JS 模板'}吗？`"
      confirm-text="清空"
      :danger="true"
      @confirm="handleClear"
      @cancel="showClearDialog = false"
    />
  </div>
</template>
