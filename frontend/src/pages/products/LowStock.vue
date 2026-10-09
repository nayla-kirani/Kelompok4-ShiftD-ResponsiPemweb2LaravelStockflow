```vue
<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

import api from '@/api/client'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { Product } from '@/types'

const products = ref<Product[]>([])
const loading = ref(true)
const errorMessage = ref('')

/**
 * Mengambil daftar produk dengan stok rendah.
 */
async function fetchLowStockProducts(): Promise<void> {
  loading.value = true
  errorMessage.value = ''

  try {
    const { data } = await api.get('/products', {
      params: {
        stock_status: 'low',
        per_page: 50,
      },
    })

    products.value = Array.isArray(data.data) ? data.data : []
  } catch (error: unknown) {
    console.error('Failed to fetch low stock products:', error)

    errorMessage.value =
      'Failed to load low stock products. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchLowStockProducts)
</script>

<template>
  <section class="space-y-6">
    <!-- Page Header -->
    <header class="flex items-center gap-3">
      <ExclamationTriangleIcon
        class="h-7 w-7 shrink-0 text-warning-500"
        aria-hidden="true"
      />

      <div>
        <h2 class="text-2xl font-bold text-gray-900">
          Low Stock Alerts
        </h2>

        <p class="mt-1 text-sm text-gray-500">
          Monitor products that need restocking.
        </p>
      </div>
    </header>

    <!-- Loading State -->
    <div
      v-if="loading"
      class="flex items-center justify-center rounded-xl bg-white py-20 shadow-sm ring-1 ring-gray-200"
      role="status"
      aria-live="polite"
    >
      <div
        class="h-8 w-8 animate-spin rounded-full border-4 border-primary-200 border-t-primary-600"
        aria-hidden="true"
      />

      <span class="sr-only">Loading low stock products...</span>
    </div>

    <!-- Error State -->
    <div
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-6"
      role="alert"
    >
      <p class="font-medium text-red-700">
        {{ errorMessage }}
      </p>

      <button
        type="button"
        class="mt-4 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
        @click="fetchLowStockProducts"
      >
        Try Again
      </button>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="products.length === 0"
      class="rounded-xl bg-white px-6 py-12 text-center shadow-sm ring-1 ring-gray-200"
    >
      <ExclamationTriangleIcon
        class="mx-auto h-10 w-10 text-gray-400"
        aria-hidden="true"
      />

      <h3 class="mt-3 text-base font-semibold text-gray-900">
        No Low Stock Items
      </h3>

      <p class="mt-1 text-sm text-gray-500">
        All products are currently above their low-stock threshold.
      </p>
    </div>

    <!-- Low Stock Products Table -->
    <div
      v-else
      class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200"
    >
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-gray-50">
            <tr
              class="border-b border-gray-200 text-xs font-semibold uppercase tracking-wide text-gray-500"
            >
              <th scope="col" class="whitespace-nowrap px-4 py-3">
                SKU
              </th>

              <th scope="col" class="whitespace-nowrap px-4 py-3">
                Product Name
              </th>

              <th scope="col" class="whitespace-nowrap px-4 py-3">
                Category
              </th>

              <th scope="col" class="whitespace-nowrap px-4 py-3">
                Current Qty
              </th>

              <th scope="col" class="whitespace-nowrap px-4 py-3">
                Min. Level
              </th>

              <th scope="col" class="whitespace-nowrap px-4 py-3">
                Status
              </th>

              <th scope="col" class="whitespace-nowrap px-4 py-3">
                Action
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="product in products"
              :key="product.id"
              class="transition-colors hover:bg-gray-50"
            >
              <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600">
                {{ product.sku }}
              </td>

              <td class="px-4 py-3 font-medium text-gray-900">
                {{ product.name }}
              </td>

              <td class="px-4 py-3 text-gray-600">
                {{ product.category?.name ?? '—' }}
              </td>

              <td class="whitespace-nowrap px-4 py-3 font-semibold text-danger-600">
                {{ product.quantity }} {{ product.unit }}
              </td>

              <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                {{ product.min_stock_level }}
              </td>

              <td class="whitespace-nowrap px-4 py-3">
                <StatusBadge
                  :status="product.quantity <= 0 ? 'out' : 'low'"
                />
              </td>

              <td class="whitespace-nowrap px-4 py-3">
                <RouterLink
                  :to="`/products/${product.id}`"
                  class="font-medium text-primary-600 transition hover:text-primary-500 focus:outline-none focus:underline"
                >
                  View
                  <span aria-hidden="true">→</span>
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Table Footer -->
      <footer
        class="border-t border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-500"
      >
        Showing {{ products.length }} low-stock
        {{ products.length === 1 ? 'product' : 'products' }}.
      </footer>
    </div>
  </section>
</template>
``````vue
<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

import api from '@/api/client'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { Product } from '@/types'

const products = ref<Product[]>([])
const loading = ref(true)
const errorMessage = ref('')

/**
 * Mengambil daftar produk dengan stok rendah.
 */
async function fetchLowStockProducts(): Promise<void> {
  loading.value = true
  errorMessage.value = ''

  try {
    const { data } = await api.get('/products', {
      params: {
        stock_status: 'low',
        per_page: 50,
      },
    })

    products.value = Array.isArray(data.data) ? data.data : []
  } catch (error: unknown) {
    console.error('Failed to fetch low stock products:', error)

    errorMessage.value =
      'Failed to load low stock products. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchLowStockProducts)
</script>

<template>
  <section class="space-y-6">
    <!-- Page Header -->
    <header class="flex items-center gap-3">
      <ExclamationTriangleIcon
        class="h-7 w-7 shrink-0 text-warning-500"
        aria-hidden="true"
      />

      <div>
        <h2 class="text-2xl font-bold text-gray-900">
          Low Stock Alerts
        </h2>

        <p class="mt-1 text-sm text-gray-500">
          Monitor products that need restocking.
        </p>
      </div>
    </header>

    <!-- Loading State -->
    <div
      v-if="loading"
      class="flex items-center justify-center rounded-xl bg-white py-20 shadow-sm ring-1 ring-gray-200"
      role="status"
      aria-live="polite"
    >
      <div
        class="h-8 w-8 animate-spin rounded-full border-4 border-primary-200 border-t-primary-600"
        aria-hidden="true"
      />

      <span class="sr-only">Loading low stock products...</span>
    </div>

    <!-- Error State -->
    <div
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-6"
      role="alert"
    >
      <p class="font-medium text-red-700">
        {{ errorMessage }}
      </p>

      <button
        type="button"
        class="mt-4 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
        @click="fetchLowStockProducts"
      >
        Try Again
      </button>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="products.length === 0"
      class="rounded-xl bg-white px-6 py-12 text-center shadow-sm ring-1 ring-gray-200"
    >
      <ExclamationTriangleIcon
        class="mx-auto h-10 w-10 text-gray-400"
        aria-hidden="true"
      />

      <h3 class="mt-3 text-base font-semibold text-gray-900">
        No Low Stock Items
      </h3>

      <p class="mt-1 text-sm text-gray-500">
        All products are currently above their low-stock threshold.
      </p>
    </div>

    <!-- Low Stock Products Table -->
    <div
      v-else
      class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200"
    >
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-gray-50">
            <tr
              class="border-b border-gray-200 text-xs font-semibold uppercase tracking-wide text-gray-500"
            >
              <th scope="col" class="whitespace-nowrap px-4 py-3">
                SKU
              </th>

              <th scope="col" class="whitespace-nowrap px-4 py-3">
                Product Name
              </th>

              <th scope="col" class="whitespace-nowrap px-4 py-3">
                Category
              </th>

              <th scope="col" class="whitespace-nowrap px-4 py-3">
                Current Qty
              </th>

              <th scope="col" class="whitespace-nowrap px-4 py-3">
                Min. Level
              </th>

              <th scope="col" class="whitespace-nowrap px-4 py-3">
                Status
              </th>

              <th scope="col" class="whitespace-nowrap px-4 py-3">
                Action
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="product in products"
              :key="product.id"
              class="transition-colors hover:bg-gray-50"
            >
              <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600">
                {{ product.sku }}
              </td>

              <td class="px-4 py-3 font-medium text-gray-900">
                {{ product.name }}
              </td>

              <td class="px-4 py-3 text-gray-600">
                {{ product.category?.name ?? '—' }}
              </td>

              <td class="whitespace-nowrap px-4 py-3 font-semibold text-danger-600">
                {{ product.quantity }} {{ product.unit }}
              </td>

              <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                {{ product.min_stock_level }}
              </td>

              <td class="whitespace-nowrap px-4 py-3">
                <StatusBadge
                  :status="product.quantity <= 0 ? 'out' : 'low'"
                />
              </td>

              <td class="whitespace-nowrap px-4 py-3">
                <RouterLink
                  :to="`/products/${product.id}`"
                  class="font-medium text-primary-600 transition hover:text-primary-500 focus:outline-none focus:underline"
                >
                  View
                  <span aria-hidden="true">→</span>
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Table Footer -->
      <footer
        class="border-t border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-500"
      >
        Showing {{ products.length }} low-stock
        {{ products.length === 1 ? 'product' : 'products' }}.
      </footer>
    </div>
  </section>
</template>
```