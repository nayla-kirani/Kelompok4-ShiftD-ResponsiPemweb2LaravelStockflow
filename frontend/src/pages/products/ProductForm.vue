```vue
<script setup lang="ts">
import { ref, onMounted, computed, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/client'
import type { Product, Category, Supplier } from '@/types'

type ToastType = 'success' | 'error' | 'warning'
type FormErrors = Record<string, string[]>

const route = useRoute()
const router = useRouter()

const addToast = inject<
  (message: string, type: ToastType) => void
>('addToast')

const isEdit = computed(() => Boolean(route.params.id))
const productId = computed(() => route.params.id)

const loading = ref(true)
const saving = ref(false)
const errors = ref<FormErrors>({})
const pageError = ref('')

const categories = ref<Category[]>([])
const suppliers = ref<Supplier[]>([])

const units = ['pcs', 'kg', 'liters', 'boxes', 'meters']

const form = ref({
  name: '',
  sku: '',
  description: '',
  category_id: '',
  supplier_id: '',
  unit_price: 0,
  cost_price: 0,
  quantity: 0,
  min_stock_level: 0,
  max_stock_level: 0,
  unit: 'pcs',
  location: '',
  barcode: '',
})

const inputClass = `
  mt-1 block w-full rounded-lg border border-gray-300
  bg-white px-3 py-2.5 text-sm text-gray-900 outline-none
  transition placeholder:text-gray-400
  focus:border-primary-500 focus:ring-2 focus:ring-primary-100
  disabled:cursor-not-allowed disabled:bg-gray-100
