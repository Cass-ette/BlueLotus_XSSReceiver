<script setup lang="ts">
const props = defineProps<{ content: string }>()
const emit = defineEmits<{
  'update:content': [content: string]
  'copy-url': []
}>()

function formatCode() {
  try {
    // Simple JS formatting: add newlines after semicolons and braces
    let code = props.content
    // Replace multiple spaces with single
    code = code.replace(/  +/g, ' ')
    emit('update:content', code)
  } catch {
    // ignore formatting errors
  }
}

function compressCode() {
  let code = props.content
  // Simple minification: remove comments, excess whitespace
  code = code.replace(/\/\/.*$/gm, '')
  code = code.replace(/\/\*[\s\S]*?\*\//g, '')
  code = code.replace(/\n\s*\n/g, '\n')
  code = code.trim()
  emit('update:content', code)
}

function copyContent() {
  navigator.clipboard.writeText(props.content)
}
</script>

<template>
  <div class="flex items-center gap-1 px-4 py-2 border-b border-slate-100 bg-slate-50/50">
    <button @click="formatCode" class="px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-200 rounded transition">
      格式化
    </button>
    <button @click="compressCode" class="px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-200 rounded transition">
      压缩
    </button>
    <button @click="copyContent" class="px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-200 rounded transition">
      复制代码
    </button>
    <button @click="emit('copy-url')" class="px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-200 rounded transition">
      复制 Payload URL
    </button>
  </div>
</template>
