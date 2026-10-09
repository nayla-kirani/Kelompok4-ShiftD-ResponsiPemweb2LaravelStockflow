```vue
<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api, { stripEmpty } from '@/api/client'
import type {
  Product,
  Category,
  Supplier,
  PaginatedResponse,
} from '@/types'

import Pagination from '@/components/ui/Pagination.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import {
  MagnifyingGlassIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline'

const router = useRouter()

// Data
const products = ref<Product[]>([])
const categories = ref<Category[]>([])
const suppliers = ref<Supplier[]>([])

// Pagination metadata
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
})

// UI state
const loading = ref(false)
const errorMessage = ref('')

// Filters
const search = ref('')
const categoryFilter = ref('')
const supplierFilter = ref('')
const stockFilter = ref('')
const page = ref(1)

const PER_PAGE = 15

// Format currency
const fmt = (value: number) =>
  new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value)

// Stock status
function stockIndicator(product: Product) {
  if (product.quantity <= 0) return 'out'
  if (product.quantity <= product.min_stock_level) return 'low'

  return 'ok'
}

// Fetch products
let latestRequestId = 0

async function fetchProducts() {
  const requestId = ++latestRequestId

  loading.value = true
  errorMessage.value = ''

  try {
    const { data } = await api.get<PaginatedResponse<Product>>(
      '/products',
      {
        params: stripEmpty({
          search: search.value.trim(),
          category_id: categoryFilter.value,
          supplier_id: supplierFilter.value,
          stock_status: stockFilter.value,
          page: page.value,
          per_page: PER_PAGE,
        }),
      },
    )

    // Ignore an outdated response
    if (requestId !== latestRequestId) return

    products.value = data.data

    meta.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total,
    }
  } catch (error) {
    if (requestId !== latestRequestId) return

    console.error('Failed to fetch products:', error)

    products.value = []
    errorMessage.value =
      'Failed to load products. Please try again.'
  } finally {
    if (requestId === latestRequestId) {
      loading.value = false
    }
  }
}

// Fetch categories and suppliers
async function fetchFilters() {
  try {
    const [categoryResponse, supplierResponse] =
      await Promise.all([
        api.get<PaginatedResponse<Category>>('/categories', {
          params: { per_page: 100 },
        }),
        api.get<PaginatedResponse<Supplier>>('/suppliers', {
          params: { per_page: 100 },
        }),
      ])

    categories.value = categoryResponse.data.data
    suppliers.value = supplierResponse.data.data
  } catch (error) {
    console.error('Failed to fetch filters:', error)
    errorMessage.value =
      'Failed to load categories or suppliers.'
  }
}

// Initial loading
onMounted(async () => {
  await Promise.all([
    fetchFilters(),
    fetchProducts(),
  ])
})

// Reset pagination when filters change
watch(
  [search, categoryFilter, supplierFilter, stockFilter],
  () => {
    if (page.value !== 1) {
      page.value = 1
      return
    }

    fetchProducts()
  },
)

// Change page
function changePage(newPage: number) {
  if (
    newPage < 1 ||
    newPage > meta.value.last_page ||
    newPage === page.value
  ) {
    return
  }

  page.value = newPage
  fetchProducts()
}
</script>

<template>
  <section class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
    >
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Products
        </h1>

        <p class="mt-1 text-sm text-gray-500">
          Manage your inventory products and stock levels.
        </p>
      </div>

      <RouterLink
        to="/products/create"
        class="inline-flex items-center justify-center gap-2 rounded-lg
               bg-primary-600 px-4 py-2.5 text-sm font-semibold
               text-white transition hover:bg-primary-700
               focus:outline-none focus:ring-2 focus:ring-primary-500
               focus:ring-offset-2"
      >
        <PlusIcon class="h-5 w-5" />
        Add Product
      </RouterLink>
    </div>

    <!-- Filters -->
    <div
      class="grid grid-cols-1 gap-3 rounded-xl bg-white p-4
             shadow-sm ring-1 ring-gray-200
             sm:grid-cols-2 xl:grid-cols-4"
    >
      <!-- Search -->
      <div class="relative">
        <MagnifyingGlassIcon
          class="pointer-events-none absolute left-3 top-1/2
                 h-4 w-4 -translate-y-1/2 text-gray-400"
        />

        <input
          v-model="search"
          type="search"
          placeholder="Search by name or SKU..."
          aria-label="Search products by name or SKU"
          class="w-full rounded-lg border border-gray-300
                 py-2.5 pl-9 pr-3 text-sm outline-none
                 transition focus:border-primary-500
                 focus:ring-2 focus:ring-primary-100"
        />
      </div>

      <!-- Category -->
      <select
        v-model="categoryFilter"
        aria-label="Filter by category"
        class="w-full rounded-lg border border-gray-300
               px-3 py-2.5 text-sm outline-none
               focus:border-primary-500 focus:ring-2
               focus:ring-primary-100"
      >
        <option value="">All Categories</option>

        <option
          v-for="category in categories"
          :key="category.id"
          :value="category.id"
        >
          {{ category.name }}
        </option>
      </select>

      <!-- Supplier -->
      <select
        v-model="supplierFilter"
        aria-label="Filter by supplier"
        class="w-full rounded-lg border border-gray-300
               px-3 py-2.5 text-sm outline-none
               focus:border-primary-500 focus:ring-2
               focus:ring-primary-100"
      >
        <option value="">All Suppliers</option>

        <option
          v-for="supplier in suppliers"
          :key="supplier.id"
          :value="supplier.id"
        >
          {{ supplier.name }}
        </option>
      </select>

      <!-- Stock -->
      <select
        v-model="stockFilter"
        aria-label="Filter by stock status"
        class="w-full rounded-lg border border-gray-300
               px-3 py-2.5 text-sm outline-none
               focus:border-primary-500 focus:ring-2
               focus:ring-primary-100"
      >
        <option value="">All Stock</option>
        <option value="in">In Stock</option>
        <option value="low">Low Stock</option>
        <option value="out">Out of Stock</option>
      </select>
    </div>

    <!-- Error message -->
    <div
      v-if="errorMessage"
      role="alert"
      class="flex flex-wrap items-center justify-between gap-3
             rounded-lg border border-red-200 bg-red-50
             px-4 py-3 text-sm text-red-700"
    >
      <span>{{ errorMessage }}</span>

      <button
        type="button"
        class="font-semibold underline"
        @click="fetchProducts"
      >
        Retry
      </button>
    </div>

    <!-- Products table -->
    <div
      class="overflow-hidden rounded-xl bg-white
             shadow-sm ring-1 ring-gray-200"
    >
      <div
        class="flex flex-wrap items-center justify-between gap-2
               border-b border-gray-200 px-4 py-4"
      >
        <h2 class="font-semibold text-gray-900">
          Product Inventory
        </h2>

        <span class="text-sm text-gray-500">
          {{ meta.total }} products
        </span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-[850px] w-full text-left text-sm">
          <thead class="bg-gray-50">
            <tr
              class="border-b border-gray-200 text-xs
                     font-semibold uppercase tracking-wide
                     text-gray-500"
            >
              <th class="px-4 py-3">SKU</th>
              <th class="px-4 py-3">Product Name</th>
              <th class="px-4 py-3">Category</th>
              <th class="px-4 py-3">Quantity</th>
              <th class="px-4 py-3">Unit Price</th>
              <th class="px-4 py-3">Cost Price</th>
              <th class="px-4 py-3">Status</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100">
            <!-- Loading -->
            <tr v-if="loading">
              <td
                colspan="7"
                class="px-4 py-12 text-center text-gray-500"
              >
                <div
                  class="flex items-center justify-center gap-2"
                >
                  <span
                    class="h-4 w-4 animate-spin rounded-full
                           border-2 border-gray-300
                           border-t-primary-600"
                  ></span>

                  Loading products...
                </div>
              </td>
            </tr>

            <!-- Error state -->
            <tr v-else-if="errorMessage">
              <td
                colspan="7"
                class="px-4 py-12 text-center text-gray-500"
              >
                Unable to display products.
              </td>
            </tr>

            <!-- Empty state -->
            <tr v-else-if="products.length === 0">
              <td
                colspan="7"
                class="px-4 py-12 text-center"
              >
                <p class="font-medium text-gray-700">
                  No products found
                </p>

                <p class="mt-1 text-xs text-gray-500">
                  Try changing your search or filters.
                </p>
              </td>
            </tr>

            <!-- Product rows -->
            <tr
              v-for="product in products"
              v-else
              :key="product.id"
              tabindex="0"
              class="cursor-pointer transition-colors
                     hover:bg-gray-50
                     focus-visible:outline-none
                     focus-visible:ring-2
                     focus-visible:ring-inset
                     focus-visible:ring-primary-500"
              @click="router.push(`/products/${product.id}`)"
              @keydown.enter="router.push(`/products/${product.id}`)"
            >
              <td
                class="whitespace-nowrap px-4 py-3
                       font-mono text-xs text-gray-600"
              >
                {{ product.sku }}
              </td>

              <td class="px-4 py-3 font-medium text-gray-900">
                {{ product.name }}
              </td>

              <td class="px-4 py-3 text-gray-600">
                {{ product.category?.name ?? '—' }}
              </td>

              <td class="whitespace-nowrap px-4 py-3">
                <span
                  :class="{
                    'font-semibold text-red-600':
                      stockIndicator(product) === 'out' ||
                      stockIndicator(product) === 'low',
                    'text-emerald-600':
                      stockIndicator(product) === 'ok',
                  }"
                >
                  {{ product.quantity }}
                </span>

                <span class="ml-1 text-xs text-gray-400">
                  {{ product.unit }}
                </span>
              </td>

              <td class="whitespace-nowrap px-4 py-3 text-gray-900">
                {{ fmt(product.unit_price) }}
              </td>

              <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                {{ fmt(product.cost_price) }}
              </td>

              <td class="px-4 py-3">
                <StatusBadge :status="stockIndicator(product)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="border-t border-gray-200">
        <Pagination
          :current-page="meta.current_page"
          :last-page="meta.last_page"
          :total="meta.total"
          :per-page="meta.per_page"
          @page-change="changePage"
        />
      </div>
    </div>
  </section>
</template>
```