`

const labelClass = 'block text-sm font-medium text-gray-700'

function notify(message: string, type: ToastType) {
  addToast?.(message, type)
}

function fieldError(field: string) {
  return errors.value[field]?.[0] ?? ''
}

function validateForm(): boolean {
  const validationErrors: FormErrors = {}

  if (!form.value.name.trim()) {
    validationErrors.name = ['Product name is required.']
  }

  if (!form.value.sku.trim()) {
    validationErrors.sku = ['SKU is required.']
  }

  if (!form.value.category_id) {
    validationErrors.category_id = ['Please select a category.']
  }

  if (
    !Number.isFinite(form.value.unit_price) ||
    form.value.unit_price < 0
  ) {
    validationErrors.unit_price = [
      'Unit price must be a valid non-negative number.',
    ]
  }

  if (
    !Number.isFinite(form.value.cost_price) ||
    form.value.cost_price < 0
  ) {
    validationErrors.cost_price = [
      'Cost price must be a valid non-negative number.',
    ]
  }

  if (
    !Number.isInteger(form.value.quantity) ||
    form.value.quantity < 0
  ) {
    validationErrors.quantity = [
      'Quantity must be a non-negative whole number.',
    ]
  }

  if (
    !Number.isInteger(form.value.min_stock_level) ||
    form.value.min_stock_level < 0
  ) {
    validationErrors.min_stock_level = [
      'Minimum stock must be a non-negative whole number.',
    ]
  }

  if (
    !Number.isInteger(form.value.max_stock_level) ||
    form.value.max_stock_level < 0
  ) {
    validationErrors.max_stock_level = [
      'Maximum stock must be a non-negative whole number.',
    ]
  }

  if (
    form.value.max_stock_level > 0 &&
    form.value.max_stock_level < form.value.min_stock_level
  ) {
    validationErrors.max_stock_level = [
      'Maximum stock cannot be lower than minimum stock.',
    ]
  }

  errors.value = validationErrors

  return Object.keys(validationErrors).length === 0
}

async function fetchFormData() {
  loading.value = true
  pageError.value = ''

  try {
    const [categoryResponse, supplierResponse] =
      await Promise.all([
        api.get('/categories', {
          params: { per_page: 100 },
        }),
        api.get('/suppliers', {
          params: { per_page: 100 },
        }),
      ])

    categories.value = categoryResponse.data.data
    suppliers.value = supplierResponse.data.data

    if (isEdit.value) {
      const { data } = await api.get(
        `/products/${productId.value}`,
      )

      const product: Product = data.data ?? data

      form.value = {
        name: product.name ?? '',
        sku: product.sku ?? '',
        description: product.description ?? '',
        category_id:
          product.category_id != null
            ? String(product.category_id)
            : '',
        supplier_id:
          product.supplier_id != null
            ? String(product.supplier_id)
            : '',
        unit_price: Number(product.unit_price ?? 0),
        cost_price: Number(product.cost_price ?? 0),
        quantity: Number(product.quantity ?? 0),
        min_stock_level: Number(
          product.min_stock_level ?? 0,
        ),
        max_stock_level: Number(
          product.max_stock_level ?? 0,
        ),
        unit: product.unit ?? 'pcs',
        location: product.location ?? '',
        barcode: product.barcode ?? '',
      }
    }
  } catch (error) {
    console.error('Failed to load product form:', error)

    pageError.value = isEdit.value
      ? 'Failed to load product details. Please try again.'
      : 'Failed to load categories or suppliers.'
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  if (saving.value) return

  if (!validateForm()) {
    notify('Please correct the errors in the form.', 'warning')
    return
  }

  saving.value = true
  errors.value = {}

  try {
    const payload = {
      name: form.value.name.trim(),
      sku: form.value.sku.trim(),
      description: form.value.description.trim() || null,
      category_id: Number(form.value.category_id),
      supplier_id: form.value.supplier_id
        ? Number(form.value.supplier_id)
        : null,
      unit_price: form.value.unit_price,
      cost_price: form.value.cost_price,
      min_stock_level: form.value.min_stock_level,
      max_stock_level: form.value.max_stock_level,
      unit: form.value.unit,
      location: form.value.location.trim() || null,
      barcode: form.value.barcode.trim() || null,

      // Initial quantity is only sent when creating a product.
      ...(!isEdit.value
        ? { quantity: form.value.quantity }
        : {}),
    }

    if (isEdit.value) {
      await api.put(
        `/products/${productId.value}`,
        payload,
      )

      notify('Product updated successfully.', 'success')
    } else {
      await api.post('/products', payload)

      notify('Product created successfully.', 'success')
    }

    await router.push('/products')
  } catch (error: unknown) {
    console.error('Failed to save product:', error)

    const response = (
      error as {
        response?: {
          status?: number
          data?: {
            message?: string
            errors?: FormErrors
          }
        }
      }
    ).response

    if (response?.status === 422 && response.data?.errors) {
      errors.value = response.data.errors
      notify(
        'Some fields are invalid. Please check the form.',
        'warning',
      )
    } else {
      notify(
        response?.data?.message ??
          'Failed to save product. Please try again.',
        'error',
      )
    }
  } finally {
    saving.value = false
  }
}

onMounted(fetchFormData)
</script>

<template>
  <section class="mx-auto max-w-4xl space-y-6">
    <!-- Page header -->
    <div>
      <button
        type="button"
        class="mb-3 inline-flex items-center gap-2 text-sm
               font-medium text-gray-500 transition
               hover:text-gray-900"
        @click="router.push('/products')"
      >
        <span aria-hidden="true">&larr;</span>
        Back to Products
      </button>

      <h1 class="text-2xl font-bold text-gray-900">
        {{ isEdit ? 'Edit Product' : 'Create Product' }}
      </h1>

      <p class="mt-1 text-sm text-gray-500">
        {{
          isEdit
            ? 'Update product information and stock settings.'
            : 'Add a new product to your inventory.'
        }}
      </p>
    </div>

    <!-- Loading state -->
    <div
      v-if="loading"
      class="flex flex-col items-center justify-center
             rounded-xl bg-white py-20 shadow-sm
             ring-1 ring-gray-200"
    >
      <div
        class="h-8 w-8 animate-spin rounded-full border-4
               border-primary-200 border-t-primary-600"
        role="status"
        aria-label="Loading"
      ></div>

      <p class="mt-3 text-sm text-gray-500">
        Loading product information...
      </p>
    </div>

    <!-- Page error -->
    <div
      v-else-if="pageError"
      class="rounded-xl border border-red-200
             bg-red-50 p-6"
      role="alert"
    >
      <h2 class="font-semibold text-red-800">
        Unable to load the form
      </h2>

      <p class="mt-1 text-sm text-red-700">
        {{ pageError }}
      </p>

      <button
        type="button"
        class="mt-4 rounded-lg bg-red-600 px-4 py-2
               text-sm font-semibold text-white
               hover:bg-red-700"
        @click="fetchFormData"
      >
        Try Again
      </button>
    </div>

    <!-- Product form -->
    <form
      v-else
      class="space-y-6 rounded-xl bg-white p-5
             shadow-sm ring-1 ring-gray-200 sm:p-7"
      @submit.prevent="handleSubmit"
    >
      <!-- General information -->
      <div>
        <h2 class="text-base font-semibold text-gray-900">
          General Information
        </h2>

        <p class="mt-1 text-sm text-gray-500">
          Enter the product name and identification details.
        </p>
      </div>

      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
          <label :class="labelClass" for="product-name">
            Product Name *
          </label>

          <input
            id="product-name"
            v-model="form.name"
            :class="inputClass"
            placeholder="Enter product name"
            autocomplete="off"
            maxlength="255"
            required
            :disabled="saving"
            :aria-invalid="!!fieldError('name')"
          />

          <p
            v-if="fieldError('name')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('name') }}
          </p>
        </div>

        <div>
          <label :class="labelClass" for="product-sku">
            SKU *
          </label>

          <input
            id="product-sku"
            v-model="form.sku"
            :class="inputClass"
            placeholder="e.g. PROD-001"
            autocomplete="off"
            maxlength="100"
            required
            :disabled="saving"
            :aria-invalid="!!fieldError('sku')"
          />

          <p
            v-if="fieldError('sku')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('sku') }}
          </p>
        </div>
      </div>

      <div>
        <label :class="labelClass" for="description">
          Description
        </label>

        <textarea
          id="description"
          v-model="form.description"
          :class="inputClass"
          rows="3"
          placeholder="Describe the product (optional)"
          :disabled="saving"
        ></textarea>

        <p
          v-if="fieldError('description')"
          class="mt-1 text-xs text-red-600"
        >
          {{ fieldError('description') }}
        </p>
      </div>

      <div class="border-t border-gray-100"></div>

      <!-- Classification -->
      <div>
        <h2 class="text-base font-semibold text-gray-900">
          Classification
        </h2>

        <p class="mt-1 text-sm text-gray-500">
          Organize the product by category and supplier.
        </p>
      </div>

      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
          <label :class="labelClass" for="category">
            Category *
          </label>

          <select
            id="category"
            v-model="form.category_id"
            :class="inputClass"
            required
            :disabled="saving"
            :aria-invalid="!!fieldError('category_id')"
          >
            <option value="" disabled>
              Select category
            </option>

            <option
              v-for="category in categories"
              :key="category.id"
              :value="String(category.id)"
            >
              {{ category.name }}
            </option>
          </select>

          <p
            v-if="fieldError('category_id')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('category_id') }}
          </p>
        </div>

        <div>
          <label :class="labelClass" for="supplier">
            Supplier
          </label>

          <select
            id="supplier"
            v-model="form.supplier_id"
            :class="inputClass"
            :disabled="saving"
          >
            <option value="">No supplier</option>

            <option
              v-for="supplier in suppliers"
              :key="supplier.id"
              :value="String(supplier.id)"
            >
              {{ supplier.name }}
            </option>
          </select>

          <p
            v-if="fieldError('supplier_id')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('supplier_id') }}
          </p>
        </div>
      </div>

      <div class="border-t border-gray-100"></div>

      <!-- Pricing -->
      <div>
        <h2 class="text-base font-semibold text-gray-900">
          Pricing and Unit
        </h2>

        <p class="mt-1 text-sm text-gray-500">
          Set the selling price, purchase cost, and unit of measurement.
        </p>
      </div>

      <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div>
          <label :class="labelClass" for="unit-price">
            Unit Price *
          </label>

          <input
            id="unit-price"
            v-model.number="form.unit_price"
            :class="inputClass"
            type="number"
            min="0"
            step="0.01"
            required
            :disabled="saving"
            :aria-invalid="!!fieldError('unit_price')"
          />

          <p
            v-if="fieldError('unit_price')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('unit_price') }}
          </p>
        </div>

        <div>
          <label :class="labelClass" for="cost-price">
            Cost Price *
          </label>

          <input
            id="cost-price"
            v-model.number="form.cost_price"
            :class="inputClass"
            type="number"
            min="0"
            step="0.01"
            required
            :disabled="saving"
            :aria-invalid="!!fieldError('cost_price')"
          />

          <p
            v-if="fieldError('cost_price')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('cost_price') }}
          </p>
        </div>

        <div>
          <label :class="labelClass" for="unit">
            Unit
          </label>

          <select
            id="unit"
            v-model="form.unit"
            :class="inputClass"
            :disabled="saving"
          >
            <option
              v-for="unit in units"
              :key="unit"
              :value="unit"
            >
              {{ unit }}
            </option>
          </select>

          <p
            v-if="fieldError('unit')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('unit') }}
          </p>
        </div>
      </div>

      <div class="border-t border-gray-100"></div>

      <!-- Inventory -->
      <div>
        <h2 class="text-base font-semibold text-gray-900">
          Inventory Settings
        </h2>

        <p class="mt-1 text-sm text-gray-500">
          Configure initial quantity and stock thresholds.
        </p>
      </div>

      <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div v-if="!isEdit">
          <label :class="labelClass" for="quantity">
            Initial Quantity
          </label>

          <input
            id="quantity"
            v-model.number="form.quantity"
            :class="inputClass"
            type="number"
            min="0"
            step="1"
            :disabled="saving"
            :aria-invalid="!!fieldError('quantity')"
          />

          <p
            v-if="fieldError('quantity')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('quantity') }}
          </p>
        </div>

        <div>
          <label :class="labelClass" for="min-stock">
            Minimum Stock
          </label>

          <input
            id="min-stock"
            v-model.number="form.min_stock_level"
            :class="inputClass"
            type="number"
            min="0"
            step="1"
            :disabled="saving"
            :aria-invalid="!!fieldError('min_stock_level')"
          />

          <p
            v-if="fieldError('min_stock_level')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('min_stock_level') }}
          </p>
        </div>

        <div>
          <label :class="labelClass" for="max-stock">
            Maximum Stock
          </label>

          <input
            id="max-stock"
            v-model.number="form.max_stock_level"
            :class="inputClass"
            type="number"
            min="0"
            step="1"
            :disabled="saving"
            :aria-invalid="!!fieldError('max_stock_level')"
          />

          <p
            v-if="fieldError('max_stock_level')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('max_stock_level') }}
          </p>

          <p class="mt-1 text-xs text-gray-400">
            Enter 0 if no maximum is defined.
          </p>
        </div>
      </div>

      <div class="border-t border-gray-100"></div>

      <!-- Storage details -->
      <div>
        <h2 class="text-base font-semibold text-gray-900">
          Storage Details
        </h2>

        <p class="mt-1 text-sm text-gray-500">
          Specify where the product is stored and its barcode.
        </p>
      </div>

      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
          <label :class="labelClass" for="location">
            Storage Location
          </label>

          <input
            id="location"
            v-model="form.location"
            :class="inputClass"
            placeholder="e.g. Warehouse A, Shelf 02"
            :disabled="saving"
          />

          <p
            v-if="fieldError('location')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('location') }}
          </p>
        </div>

        <div>
          <label :class="labelClass" for="barcode">
            Barcode
          </label>

          <input
            id="barcode"
            v-model="form.barcode"
            :class="inputClass"
            placeholder="Enter barcode (optional)"
            :disabled="saving"
          />

          <p
            v-if="fieldError('barcode')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('barcode') }}
          </p>
        </div>
      </div>

      <!-- Actions -->
      <div
        class="flex flex-col-reverse gap-3 border-t
               border-gray-100 pt-5 sm:flex-row
               sm:justify-end"
      >
        <button
          type="button"
          class="rounded-lg border border-gray-300
                 px-5 py-2.5 text-sm font-semibold
                 text-gray-700 transition hover:bg-gray-50
                 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="saving"
          @click="router.push('/products')"
        >
          Cancel
        </button>

        <button
          type="submit"
          class="inline-flex items-center justify-center
                 gap-2 rounded-lg bg-primary-600
                 px-5 py-2.5 text-sm font-semibold
                 text-white transition hover:bg-primary-700
                 focus:outline-none focus:ring-2
                 focus:ring-primary-500 focus:ring-offset-2
                 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="saving"
        >
          <span
            v-if="saving"
            class="h-4 w-4 animate-spin rounded-full
                   border-2 border-white/40 border-t-white"
          ></span>

          {{
            saving
              ? 'Saving...'
              : isEdit
                ? 'Update Product'
                : 'Create Product'
          }}
        </button>
      </div>
    </form>
  </section>
</template>
```