import { useQuery } from '@tanstack/react-query'
import { getFiles } from '../services/fileService'

export function useFiles(folderId: number | null = null) {
  return useQuery({
    queryKey: ['files', folderId],
    queryFn: () => getFiles(folderId),
  })
}
