<script setup lang="ts">
import { ref } from 'vue'
import { useRecordsStore } from '../../stores/records'

const store = useRecordsStore()
const searchInput = ref(store.search)
let debounceTimer: ReturnType<typeof setTimeout> | null = null

function handleInput() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    store.setSearch(searchInput.value)
  }, 300)
}

function handleClear() {
  searchInput.value = ''
  store.setSearch('')
}
</script>

<template>
  <div class="relative">
    <input
      v-model="searchInput"
      @input="handleInput"
      type="text"
      placeholder="搜索 IP / 数据..."
      class="w-56 pl-3 pr-8 py-1.5 text-sm border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
    />
    <button
      v-if="searchInput"
      @click="handleClear"
      class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs"
    >
      &times;
    </button>
  </div>
</template>
