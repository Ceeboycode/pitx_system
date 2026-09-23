<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'

import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

import {
  fetchRegions,
  fetchProvincesByRegion,
  fetchCitiesMunByProvince,
  fetchCitiesMunByRegion,
  fetchBarangaysByCityMun,
  type PSGCItem,
} from '@/lib/psgc'

import { Separator } from '@/components/ui/separator'

type Codes = {
  regionCode: string
  provinceCode: string
  cityMunCode: string
  barangayCode: string
}

const props = defineProps<{
  address: string
  codes?: Partial<Codes>
  label?: string
  streetLabel?: string
}>()

const emit = defineEmits<{
  (e: 'update:address', v: string): void
  (e: 'update:codes', v: Codes): void
}>()

const label = computed(() => props.label ?? 'Company Address')
const streetLabel = computed(() => props.streetLabel ?? 'Street / Building / Unit')

const loading = ref({
  regions: false,
  provinces: false,
  cities: false,
  barangays: false,
})

const error = ref<string | null>(null)

const regions = ref<PSGCItem[]>([])
const provinces = ref<PSGCItem[]>([])
const cities = ref<PSGCItem[]>([])
const barangays = ref<PSGCItem[]>([])

const regionCode = ref(props.codes?.regionCode ?? '')
const provinceCode = ref(props.codes?.provinceCode ?? '')
const cityMunCode = ref(props.codes?.cityMunCode ?? '')
const barangayCode = ref(props.codes?.barangayCode ?? '')

const street = ref('')

const hasProvinces = computed(() => provinces.value.length > 0)
const isNoProvinceRegion = computed(
  () => Boolean(regionCode.value) && !loading.value.provinces && provinces.value.length === 0,
)

// Selected labels (for composing final address string)
const selectedRegion = computed(
  () => regions.value.find((r) => r.code === regionCode.value)?.name ?? '',
)
const selectedProvince = computed(() => {
  if (isNoProvinceRegion.value) return ''
  return provinces.value.find((p) => p.code === provinceCode.value)?.name ?? ''
})
const selectedCity = computed(
  () => cities.value.find((c) => c.code === cityMunCode.value)?.name ?? '',
)
const selectedBarangay = computed(
  () => barangays.value.find((b) => b.code === barangayCode.value)?.name ?? '',
)

const composedAddress = computed(() => {
  const parts = [
    street.value?.trim(),
    selectedBarangay.value?.trim(),
    selectedCity.value?.trim(),
    selectedProvince.value?.trim(),
    selectedRegion.value?.trim(),
  ].filter(Boolean)
  return parts.join(', ')
})

watch(composedAddress, (val) => {
  emit('update:address', val)
})

watch([regionCode, provinceCode, cityMunCode, barangayCode], () => {
  emit('update:codes', {
    regionCode: regionCode.value,
    provinceCode: provinceCode.value,
    cityMunCode: cityMunCode.value,
    barangayCode: barangayCode.value,
  })
})

async function loadRegions() {
  try {
    error.value = null
    loading.value.regions = true
    regions.value = await fetchRegions()
  } catch (e: any) {
    error.value = e?.message ?? 'Failed to load regions'
  } finally {
    loading.value.regions = false
  }
}

async function loadProvinces() {
  provinces.value = []
  cities.value = []
  barangays.value = []
  provinceCode.value = ''
  cityMunCode.value = ''
  barangayCode.value = ''

  if (!regionCode.value) return

  try {
    error.value = null
    loading.value.provinces = true
    const fetchedProvinces = await fetchProvincesByRegion(regionCode.value)
    provinces.value = fetchedProvinces

    // If region has no provinces (e.g. NCR), immediately load its cities/municipalities
    if (fetchedProvinces.length === 0) {
      await loadCities()
    }
  } catch (e: any) {
    error.value = e?.message ?? 'Failed to load provinces'
  } finally {
    loading.value.provinces = false
  }
}

