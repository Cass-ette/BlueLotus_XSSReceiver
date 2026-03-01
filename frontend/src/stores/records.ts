import { defineStore } from 'pinia'
import { ref } from 'vue'
import { recordsApi } from '../api'

export interface Record {
  id: number
  user_ip: string
  user_port: string
  protocol: string
  request_method: string
  request_uri: string
  request_time: number
  location: string
  headers_data: Record<string, string> | null
  get_data: Record<string, string> | null
  decoded_get_data: Record<string, string> | null
  post_data: Record<string, string> | null
  decoded_post_data: Record<string, string> | null
  cookie_data: Record<string, string> | null
  decoded_cookie_data: Record<string, string> | null
  keepsession: number
  created_at: string
}

export interface RecordListResponse {
  data: Record[]
  total: number
  page: number
  limit: number
  pages: number
}

export const useRecordsStore = defineStore('records', () => {
  const records = ref<Record[]>([])
  const total = ref(0)
  const page = ref(1)
  const limit = ref(25)
  const pages = ref(0)
  const search = ref('')
  const loading = ref(false)

  async function fetchRecords() {
    loading.value = true
    try {
      const res = await recordsApi.list({
        page: page.value,
        limit: limit.value,
        search: search.value,
      })
      const data: RecordListResponse = res.data
      records.value = data.data
      total.value = data.total
      pages.value = data.pages
    } finally {
      loading.value = false
    }
  }

  async function deleteRecord(id: number) {
    await recordsApi.delete(id)
    await fetchRecords()
  }

  async function clearRecords() {
    await recordsApi.clear()
    records.value = []
    total.value = 0
    pages.value = 0
  }

  function setPage(p: number) {
    page.value = p
    fetchRecords()
  }

  function setSearch(s: string) {
    search.value = s
    page.value = 1
    fetchRecords()
  }

  return { records, total, page, limit, pages, search, loading, fetchRecords, deleteRecord, clearRecords, setPage, setSearch }
})
