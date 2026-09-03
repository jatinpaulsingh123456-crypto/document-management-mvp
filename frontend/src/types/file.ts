export interface FileItem {
  id: number
  folder_id: number
  uploader_id: number
  location_id: number | null
  department_id: number | null
  name: string
  original_name: string
  storage_key: string
  mime_type: string
  extension: string | null
  size_bytes: number
  description: string | null
  tags: string[] | null
  checksum: string
  status: string
  uploaded_at: string
  updated_at: string
}
