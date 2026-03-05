// resources/js/lib/psgc.ts
export type PSGCItem = {
  code: string
  name: string
}

const BASE = 'https://psgc.gitlab.io/api'

async function getJson<T>(url: string): Promise<T> {
  const res = await fetch(url, { headers: { Accept: 'application/json' } })
  if (!res.ok) throw new Error(`PSGC API error ${res.status} for ${url}`)
  return res.json()
}

// Regions
export function fetchRegions(): Promise<PSGCItem[]> {
  return getJson(`${BASE}/regions/`)
}

// Provinces in a region
export function fetchProvincesByRegion(regionCode: string): Promise<PSGCItem[]> {
  return getJson(`${BASE}/regions/${regionCode}/provinces/`)
}

// Cities/Municipalities in a province
export function fetchCitiesMunByProvince(provinceCode: string): Promise<PSGCItem[]> {
  return getJson(`${BASE}/provinces/${provinceCode}/cities-municipalities/`)
}

// Barangays in a city/municipality
export function fetchBarangaysByCityMun(cityMunCode: string): Promise<PSGCItem[]> {
  return getJson(`${BASE}/cities-municipalities/${cityMunCode}/barangays/`)
}