async function loadCities() {
  cities.value = []
  barangays.value = []
  cityMunCode.value = ''
  barangayCode.value = ''

  if (!regionCode.value) return

  // If region has provinces, a province must be selected first
  if (hasProvinces.value && !provinceCode.value) return

  try {
    error.value = null
    loading.value.cities = true

    if (isNoProvinceRegion.value || provinces.value.length === 0) {
      cities.value = await fetchCitiesMunByRegion(regionCode.value)
    } else if (provinceCode.value) {
      cities.value = await fetchCitiesMunByProvince(provinceCode.value)
    }
  } catch (e: any) {
    error.value = e?.message ?? 'Failed to load cities/municipalities'
  } finally {
    loading.value.cities = false
  }
}

async function loadBarangays() {
  barangays.value = []
  barangayCode.value = ''

  if (!cityMunCode.value) return

  try {
    error.value = null
    loading.value.barangays = true
    barangays.value = await fetchBarangaysByCityMun(cityMunCode.value)
  } catch (e: any) {
    error.value = e?.message ?? 'Failed to load barangays'
  } finally {
    loading.value.barangays = false
  }
}

watch(regionCode, async () => {
  await loadProvinces()
})

watch(provinceCode, async (newVal) => {
  if (hasProvinces.value && newVal) {
    await loadCities()
  }
})

watch(cityMunCode, async (newVal) => {
  if (newVal) {
    await loadBarangays()
  }
})

onMounted(async () => {
  await loadRegions()

  if (regionCode.value) {
    try {
      loading.value.provinces = true
      provinces.value = await fetchProvincesByRegion(regionCode.value)
    } catch (e: any) {
      error.value = e?.message ?? 'Failed to load provinces'
    } finally {
      loading.value.provinces = false
    }

    if (provinces.value.length === 0 || isNoProvinceRegion.value) {
      await loadCities()
    } else if (provinceCode.value) {
      await loadCities()
    }

    if (cityMunCode.value) {
      await loadBarangays()
    }
  }
})
</script>

<template>
  <div class="space-y-2">
    <div class="flex items-center gap-3 py-2">
      <p class="font-semibold text-custom-accent-3 text-base">{{ label }}</p>
      <Separator class="flex-1" />
    </div>

    <div class="grid gap-2 sm:grid-cols-2">
      <div class="space-y-1">
        <Label>Region</Label>
        <Select v-model="regionCode">
          <SelectTrigger class="w-full">
            <SelectValue :placeholder="loading.regions ? 'Loading...' : 'Select region...'" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="r in regions" :key="r.code" :value="r.code">
              {{ r.name === 'NCR' ? 'NCR (National Capital Region)' : (r.regionName && r.regionName !== r.name ? `${r.name} (${r.regionName})` : r.name) }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="space-y-1">
        <Label>Province</Label>
        <Select v-model="provinceCode" :disabled="!regionCode || isNoProvinceRegion || loading.provinces">
          <SelectTrigger class="w-full">
            <SelectValue
              :placeholder="
                !regionCode
                  ? 'Select region first...'
                  : loading.provinces
                    ? 'Loading...'
                    : isNoProvinceRegion
                      ? 'Not Applicable (NCR)'
                      : 'Select province...'
              "
            />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="p in provinces" :key="p.code" :value="p.code">
              {{ p.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="space-y-1">
        <Label>City / Municipality</Label>
        <Select
          v-model="cityMunCode"
          :disabled="!regionCode || loading.provinces || loading.cities || (hasProvinces && !provinceCode)"
        >
          <SelectTrigger class="w-full">
            <SelectValue
              :placeholder="
                !regionCode
                  ? 'Select region first...'
                  : loading.provinces || loading.cities
                    ? 'Loading...'
                    : hasProvinces && !provinceCode
                      ? 'Select province first...'
                      : 'Select city/municipality...'
              "
            />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="c in cities" :key="c.code" :value="c.code">
              {{ c.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="space-y-1">
        <Label>Barangay</Label>
        <Select v-model="barangayCode" :disabled="!cityMunCode || loading.barangays">
          <SelectTrigger class="w-full">
            <SelectValue
              :placeholder="
                !cityMunCode
                  ? 'Select city first...'
                  : loading.barangays
                    ? 'Loading...'
                    : 'Select barangay...'
              "
            />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="b in barangays" :key="b.code" :value="b.code">
              {{ b.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>

    <div class="space-y-1">
      <Label>{{ streetLabel }}</Label>
      <Input v-model="street" placeholder="House/Unit No., Street, Building..." />
    </div>

    <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
  </div>
</template>
