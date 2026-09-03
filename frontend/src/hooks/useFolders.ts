import { useQuery } from '@tanstack/react-query'
import {
  getFolderChildren,
  type FolderItem,
} from '../services/folderService'

async function getAllFolders(): Promise<FolderItem[]> {
  const result: FolderItem[] = []

  async function loadChildren(parentId: number | null) {
    const folders = await getFolderChildren(parentId)

    result.push(...folders)

    for (const folder of folders) {
      if (folder.children_count > 0) {
        await loadChildren(folder.id)
      }
    }
  }

  await loadChildren(null)

  return result
}

export function useFolders() {
  return useQuery({
    queryKey: ['folders'],
    queryFn: getAllFolders,
  })
}
