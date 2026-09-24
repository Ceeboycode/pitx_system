// resources/js/lib/psgc.ts
export type PSGCItem = {
  code: string
  name: string
  regionName?: string
}

const BASE = 'https://psgc.gitlab.io/api'
const cache = new Map<string, any>()

async function getJson<T>(url: string): Promise<T> {
  if (cache.has(url)) {
    return cache.get(url) as T
  }
  const res = await fetch(url, { headers: { Accept: 'application/json' } })
  if (!res.ok) throw new Error(`PSGC API error ${res.status} for ${url}`)
  const data = (await res.json()) as T
  cache.set(url, data)
  return data
}

// Regions
export async function fetchRegions(): Promise<PSGCItem[]> {
  const data = await getJson<PSGCItem[]>(`${BASE}/regions/`)
  return data
}

// Provinces in a region
export async function fetchProvincesByRegion(regionCode: string): Promise<PSGCItem[]> {
  const data = await getJson<PSGCItem[]>(`${BASE}/regions/${regionCode}/provinces/`)
  return [...data].sort((a, b) => a.name.localeCompare(b.name))
}

// Cities/Municipalities in a province
export async function fetchCitiesMunByProvince(provinceCode: string): Promise<PSGCItem[]> {
  const data = await getJson<PSGCItem[]>(`${BASE}/provinces/${provinceCode}/cities-municipalities/`)
  return [...data].sort((a, b) => a.name.localeCompare(b.name))
}

// Cities/Municipalities in a region (e.g. NCR / regions without provinces)
export async function fetchCitiesMunByRegion(regionCode: string): Promise<PSGCItem[]> {
  const data = await getJson<PSGCItem[]>(`${BASE}/regions/${regionCode}/cities-municipalities/`)
  return [...data].sort((a, b) => a.name.localeCompare(b.name))
}

// Barangays in a city/municipality
export async function fetchBarangaysByCityMun(cityMunCode: string): Promise<PSGCItem[]> {
  const data = await getJson<PSGCItem[]>(`${BASE}/cities-municipalities/${cityMunCode}/barangays/`)
  return [...data].sort((a, b) => a.name.localeCompare(b.name))
}
