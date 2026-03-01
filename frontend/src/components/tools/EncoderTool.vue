<script setup lang="ts">
import { ref, computed } from 'vue'

const input = ref('')
const output = ref('')
const activeEncoder = ref('base64_encode')

const encoders = [
  { key: 'base64_encode', label: 'Base64 编码' },
  { key: 'base64_decode', label: 'Base64 解码' },
  { key: 'url_encode', label: 'URL 编码' },
  { key: 'url_decode', label: 'URL 解码' },
  { key: 'unicode_encode', label: 'Unicode 编码 (\\uXXXX)' },
  { key: 'unicode_decode', label: 'Unicode 解码' },
  { key: 'html_encode', label: 'HTML 实体编码' },
  { key: 'html_decode', label: 'HTML 实体解码' },
  { key: 'hex_encode', label: 'Hex 编码 (\\xXX)' },
  { key: 'hex_decode', label: 'Hex 解码' },
  { key: 'decimal_encode', label: 'HTML 十进制 (&#XX;)' },
  { key: 'html2js', label: 'HTML → JS String' },
  { key: 'js2html', label: 'JS String → HTML' },
]

function process() {
  try {
    switch (activeEncoder.value) {
      case 'base64_encode':
        output.value = btoa(unescape(encodeURIComponent(input.value)))
        break
      case 'base64_decode':
        output.value = decodeURIComponent(escape(atob(input.value)))
        break
      case 'url_encode':
        output.value = encodeURIComponent(input.value)
        break
      case 'url_decode':
        output.value = decodeURIComponent(input.value)
        break
      case 'unicode_encode':
        output.value = Array.from(input.value)
          .map(c => '\\u' + c.charCodeAt(0).toString(16).padStart(4, '0'))
          .join('')
        break
      case 'unicode_decode':
        output.value = input.value.replace(/\\u([0-9a-fA-F]{4})/g, (_, hex) =>
          String.fromCharCode(parseInt(hex, 16))
        )
        break
      case 'html_encode':
        output.value = input.value
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#39;')
        break
      case 'html_decode': {
        const el = document.createElement('textarea')
        el.innerHTML = input.value
        output.value = el.value
        break
      }
      case 'hex_encode':
        output.value = Array.from(input.value)
          .map(c => '\\x' + c.charCodeAt(0).toString(16).padStart(2, '0'))
          .join('')
        break
      case 'hex_decode':
        output.value = input.value.replace(/\\x([0-9a-fA-F]{2})/g, (_, hex) =>
          String.fromCharCode(parseInt(hex, 16))
        )
        break
      case 'decimal_encode':
        output.value = Array.from(input.value)
          .map(c => '&#' + c.charCodeAt(0) + ';')
          .join('')
        break
      case 'html2js':
        output.value = JSON.stringify(input.value)
        break
      case 'js2html':
        output.value = JSON.parse(input.value)
        break
      default:
        output.value = input.value
    }
  } catch (e: any) {
    output.value = `错误: ${e.message}`
  }
}

function swap() {
  const temp = input.value
  input.value = output.value
  output.value = temp
}

function copyOutput() {
  navigator.clipboard.writeText(output.value)
}

function clear() {
  input.value = ''
  output.value = ''
}
</script>

<template>
  <div class="max-w-4xl mx-auto">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">编码/解码工具</h2>

    <!-- Encoder selector -->
    <div class="flex flex-wrap gap-2 mb-4">
      <button
        v-for="enc in encoders"
        :key="enc.key"
        @click="activeEncoder = enc.key"
        class="px-3 py-1.5 text-xs rounded-lg transition"
        :class="activeEncoder === enc.key
          ? 'bg-blue-600 text-white'
          : 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50'"
      >
        {{ enc.label }}
      </button>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <!-- Input -->
      <div class="flex flex-col">
        <label class="text-sm font-medium text-slate-600 mb-2">输入</label>
        <textarea
          v-model="input"
          class="flex-1 min-h-[300px] p-4 font-mono text-sm border border-slate-300 rounded-xl outline-none focus:ring-2 focus:ring-blue-500 resize-none"
          placeholder="输入要编码/解码的内容..."
        ></textarea>
      </div>

      <!-- Output -->
      <div class="flex flex-col">
        <label class="text-sm font-medium text-slate-600 mb-2">输出</label>
        <textarea
          v-model="output"
          readonly
          class="flex-1 min-h-[300px] p-4 font-mono text-sm border border-slate-300 rounded-xl bg-slate-50 outline-none resize-none"
          placeholder="结果将显示在这里..."
        ></textarea>
      </div>
    </div>

    <div class="flex items-center gap-3 mt-4">
      <button @click="process" class="px-5 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
        转换
      </button>
      <button @click="swap" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm rounded-lg hover:bg-slate-200 transition">
        交换输入/输出
      </button>
      <button @click="copyOutput" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm rounded-lg hover:bg-slate-200 transition">
        复制结果
      </button>
      <button @click="clear" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm rounded-lg hover:bg-slate-200 transition">
        清空
      </button>
    </div>
  </div>
</template>
