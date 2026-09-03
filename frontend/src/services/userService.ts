import api from './api'

export interface UserItem {
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

export interface RoleItem {
  id: number
  code: string
  name: string
}

export async function getUsers(): Promise<UserItem[]> {
  const response = await api.get<{
    success: boolean
    data: UserItem[]
  }>('/users')

  return response.data.data
}

export async function getShareRecipients(
  fileId: number,
): Promise<UserItem[]> {
  const response = await api.get<{
    success: boolean
    data: UserItem[]
  }>(`/users/share-recipients/${fileId}`)

  return response.data.data
}

export async function getRoles(): Promise<RoleItem[]> {
  const response = await api.get<{
    success: boolean
    data: RoleItem[]
  }>('/users/roles')

  return response.data.data
}

export async function createUser(data: {
  username: string
  email: string
  name: string
  password: string
  role_id: number
  location_id?: number | null
  department_id?: number | null
  manager_id?: number | null
}) {
  const response = await api.post('/users', data)
  return response.data
}
