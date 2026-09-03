import { useMutation } from '@tanstack/react-query'
import { uploadFile } from '../services/fileService'

export function useUploadFile() {
  return useMutation({
    mutationFn: ({
      file,
      folderId,
      description,
      tags,
    }: {
      file: File
      folderId: number
      description?: string
      tags?: string
    }) => uploadFile(file, folderId, description, tags),
  })
}
