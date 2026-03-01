<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { recordsApi } from '../../api'

const props = defineProps<{ recordId: number }>()
const emit = defineEmits<{ close: [] }>()

const record = ref<any>(null)
const loading = ref(true)
const activeTab = ref('headers')

const tabs = [
  { key: 'headers', label: 'Headers' },
  { key: 'get', label: 'GET' },
  { key: 'post', label: 'POST' },
  { key: 'cookie', label: 'Cookie' },
]

onMounted(async () => {
  try {
    const res = await recordsApi.get(props.recordId)
    record.value = res.data
  } finally {
    loading.value = false
  }
})

function formatTime(ts: number) {
  return new Date(ts * 1000).toLocaleString('zh-CN')
}

function getTabData(tab: string): Record<string, string> | null {
  if (!record.value) return null
  switch (tab) {
    case 'headers': return record.value.headers_data
    case 'get': return record.value.get_data
    case 'post': return record.value.post_data
    case 'cookie': return record.value.cookie_data
    default: return null
  }
}

function getDecodedData(tab: string): Record<string, string> | null {
  if (!record.value) return null
  switch (tab) {
    case 'get': return record.value.decoded_get_data
    case 'post': return record.value.decoded_post_data
    case 'cookie': return record.value.decoded_cookie_data
    default: return null
  }
}

function copyToClipboard(text: string) {
  navigator.clipboard.writeText(text)
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="emit('close')">
    <div class="bg-white rounded-xl w-[800px] max-h-[80vh] flex flex-col shadow-xl">
      <!-- Header -->
      <div class="flex items-center justify-between p-5 border-b border-slate-200">
        <h3 class="text-lg font-semibold text-slate-800">记录详情 #{{ recordId }}</h3>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 text-xl">&times;</button>
      </div>

      <div v-if="loading" class="p-12 text-center text-slate-400">加载中...</div>

      <div v-else-if="record" class="flex-1 overflow-auto">
        <!-- Meta info -->
        <div class="p-5 grid grid-cols-3 gap-4 border-b border-slate-100 text-sm">
          <div>
            <span class="text-slate-500">IP:</span>
            <span class="ml-2 font-mono">{{ record.user_ip }}:{{ record.user_port }}</span>
          </div>
          <div>
            <span class="text-slate-500">归属地:</span>
            <span class="ml-2">{{ record.location || '-' }}</span>
          </div>
          <div>
            <span class="text-slate-500">时间:</span>
            <span class="ml-2">{{ formatTime(record.request_time) }}</span>
          </div>
          <div>
            <span class="text-slate-500">协议:</span>
            <span class="ml-2">{{ record.protocol }}</span>
          </div>
          <div>
            <span class="text-slate-500">方法:</span>
            <span class="ml-2 font-medium">{{ record.request_method }}</span>
          </div>
          <div>
            <span class="text-slate-500">URI:</span>
            <span class="ml-2 font-mono text-xs break-all">{{ record.request_uri }}</span>
          </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-slate-200 flex">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            class="px-5 py-3 text-sm font-medium transition border-b-2"
            :class="activeTab === tab.key
              ? 'text-blue-600 border-blue-600'
              : 'text-slate-500 border-transparent hover:text-slate-700'"
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- Data table -->
        <div class="p-5">
          <table class="w-full text-sm" v-if="getTabData(activeTab) && Object.keys(getTabData(activeTab)!).length > 0">
            <thead>
              <tr class="border-b border-slate-200">
                <th class="text-left py-2 px-3 font-medium text-slate-600 w-1/3">Key</th>
                <th class="text-left py-2 px-3 font-medium text-slate-600">Value</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(value, key) in getTabData(activeTab)"
                :key="key"
                class="border-b border-slate-50"
              >
                <td class="py-2 px-3 font-mono text-xs text-slate-700">{{ key }}</td>
                <td class="py-2 px-3 font-mono text-xs text-slate-600 break-all">
                  <span
                    class="cursor-pointer hover:text-blue-600"
                    @click="copyToClipboard(String(value))"
                    :title="'点击复制'"
                  >{{ value }}</span>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-else class="text-center text-slate-400 py-8 text-sm">无数据</div>

          <!-- Decoded data -->
          <div v-if="getDecodedData(activeTab) && Object.keys(getDecodedData(activeTab)!).length > 0" class="mt-4">
            <h4 class="text-sm font-medium text-slate-600 mb-2">Base64 解码后</h4>
            <table class="w-full text-sm">
              <tbody>
                <tr
                  v-for="(value, key) in getDecodedData(activeTab)"
                  :key="key"
                  class="border-b border-slate-50"
                >
                  <td class="py-2 px-3 font-mono text-xs text-slate-700 w-1/3">{{ key }}</td>
                  <td class="py-2 px-3 font-mono text-xs text-slate-600 break-all">{{ value }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
