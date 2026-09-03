import api from './api'

export interface FolderItem {
  id: number
  parent_id: number | null
  location_id: number | null
  department_id: number | null
  owner_user_id: number | null
  name: string
  folder_type: 'location' | 'department' | 'user' | 'custom'
  created_by: number
  created_at: string
  updated_at: string
  children_count: number
  files_count: number
}

export async function getFolderChildren(
  parentId: number | null,
): Promise<FolderItem[]> {
  const response = await api.get<{
    success: boolean
    data: FolderItem[]
  }>('/folders', {
    params: parentId === null ? {} : { parent_id: parentId },
  })

  return response.data.data
}
