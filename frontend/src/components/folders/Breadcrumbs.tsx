import { ChevronRight, Folder } from 'lucide-react'
import type { FolderItem } from '../../services/folderService'

interface BreadcrumbsProps {
  folders: FolderItem[]
  selectedFolderId: number | null
  onSelect: (folderId: number | null) => void
}

export default function Breadcrumbs({
  folders,
  selectedFolderId,
  onSelect,
}: BreadcrumbsProps) {
  const selected = folders.find(
    (folder) => folder.id === selectedFolderId,
  )

  const breadcrumbs: FolderItem[] = []

  let current = selected

  while (current) {
    breadcrumbs.unshift(current)

    current = folders.find(
      (folder) => folder.id === current?.parent_id,
    )
  }

  return (
    <div className="folder-breadcrumbs">
      <button type="button" onClick={() => onSelect(null)}>
        <Folder size={16} />
        All Files
      </button>

      {breadcrumbs.map((folder) => (
        <div className="breadcrumb-item" key={folder.id}>
          <ChevronRight size={15} />

          <button
            type="button"
            onClick={() => onSelect(folder.id)}
          >
            {folder.name}
          </button>
        </div>
      ))}
    </div>
  )
}
