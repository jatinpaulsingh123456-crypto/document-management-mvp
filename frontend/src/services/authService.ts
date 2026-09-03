import api from './api'

export interface CurrentUser {
  id: number
  username: string
  email: string
  name: string
  status: number
  role: {
    id: number
    code: string
    name: string
  } | null
  location: {
    id: number
    name: string
    code: string
    country: string
  } | null
  department: {
    id: number
    name: string
    code: string
  } | null
  manager: {
    id: number
    username: string
    name: string
  } | null
}

export async function getCurrentUser(): Promise<CurrentUser> {
  const response = await api.get<{
    success: boolean
    data: CurrentUser
  }>('/auth/me')

  return response.data.data
}
