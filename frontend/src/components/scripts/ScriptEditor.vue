<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useScriptsStore } from '../../stores/scripts'
import ScriptToolbar from './ScriptToolbar.vue'
import ConfirmDialog from '../common/ConfirmDialog.vue'

const store = useScriptsStore()
const editorContent = ref('')
const editorName = ref('')
const editorDesc = ref('')
const showDeleteDialog = ref(false)
const saved = ref(false)

const isEditing = computed(() => !!store.currentScript)

watch(() => store.currentScript, (script) => {
  if (script) {
    editorContent.value = script.content || ''
    editorName.value = script.name
    editorDesc.value = script.description || ''
    saved.value = false
  }
}, { immediate: true })

async function handleSave() {
  if (!store.currentScript) return
  await store.updateScript(store.currentScript.id, {
    name: editorName.value,
    description: editorDesc.value,
    content: editorContent.value,
  })
  saved.value = true
  setTimeout(() => saved.value = false, 2000)
}

function handleDelete() {
  if (!store.currentScript) return
  store.deleteScript(store.currentScript.id)
  showDeleteDialog.value = false
}

function handleContentUpdate(content: string) {
  editorContent.value = content
}

function copyPayloadUrl() {
  if (!store.currentScript) return
  const base = window.location.origin
  const url = `${base}/js/${store.currentScript.name}.js`
  navigator.clipboard.writeText(`<script src="${url}"><\/script>`)
}
</script>

<template>
  <div class="bg-white rounded-xl border border-slate-200 flex flex-col h-full">
    <!-- No selection -->
    <div v-if="!isEditing" class="flex-1 flex items-center justify-center text-slate-400 text-sm">
      选择一个脚本开始编辑
    </div>

    <!-- Editor -->
    <template v-else>
      <!-- Header -->
      <div class="flex items-center gap-3 p-4 border-b border-slate-200">
        <input
          v-model="editorName"
          class="flex-1 px-3 py-1.5 text-sm border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="脚本名称"
        />
        <input
          v-model="editorDesc"
          class="flex-1 px-3 py-1.5 text-sm border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="描述"
        />
      </div>

      <ScriptToolbar
        :content="editorContent"
        @update:content="handleContentUpdate"
        @copy-url="copyPayloadUrl"
      />

      <!-- Code editor (textarea for simplicity, can be replaced with Monaco later) -->
      <div class="flex-1 relative">
        <textarea
          v-model="editorContent"
          class="w-full h-full p-4 font-mono text-sm resize-none outline-none bg-slate-50 border-0"
          spellcheck="false"
          placeholder="// 在此编写 JavaScript..."
        ></textarea>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-between p-3 border-t border-slate-200">
        <div class="flex items-center gap-2">
          <button
            @click="handleSave"
            class="px-4 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
          >
            保存
          </button>
          <span v-if="saved" class="text-xs text-green-600">已保存</span>
        </div>
        <button
          @click="showDeleteDialog = true"
          class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition"
        >
          删除
        </button>
      </div>
    </template>

    <ConfirmDialog
      v-if="showDeleteDialog"
      title="确认删除"
      :message="`确定要删除脚本「${editorName}」吗？`"
      confirm-text="删除"
      :danger="true"
      @confirm="handleDelete"
      @cancel="showDeleteDialog = false"
    />
  </div>
</template>
