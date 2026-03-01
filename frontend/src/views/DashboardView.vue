<script setup lang="ts">
import { ref } from 'vue'
import Sidebar from '../components/layout/Sidebar.vue'
import Header from '../components/layout/Header.vue'
import RecordTable from '../components/records/RecordTable.vue'
import RecordDetail from '../components/records/RecordDetail.vue'
import ScriptList from '../components/scripts/ScriptList.vue'
import ScriptEditor from '../components/scripts/ScriptEditor.vue'
import EncoderTool from '../components/tools/EncoderTool.vue'

const activeTab = ref<'records' | 'scripts' | 'encoder'>('records')
const selectedRecordId = ref<number | null>(null)

function showRecordDetail(id: number) {
  selectedRecordId.value = id
}

function closeRecordDetail() {
  selectedRecordId.value = null
}
</script>

<template>
  <div class="flex h-screen overflow-hidden">
    <Sidebar :active-tab="activeTab" @change="activeTab = $event" />

    <div class="flex-1 flex flex-col overflow-hidden">
      <Header />

      <main class="flex-1 overflow-auto p-6">
        <div v-if="activeTab === 'records'" class="h-full">
          <RecordTable @show-detail="showRecordDetail" />
          <RecordDetail
            v-if="selectedRecordId !== null"
            :record-id="selectedRecordId"
            @close="closeRecordDetail"
          />
        </div>

        <div v-else-if="activeTab === 'scripts'" class="h-full flex gap-4">
          <ScriptList class="w-80 shrink-0" />
          <ScriptEditor class="flex-1" />
        </div>

        <div v-else-if="activeTab === 'encoder'" class="h-full">
          <EncoderTool />
        </div>
      </main>
    </div>
  </div>
</template>
