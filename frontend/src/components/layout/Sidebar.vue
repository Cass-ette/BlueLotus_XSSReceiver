<script setup lang="ts">
defineProps<{
  activeTab: string
}>()

const emit = defineEmits<{
  change: [tab: 'records' | 'scripts' | 'encoder']
}>()

const tabs = [
  { key: 'records' as const, label: 'XSS 记录', icon: '&#9641;' },
  { key: 'scripts' as const, label: 'JS 管理', icon: '&#9998;' },
  { key: 'encoder' as const, label: '编码工具', icon: '&#9881;' },
]
</script>

<template>
  <aside class="w-56 bg-slate-900 text-white flex flex-col shrink-0">
    <div class="p-5 border-b border-slate-700">
      <h1 class="text-lg font-bold tracking-wide">BlueLotus</h1>
      <p class="text-xs text-slate-400 mt-0.5">XSS Receiver</p>
    </div>

    <nav class="flex-1 py-4">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="emit('change', tab.key)"
        class="w-full flex items-center gap-3 px-5 py-3 text-left text-sm transition-colors"
        :class="activeTab === tab.key
          ? 'bg-blue-600 text-white'
          : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
      >
        <span v-html="tab.icon" class="text-base"></span>
        {{ tab.label }}
      </button>
    </nav>

    <div class="p-4 border-t border-slate-700 text-xs text-slate-500">
      v4.0.0
    </div>
  </aside>
</template>
