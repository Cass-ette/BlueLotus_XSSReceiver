import { defineStore } from 'pinia'
import { ref } from 'vue'
import { scriptsApi } from '../api'

export interface Script {
  id: number
  name: string
  description: string
  content: string
  type: string
  created_at: string
  updated_at: string
}

export const useScriptsStore = defineStore('scripts', () => {
  const scripts = ref<Script[]>([])
  const currentScript = ref<Script | null>(null)
  const activeType = ref<'myjs' | 'template'>('myjs')
  const loading = ref(false)

  async function fetchScripts() {
    loading.value = true
    try {
      const res = await scriptsApi.list(activeType.value)
      scripts.value = res.data
    } finally {
      loading.value = false
    }
  }

  async function loadScript(id: number) {
    const res = await scriptsApi.get(id)
    currentScript.value = res.data
  }

  async function createScript(data: { name: string; description: string; content: string }) {
    await scriptsApi.create({ ...data, type: activeType.value })
    await fetchScripts()
  }

  async function updateScript(id: number, data: { name: string; description: string; content: string }) {
    await scriptsApi.update(id, data)
    await fetchScripts()
  }

  async function deleteScript(id: number) {
    await scriptsApi.delete(id)
    if (currentScript.value?.id === id) {
      currentScript.value = null
    }
    await fetchScripts()
  }

  async function clearScripts() {
    await scriptsApi.clear(activeType.value)
    scripts.value = []
    currentScript.value = null
  }

  function setType(type: 'myjs' | 'template') {
    activeType.value = type
    currentScript.value = null
    fetchScripts()
  }

  return { scripts, currentScript, activeType, loading, fetchScripts, loadScript, createScript, updateScript, deleteScript, clearScripts, setType }
})
