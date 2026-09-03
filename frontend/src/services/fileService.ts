import api from './api'
import type { FileItem } from '../types/file'

export async function getFiles(
  folderId: number | null = null,
): Promise<FileItem[]> {
  const response = await api.get<{
    success: boolean
    data: FileItem[]
  }>('/files', {
    params: folderId === null ? {} : { folder_id: folderId },
  })

  return response.data.data
}

export async function getFile(id: number): Promise<FileItem> {
  const response = await api.get<{
    success: boolean
    data: FileItem
  }>(`/files/${id}`)

  return response.data.data
}

export async function getFileShares(id: number) {
  const response = await api.get(`/files/${id}/shares`)
  return response.data.data
}

export async function updateFile(
  id: number,
  data: {
    description?: string
    name?: string
    original_name?: string
    tags?: string[]
  },
) {
  const response = await api.put(`/files/${id}`, data)
  return response.data
}

export async function downloadFile(id: number) {
  return api.get(`/files/${id}/download`, {
    responseType: 'blob',
  })
}

export async function shareFile(
  id: number,
  userId: number,
  permission: 'read' | 'write',
) {
  const response = await api.post(`/files/${id}/share`, {
    user_id: userId,
    permission,
  })

  return response.data
}

export async function deleteFile(id: number) {
  const response = await api.delete(`/files/${id}`)
  return response.data
}

export async function moveFile(id: number, folderId: number) {
  const response = await api.put(`/files/${id}/move`, {
    folder_id: folderId,
  })

  return response.data
}

export async function uploadFile(
  file: File,
  folderId: number,
  description?: string,
  tags?: string,
) {
  const formData = new FormData()

  formData.append('file', file)
  formData.append('folder_id', String(folderId))

  if (description?.trim()) {
    formData.append('description', description.trim())
  }

  if (tags?.trim()) {
    formData.append('tags', tags.trim())
  }

  const response = await api.post<{
    success: boolean
    message: string
    data: FileItem
  }>('/files', formData)

  return response.data
}

export interface DuplicateFileMatch {
  file_id: number
  checksum: string
  original_name: string
  folder_id: number | null
  uploader_id: number | null
}

export async function checkDuplicateChecksums(
  checksums: string[],
): Promise<DuplicateFileMatch[]> {
  const response = await api.post<{
    success: boolean
    data: {
      duplicates: DuplicateFileMatch[]
    }
  }>('/files/check-duplicates', {
    checksums,
  })

  return response.data.data.duplicates
}

export interface SharedFileItem extends FileItem {
  share: {
    id?: number
    permission: 'read' | 'write' | 'manage'
    expires_at: string | null
    shared_at: string
    shared_by: {
      id: number
      name: string
      email: string
    }
    shared_with?: {
      id: number
      name: string
      email: string
    }
  }
}

export interface ShareHistoryItem {
  id: number
  action: 'share' | 'unshare'
  status: 'shared' | 'revoked'
  created_at: string
  file: {
    id: number | null
    original_name: string | null
    mime_type: string | null
    size_bytes: number | null
    location_id: number | null
    department_id: number | null
  }
  shared_by: {
    id: number | null
  }
  shared_with: {
    id: number | null
    name: string | null
    email: string | null
  }
  permission: 'read' | 'write' | 'manage' | null
  expires_at: string | null
  performed_by_user_id: number | null
}

export async function getSharedFiles(): Promise<SharedFileItem[]> {
  const response = await api.get<{
    success: boolean
    data: SharedFileItem[]
  }>('/shared-files')

  return response.data.data
}

export async function getMyShares(): Promise<SharedFileItem[]> {
  const response = await api.get<{
    success: boolean
    data: SharedFileItem[]
  }>('/my-shares')

  return response.data.data
}

export async function getSharedHistory(): Promise<ShareHistoryItem[]> {
  const response = await api.get<{
    success: boolean
    data: ShareHistoryItem[]
  }>('/shared-history')

  return response.data.data
}
