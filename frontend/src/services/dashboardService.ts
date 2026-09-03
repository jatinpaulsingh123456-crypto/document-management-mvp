import api from './api'

export interface DashboardFolder {
  id: number
  name: string
  filesCount: number
}

export interface DashboardRecentFile {
  id: number
  originalName: string
  mimeType: string
  sizeBytes: number
  uploadedAt: string
}

export interface DashboardData {
  user: {
    id: number
    name: string
  }
  stats: {
    totalFiles: number
    totalFolders: number
    storageUsedBytes: number
    storageQuotaBytes: number
    sharedWithMe: number
  }
  folders: DashboardFolder[]
  recentFiles: DashboardRecentFile[]
}

export async function getDashboard(): Promise<DashboardData> {
  const response = await api.get<{
    success: boolean
    data: DashboardData
  }>('/dashboard')

  return response.data.data
}
