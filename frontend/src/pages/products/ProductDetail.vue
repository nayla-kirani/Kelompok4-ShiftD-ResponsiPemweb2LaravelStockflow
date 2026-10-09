```vue
<script setup lang="ts">
import { ref, computed, onMounted, inject } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import api from '@/api/client'
import type { Product, StockMovement } from '@/types'
import Modal from '@/components/ui/Modal.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import dayjs from 'dayjs'
import {
  PencilIcon,
  TrashIcon,
  ArrowsRightLeftIcon,
} from '@heroicons/vue/24/outline'

type StockAdjustmentType = 'in' | 'out' | 'adjustment'
type ToastType = 'success' | 'error' | 'warning'

const route = useRoute()
const router = useRouter()

const addToast = inject<
  (message: string, type: ToastType) => void
>('addToast')

const product = ref<Product | null>(null)
const movements = ref<StockMovement[]>([])
const loading = ref(true)
const pageError = ref('')
const showAdjust = ref(false)
const adjusting = ref(false)
const deleting = ref(false)

const adjustForm = ref<{
  type: StockAdjustmentType
  quantity: number
  reason: string
}>({
  type: 'in',
  quantity: 1,
  reason: '',
})

const productId = computed(() => String(route.params.id))

const stockStatus = computed(() => {
  if (!product.value) return 'ok'
  if (product.value.quantity <= 0) return 'out'
  if (product.value.quantity <= product.value.min_stock_level) {
    return 'low'
  }

  return 'ok'
})

const stockPercent = computed(() => {
  const currentProduct = product.value
  const maxStock = currentProduct?.max_stock_level

  if (!currentProduct || !maxStock || maxStock <= 0) return 0

  return Math.min(
    100,
    Math.max(
      0,
      Math.round((currentProduct.quantity / maxStock) * 100),
    ),
  )
})

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value)
}

function getErrorMessage(error: unknown, fallback: string): string {
  if (
    typeof error === 'object' &&
    error !== null &&
    'response' in error
  ) {
    const response = error.response

    if (
      typeof response === 'object' &&
      response !== null &&
      'data' in response
    ) {
      const data = response.data

      if (
        typeof data === 'object' &&
        data !== null &&
        'message' in data &&
        typeof data.message === 'string'
      ) {
        return data.message
      }
    }
  }

  return fallback
}

async function fetchProductDetails(): Promise<void> {
  loading.value = true
  pageError.value = ''

  try {
    const [productResponse, movementsResponse] = await Promise.all([
      api.get(`/products/${productId.value}`),
      api.get(`/products/${productId.value}/movements`, {
        params: { per_page: 20 },
      }),
    ])

    product.value =
      productResponse.data.data ?? productResponse.data

    const movementData =
      movementsResponse.data.data ?? movementsResponse.data

    movements.value = Array.isArray(movementData)
      ? movementData
      : Array.isArray(movementData.data)
        ? movementData.data
        : []
  } catch (error: unknown) {
    pageError.value = getErrorMessage(
      error,
      'Failed to load product details. Please try again.',
    )
  } finally {
    loading.value = false
  }
}

async function adjustStock(): Promise<void> {
  if (adjustForm.value.quantity <= 0) {
    addToast?.('Quantity must be greater than zero.', 'warning')
    return
  }

  adjusting.value = true

  try {
    await api.post(
      `/products/${productId.value}/adjust-stock`,
      adjustForm.value,
    )

    addToast?.('Stock adjusted successfully.', 'success')
    showAdjust.value = false

    adjustForm.value = {
      type: 'in',
      quantity: 1,
      reason: '',
    }

    await fetchProductDetails()
  } catch (error: unknown) {
    addToast?.(
      getErrorMessage(error, 'Failed to adjust stock.'),
      'error',
    )
  } finally {
    adjusting.value = false
  }
}

async function deleteProduct(): Promise<void> {
  if (deleting.value) return

  const confirmed = window.confirm(
    'Are you sure you want to delete this product?',
  )

  if (!confirmed) return

  deleting.value = true

  try {
    await api.delete(`/products/${productId.value}`)

    addToast?.('Product deleted successfully.', 'success')
    await router.push('/products')
  } catch (error: unknown) {
    addToast?.(
      getErrorMessage(error, 'Failed to delete product.'),
      'error',
    )
  } finally {
    deleting.value = false
  }
}

onMounted(fetchProductDetails)
</script>

<template>
  <div>
    <!-- Loading -->
    <div
      v-if="loading"
      class="flex items-center justify-center py-20"
      role="status"
      aria-label="Loading product details"
    >
      <div
        class="h-8 w-8 animate-spin rounded-full border-4 border-primary-200 border-t-primary-600"
      />
    </div>

    <!-- Error -->
    <div
      v-else-if="pageError"
      class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-200"
    >
      <p class="text-sm text-danger-600">
        {{ pageError }}
      </p>

      <button
        type="button"
        class="mt-4 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700"
        @click="fetchProductDetails"
      >
        Try Again
      </button>
    </div>

    <!-- Product Details -->
    <template v-else-if="product">
      <!-- Header -->
      <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">
            {{ product.name }}
          </h2>

          <p class="text-sm text-gray-500">
            SKU: {{ product.sku }}
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700"
            @click="showAdjust = true"
          >
            <ArrowsRightLeftIcon class="h-4 w-4" />
            Adjust Stock
          </button>

          <RouterLink
            :to="`/products/${product.id}/edit`"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            <PencilIcon class="h-4 w-4" />
            Edit
          </RouterLink>

          <button
            type="button"
            :disabled="deleting"
            class="inline-flex items-center gap-2 rounded-lg border border-danger-500 px-4 py-2 text-sm font-medium text-danger-600 hover:bg-danger-50 disabled:cursor-not-allowed disabled:opacity-50"
            @click="deleteProduct"
          >
            <TrashIcon class="h-4 w-4" />
            {{ deleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
          <!-- Product Information -->
          <section
            class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
          >
            <h3 class="mb-4 text-lg font-semibold text-gray-900">
              Product Information
            </h3>

            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-gray-500">Category</dt>
                <dd class="font-medium text-gray-900">
                  {{ product.category?.name ?? '—' }}
                </dd>
              </div>

              <div>
                <dt class="text-gray-500">Supplier</dt>
                <dd class="font-medium text-gray-900">
                  {{ product.supplier?.name ?? '—' }}
                </dd>
              </div>

              <div>
                <dt class="text-gray-500">Unit Price</dt>
                <dd class="font-medium text-gray-900">
                  {{ formatCurrency(product.unit_price) }}
                </dd>
              </div>

              <div>
                <dt class="text-gray-500">Cost Price</dt>
                <dd class="font-medium text-gray-900">
                  {{ formatCurrency(product.cost_price) }}
                </dd>
              </div>

              <div>
                <dt class="text-gray-500">Unit</dt>
                <dd class="font-medium text-gray-900">
                  {{ product.unit }}
                </dd>
              </div>

              <div>
                <dt class="text-gray-500">Location</dt>
                <dd class="font-medium text-gray-900">
                  {{ product.location ?? '—' }}
                </dd>
              </div>

              <div>
                <dt class="text-gray-500">Barcode</dt>
                <dd class="font-medium text-gray-900">
                  {{ product.barcode ?? '—' }}
                </dd>
              </div>

              <div>
                <dt class="text-gray-500">Status</dt>
                <dd>
                  <StatusBadge
                    :status="product.is_active ? 'active' : 'inactive'"
                  />
                </dd>
              </div>
            </dl>

            <p
              v-if="product.description"
              class="mt-4 text-sm leading-relaxed text-gray-600"
            >
              {{ product.description }}
            </p>
          </section>

          <!-- Stock Movements -->
          <section
            class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
          >
            <div class="mb-4 flex items-center justify-between gap-3">
              <h3 class="text-lg font-semibold text-gray-900">
                Stock Movements
              </h3>

              <span class="text-xs text-gray-500">
                Latest {{ movements.length }} records
              </span>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead>
                  <tr
                    class="border-b border-gray-200 text-left text-xs font-medium uppercase text-gray-500"
                  >
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Quantity</th>
                    <th class="px-4 py-3">Reason</th>
                    <th class="px-4 py-3">User</th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                  <tr v-if="!movements.length">
                    <td
                      colspan="5"
                      class="px-4 py-6 text-center text-gray-400"
                    >
                      No stock movements yet.
                    </td>
                  </tr>

                  <tr
                    v-for="movement in movements"
                    :key="movement.id"
                    class="hover:bg-gray-50"
                  >
                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                      {{ dayjs(movement.created_at).format('MMM D, YYYY HH:mm') }}
                    </td>

                    <td class="px-4 py-3">
                      <StatusBadge :status="movement.type" />
                    </td>

                    <td
                      class="px-4 py-3 font-medium"
                      :class="
                        movement.type === 'out'
                          ? 'text-danger-600'
                          : 'text-success-600'
                      "
                    >
                      {{ movement.type === 'out' ? '-' : '+' }}{{ movement.quantity }}
                    </td>

                    <td class="px-4 py-3 text-gray-600">
                      {{ movement.reason ?? '—' }}
                    </td>

                    <td class="px-4 py-3 text-gray-600">
                      {{ movement.user?.name ?? '—' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <!-- Stock Level -->
        <aside>
          <section
            class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
          >
            <h3 class="mb-4 text-lg font-semibold text-gray-900">
              Stock Level
            </h3>

            <div class="mb-2 flex items-end justify-between gap-3">
              <span class="text-3xl font-bold text-gray-900">
                {{ product.quantity }}
              </span>

              <span class="text-sm text-gray-500">
                / {{ product.max_stock_level }} {{ product.unit }}
              </span>
            </div>

            <div
              class="mb-2 h-3 overflow-hidden rounded-full bg-gray-200"
              role="progressbar"
              :aria-valuenow="stockPercent"
              aria-valuemin="0"
              aria-valuemax="100"
              :aria-label="`Stock level: ${stockPercent}%`"
            >
              <div
                class="h-full rounded-full transition-all"
                :class="
                  stockStatus === 'ok'
                    ? 'bg-success-500'
                    : 'bg-danger-500'
                "
                :style="{ width: `${stockPercent}%` }"
              />
            </div>

            <div class="flex justify-between text-xs text-gray-500">
              <span>Min: {{ product.min_stock_level }}</span>
              <span>Max: {{ product.max_stock_level }}</span>
            </div>

            <div class="mt-3">
              <StatusBadge :status="stockStatus" />
            </div>
          </section>
        </aside>
      </div>
    </template>

    <!-- Product Not Found -->
    <div
      v-else
      class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-200"
    >
      <p class="text-gray-500">
        Product not found.
      </p>

      <RouterLink
        to="/products"
        class="mt-4 inline-block text-sm font-medium text-primary-600 hover:text-primary-700"
      >
        Back to Products
      </RouterLink>
    </div>

    <!-- Adjust Stock Modal -->
    <Modal
      :show="showAdjust"
      title="Adjust Stock"
      @close="showAdjust = false"
    >
      <form class="space-y-4" @submit.prevent="adjustStock">
        <div>
          <label
            for="adjustment-type"
            class="block text-sm font-medium text-gray-700"
          >
            Type
          </label>

          <select
            id="adjustment-type"
            v-model="adjustForm.type"
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
          >
            <option value="in">Stock In</option>
            <option value="out">Stock Out</option>
            <option value="adjustment">Adjustment</option>
          </select>
        </div>

        <div>
          <label
            for="adjustment-quantity"
            class="block text-sm font-medium text-gray-700"
          >
            Quantity
          </label>

          <input
            id="adjustment-quantity"
            v-model.number="adjustForm.quantity"
            type="number"
            min="1"
            step="1"
            required
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
          />
        </div>

        <div>
          <label
            for="adjustment-reason"
            class="block text-sm font-medium text-gray-700"
          >
            Reason
          </label>

          <textarea
            id="adjustment-reason"
            v-model="adjustForm.reason"
            rows="2"
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
          />
        </div>

        <div class="flex justify-end gap-3">
          <button
            type="button"
            class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
            @click="showAdjust = false"
          >
            Cancel
          </button>

          <button
            type="submit"
            :disabled="adjusting"
            class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {{ adjusting ? 'Saving...' : 'Adjust Stock' }}
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>
```