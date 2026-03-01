<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRecordsStore } from '../../stores/records'
import RecordSearch from './RecordSearch.vue'
import ConfirmDialog from '../common/ConfirmDialog.vue'

const emit = defineEmits<{ 'show-detail': [id: number] }>()

const store = useRecordsStore()
const showClearDialog = ref(false)
let pollTimer: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  store.fetchRecords()
  // Poll for new records every 5 seconds
  pollTimer = setInterval(() => {
    store.fetchRecords()
  }, 5000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})

function formatTime(ts: number) {
  return new Date(ts * 1000).toLocaleString('zh-CN')
}

function handleClear() {
  store.clearRecords()
  showClearDialog.value = false
}

function detectBrowser(headers: Record<string, string> | null): string {
  if (!headers) return '未知'
  const ua = headers['User-Agent'] || headers['user-agent'] || ''
  if (ua.includes('Chrome') && !ua.includes('Edg')) return 'Chrome'
  if (ua.includes('Firefox')) return 'Firefox'
  if (ua.includes('Safari') && !ua.includes('Chrome')) return 'Safari'
  if (ua.includes('Edg')) return 'Edge'
  if (ua.includes('MSIE') || ua.includes('Trident')) return 'IE'
  return '其他'
}

function getDataSummary(record: any): string {
  const parts: string[] = []
  if (record.get_data && Object.keys(record.get_data).length > 0) {
    parts.push(`GET:${Object.keys(record.get_data).length}`)
  }
  if (record.post_data && Object.keys(record.post_data).length > 0) {
    parts.push(`POST:${Object.keys(record.post_data).length}`)
  }
  if (record.cookie_data && Object.keys(record.cookie_data).length > 0) {
    parts.push(`Cookie:${Object.keys(record.cookie_data).length}`)
  }
  return parts.join(' ') || '-'
}
</script>

<template>
  <div class="h-full flex flex-col">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-slate-800">
        XSS 记录
        <span class="text-sm font-normal text-slate-500 ml-2">共 {{ store.total }} 条</span>
      </h2>
      <div class="flex items-center gap-3">
        <RecordSearch />
        <button
          @click="store.fetchRecords()"
          class="px-3 py-1.5 text-sm bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition"
        >
          刷新
        </button>
        <button
          @click="showClearDialog = true"
          class="px-3 py-1.5 text-sm bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition"
          :disabled="store.total === 0"
        >
          清空
        </button>
      </div>
    </div>

    <div class="flex-1 overflow-auto bg-white rounded-xl border border-slate-200">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 sticky top-0">
          <tr class="border-b border-slate-200">
            <th class="text-left px-4 py-3 font-medium text-slate-600">时间</th>
            <th class="text-left px-4 py-3 font-medium text-slate-600">IP</th>
            <th class="text-left px-4 py-3 font-medium text-slate-600">归属地</th>
            <th class="text-left px-4 py-3 font-medium text-slate-600">浏览器</th>
            <th class="text-left px-4 py-3 font-medium text-slate-600">方法</th>
            <th class="text-left px-4 py-3 font-medium text-slate-600">数据</th>
            <th class="text-left px-4 py-3 font-medium text-slate-600">KeepSession</th>
            <th class="text-center px-4 py-3 font-medium text-slate-600">操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="store.loading && store.records.length === 0">
            <td colspan="8" class="text-center py-12 text-slate-400">加载中...</td>
          </tr>
          <tr v-else-if="store.records.length === 0">
            <td colspan="8" class="text-center py-12 text-slate-400">暂无记录</td>
          </tr>
          <tr
            v-for="record in store.records"
            :key="record.id"
            class="border-b border-slate-100 hover:bg-blue-50/50 cursor-pointer transition"
            @click="emit('show-detail', record.id)"
          >
            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ formatTime(record.request_time) }}</td>
            <td class="px-4 py-3 font-mono text-xs">{{ record.user_ip }}</td>
            <td class="px-4 py-3 text-slate-600">{{ record.location || '-' }}</td>
            <td class="px-4 py-3 text-slate-600">{{ detectBrowser(record.headers_data as any) }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded text-xs font-medium"
                :class="record.request_method === 'GET'
                  ? 'bg-green-100 text-green-700'
                  : 'bg-orange-100 text-orange-700'"
              >
                {{ record.request_method }}
              </span>
            </td>
            <td class="px-4 py-3 text-slate-600 text-xs">{{ getDataSummary(record) }}</td>
            <td class="px-4 py-3 text-center">
              <span v-if="record.keepsession" class="text-green-600 text-xs font-medium">ON</span>
              <span v-else class="text-slate-400 text-xs">OFF</span>
            </td>
            <td class="px-4 py-3 text-center">
              <button
                @click.stop="store.deleteRecord(record.id)"
                class="text-red-500 hover:text-red-700 text-xs transition"
              >
                删除
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="store.pages > 1" class="flex items-center justify-center gap-2 mt-4">
      <button
        @click="store.setPage(store.page - 1)"
        :disabled="store.page <= 1"
        class="px-3 py-1.5 text-sm bg-white border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-40 transition"
      >
        上一页
      </button>
      <span class="text-sm text-slate-600">{{ store.page }} / {{ store.pages }}</span>
      <button
        @click="store.setPage(store.page + 1)"
        :disabled="store.page >= store.pages"
        class="px-3 py-1.5 text-sm bg-white border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-40 transition"
      >
        下一页
      </button>
    </div>

    <ConfirmDialog
      v-if="showClearDialog"
      title="确认清空"
      message="确定要清空所有 XSS 记录吗？此操作不可撤销。"
      confirm-text="清空"
      :danger="true"
      @confirm="handleClear"
      @cancel="showClearDialog = false"
    />
  </div>
</template>
