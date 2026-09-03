import { useState } from 'react'
import {
  ChevronDown,
  ChevronRight,
  Folder,
  FolderOpen,
} from 'lucide-react'
import type { FolderItem } from '../../services/folderService'

interface FolderTreeProps {
  folders: FolderItem[]
  selectedFolderId: number | null
  onSelect: (folderId: number | null) => void
}

export default function FolderTree({
  folders,
  selectedFolderId,
  onSelect,
}: FolderTreeProps) {
  const roots = folders.filter((folder) => folder.parent_id === null)

  const [expanded, setExpanded] = useState<Set<number>>(
    () => new Set(roots.map((folder) => folder.id)),
  )

  function toggleFolder(folderId: number) {
    setExpanded((current) => {
      const next = new Set(current)

      if (next.has(folderId)) {
        next.delete(folderId)
      } else {
        next.add(folderId)
      }

      return next
    })
  }

  function renderFolder(folder: FolderItem, level = 0) {
    const children = folders.filter(
      (child) => child.parent_id === folder.id,
    )

    const selected = selectedFolderId === folder.id
    const isExpanded = expanded.has(folder.id)
    const hasChildren = children.length > 0

    return (
      <div key={folder.id}>
        <div
          className={`folder-tree-row ${selected ? 'active' : ''}`}
          style={{ paddingLeft: `${8 + level * 18}px` }}
        >
          {hasChildren ? (
            <button
              type="button"
              className="folder-tree-toggle"
              onClick={() => toggleFolder(folder.id)}
              aria-label={
                isExpanded
                  ? `Collapse ${folder.name}`
                  : `Expand ${folder.name}`
              }
            >
              {isExpanded ? (
                <ChevronDown size={14} />
              ) : (
                <ChevronRight size={14} />
              )}
            </button>
          ) : (
            <span className="folder-tree-toggle-spacer" />
          )}

          <button
            type="button"
            className="folder-tree-item"
            onClick={() => onSelect(folder.id)}
          >
            {selected || isExpanded ? (
              <FolderOpen size={17} />
            ) : (
              <Folder size={17} />
            )}

            <span>{folder.name}</span>
          </button>
        </div>

        {hasChildren &&
          isExpanded &&
          children.map((child) => renderFolder(child, level + 1))}
      </div>
    )
  }

  return (
    <aside className="folder-tree">
      <button
        type="button"
        className={`folder-tree-item all-files ${
          selectedFolderId === null ? 'active' : ''
        }`}
        onClick={() => onSelect(null)}
      >
        <FolderOpen size={17} />
        <span>All Files</span>
      </button>

      {roots.map((folder) => renderFolder(folder))}
    </aside>
  )
}
