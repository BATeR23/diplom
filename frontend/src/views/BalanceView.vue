<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Баланс</span>
      <h1 class="section-heading">Управление балансом</h1>
      <p class="subheading">
        Пополните баланс для оплаты поездок. После завершения поездки средства будут автоматически списаны.
      </p>

      <div class="surface-card p-8 space-y-6">
        <div class="text-center">
          <p class="text-sm uppercase tracking-[0.3em] text-gray-600 mb-2">Текущий баланс</p>
          <p class="text-5xl font-semibold text-gray-900">{{ balance.toFixed(2) }} ₽</p>
        </div>

        <div class="border-t border-gray-200 pt-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Пополнить баланс</h2>
          <form @submit.prevent="handleRecharge" class="space-y-4">
            <div class="form-field">
              <label for="amount">Сумма пополнения (₽)</label>
              <input
                id="amount"
                v-model.number="rechargeForm.amount"
                type="number"
                min="1"
                max="10000"
                step="0.01"
                required
                placeholder="100.00"
              />
            </div>
            <div class="form-field">
              <label for="receipt">Чек (PDF файл)</label>
              <input
                id="receipt"
                type="file"
                accept=".pdf"
                required
                @change="handleFileChange"
                class="mt-2 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
              />
              <p class="text-xs text-gray-500 mt-1">Максимальный размер файла: 10MB</p>
            </div>
            <p v-if="errorMessage" class="text-sm text-rose-600">{{ errorMessage }}</p>
            <p v-if="successMessage" class="text-sm text-emerald-600">{{ successMessage }}</p>
            <button type="submit" class="primary-btn w-full" :disabled="loading">
              {{ loading ? 'Отправка...' : 'Отправить запрос на пополнение' }}
            </button>
          </form>
        </div>
      </div>

      <div class="surface-card p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">История транзакций</h2>
        <div v-if="loadingHistory" class="text-center text-gray-600 py-8">
          Загрузка...
        </div>
        <div v-else-if="transactions.length" class="space-y-3">
          <div
            v-for="transaction in transactions"
            :key="transaction.id"
            class="flex items-center justify-between p-4 rounded-lg bg-gray-50 border border-gray-200"
          >
            <div>
              <p class="font-semibold text-gray-900">{{ transaction.description || getTransactionTypeLabel(transaction.type) }}</p>
              <p class="text-sm text-gray-600">{{ formatDate(transaction.created_at) }}</p>
            </div>
            <div class="text-right">
              <p :class="transaction.amount >= 0 ? 'text-emerald-600' : 'text-red-600'" class="font-semibold">
                {{ transaction.amount >= 0 ? '+' : '' }}{{ transaction.amount.toFixed(2) }} ₽
              </p>
              <p class="text-xs text-gray-500">Баланс: {{ transaction.balance_after.toFixed(2) }} ₽</p>
            </div>
          </div>
        </div>
        <div v-else class="text-center text-gray-600 py-8">
          Нет транзакций
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import http from '@/helpers/http'
import dayjs from 'dayjs'

const balance = ref(0)
const loading = ref(false)
const loadingHistory = ref(false)
const transactions = ref([])
const errorMessage = ref('')
const successMessage = ref('')

const rechargeForm = reactive({
  amount: '',
  receipt: null,
})

const fetchBalance = async () => {
  try {
    const { data } = await http.get('/balance')
    balance.value = parseFloat(data.balance) || 0
  } catch (error) {
    console.error('Ошибка загрузки баланса:', error)
  }
}

const fetchHistory = async () => {
  loadingHistory.value = true
  try {
    const { data } = await http.get('/balance/history')
    transactions.value = data.data || data
  } catch (error) {
    console.error('Ошибка загрузки истории:', error)
  } finally {
    loadingHistory.value = false
  }
}

const handleFileChange = (event) => {
  rechargeForm.receipt = event.target.files[0]
}

const handleRecharge = async () => {
  if (!rechargeForm.receipt) {
    errorMessage.value = 'Пожалуйста, выберите файл чека'
    return
  }

  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('amount', rechargeForm.amount)
    formData.append('receipt', rechargeForm.receipt)

    await http.post('/balance/recharge', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    successMessage.value = 'Запрос на пополнение отправлен. Ожидайте подтверждения администратора.'
    rechargeForm.amount = ''
    rechargeForm.receipt = null
    document.getElementById('receipt').value = ''
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Ошибка отправки запроса'
  } finally {
    loading.value = false
  }
}

const getTransactionTypeLabel = (type) => {
  const labels = {
    recharge: 'Пополнение баланса',
    payment: 'Оплата поездки',
    refund: 'Возврат средств',
  }
  return labels[type] || type
}

const formatDate = (value) => dayjs(value).format('D MMMM YYYY, HH:mm')

onMounted(() => {
  fetchBalance()
  fetchHistory()
})
</script>


