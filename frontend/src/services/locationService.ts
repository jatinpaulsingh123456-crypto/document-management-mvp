import api from './api'

export interface LocationItem {
  id: number
  name: string
  code: string
  country: string
  parent_id: number | null
  status: number
  created_at: string
  updated_at: string
}

export interface DepartmentItem {
  id: number
  location_id: number
  parent_id: number | null
  name: string
  code: string
  status: number
}

export async function getLocations(): Promise<LocationItem[]> {
  const response = await api.get<{
    success: boolean
    data: LocationItem[]
  }>('/locations')

  return response.data.data
}

export async function getDepartmentsByLocation(
  locationId: number,
): Promise<DepartmentItem[]> {
  const response = await api.get<{
    success: boolean
    data: DepartmentItem[]
  }>('/departments', {
    params: {
      location_id: locationId,
    },
  })

  return response.data.data
}

export async function createLocation(data: {
  name: string
  code: string
  country: string
}) {
  const response = await api.post('/locations', data)
  return response.data
}

export async function updateLocation(
  id: number,
  data: {
    name?: string
    code?: string
    country?: string
    status?: number
  },
) {
  const response = await api.put(`/locations/${id}`, data)
  return response.data
}

export async function deleteLocation(id: number) {
  const response = await api.delete(`/locations/${id}`)
  return response.data
}
