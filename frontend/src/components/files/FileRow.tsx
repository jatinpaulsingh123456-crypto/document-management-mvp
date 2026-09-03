import {
  Download,
  FileArchive,
  FileImage,
  FileText,
  Film,
  MoreHorizontal,
  Pencil,
  Share2,
  Trash2,
  Folder,
} from 'lucide-react'
import { createPortal } from 'react-dom'
import { useEffect, useRef, useState } from 'react'
import type { FileItem } from '../../types/file'

interface FileRowProps {
  file: FileItem
  onDownload: (file: FileItem) => void | Promise<void>
  onEdit: (file: FileItem) => void | Promise<void>
  onShare: (file: FileItem) => void | Promise<void>
  onDelete: (file: FileItem) => void | Promise<void>
  onMove: (file: FileItem) => void | Promise<void>
  canManage: boolean
  canShare: boolean
}

function getIcon(mimeType: string) {
  if (mimeType.startsWith('image/')) return FileImage
  if (mimeType.startsWith('video/')) return Film
  if (
    mimeType.includes('zip') ||
    mimeType.includes('archive') ||
    mimeType.includes('compressed')
  ) {
    return FileArchive
  }
  if (
    mimeType.includes('pdf') ||
    mimeType.includes('word') ||
    mimeType.includes('document') ||
    mimeType.includes('text') ||
    mimeType.includes('spreadsheet') ||
    mimeType.includes('presentation')
  ) {
    return FileText
  }

  return FileText
}

function formatSize(bytes: number) {
  if (bytes < 1024) return `${bytes} B`

  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`
  }

  if (bytes < 1024 * 1024 * 1024) {
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
  }

  return `${(bytes / (1024 * 1024 * 1024)).toFixed(1)} GB`
}

export default function FileRow({
  file,
  onDownload,
  onEdit,
  onShare,
  onDelete,
  onMove,
  canManage,
  canShare,
}: FileRowProps) {
  const Icon = getIcon(file.mime_type || '')

  const [menuOpen, setMenuOpen] = useState(false)
  const [menuPosition, setMenuPosition] = useState({
    top: 0,
    left: 0,
  })

  const buttonRef = useRef<HTMLButtonElement>(null)
  const menuRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    function handleOtherMenu(event: Event) {
      const customEvent = event as CustomEvent<number>

      if (customEvent.detail !== file.id) {
        setMenuOpen(false)
      }
    }

    window.addEventListener(
      'docvault:file-menu-open',
      handleOtherMenu,
    )

    return () => {
      window.removeEventListener(
        'docvault:file-menu-open',
        handleOtherMenu,
      )
    }
  }, [file.id])

  useEffect(() => {
    if (!menuOpen) return

    const positionMenu = () => {
      const button = buttonRef.current
      const menu = menuRef.current

      if (!button || !menu) return

      const buttonRect = button.getBoundingClientRect()
      const menuRect = menu.getBoundingClientRect()

      const gap = 6
      const viewportPadding = 8

      let top = buttonRect.bottom + gap

      if (
        top + menuRect.height >
          window.innerHeight - viewportPadding &&
        buttonRect.top - menuRect.height - gap >= viewportPadding
      ) {
        top = buttonRect.top - menuRect.height - gap
      }

      top = Math.max(
        viewportPadding,
        Math.min(
          top,
          window.innerHeight - menuRect.height - viewportPadding,
        ),
      )

      let left = buttonRect.right - menuRect.width

      left = Math.max(
        viewportPadding,
        Math.min(
          left,
          window.innerWidth - menuRect.width - viewportPadding,
        ),
      )

      setMenuPosition({
        top,
        left,
      })
    }

    const frame = requestAnimationFrame(positionMenu)

    window.addEventListener('resize', positionMenu)
    window.addEventListener('scroll', positionMenu, true)

    return () => {
      cancelAnimationFrame(frame)
      window.removeEventListener('resize', positionMenu)
      window.removeEventListener('scroll', positionMenu, true)
    }
  }, [menuOpen])

  useEffect(() => {
    if (!menuOpen) return

    const handleOutsideClick = (event: PointerEvent) => {
      const target = event.target as Node

      if (buttonRef.current?.contains(target)) return
      if (menuRef.current?.contains(target)) return

      setMenuOpen(false)
    }

    document.addEventListener(
      'pointerdown',
      handleOutsideClick,
    )

    return () => {
      document.removeEventListener(
        'pointerdown',
        handleOutsideClick,
      )
    }
  }, [menuOpen])

  function toggleMenu() {
    if (!menuOpen) {
      window.dispatchEvent(
        new CustomEvent('docvault:file-menu-open', {
          detail: file.id,
        }),
      )
    }

    setMenuOpen((open) => !open)
  }

  function closeMenu() {
    setMenuOpen(false)
  }

  const menu = menuOpen
    ? createPortal(
        <div
          ref={menuRef}
          className="file-action-menu file-action-menu-portal"
          style={{
            top: `${menuPosition.top}px`,
            left: `${menuPosition.left}px`,
          }}
        >
          <button
            type="button"
            onClick={() => {
              closeMenu()
              void onDownload(file)
            }}
          >
            <Download size={16} />
            <span>Download</span>
          </button>

          {canManage && (
            <button
              type="button"
              onClick={() => {
                closeMenu()
                void onEdit(file)
              }}
            >
              <Pencil size={16} />
              <span>Rename</span>
            </button>
          )}

          {canShare && (
            <button
              type="button"
              onClick={() => {
                closeMenu()
                void onShare(file)
              }}
            >
              <Share2 size={16} />
              <span>Share</span>
            </button>
          )}

          {canManage && (
            <button
              type="button"
              onClick={() => {
                closeMenu()
                void onMove(file)
              }}
            >
              <Folder size={16} />
              <span>Move</span>
            </button>
          )}

          {canManage && (
            <button
              type="button"
              onClick={() => {
                closeMenu()
                void onDelete(file)
              }}
            >
              <Trash2 size={16} />
              <span>Delete</span>
            </button>
          )}
        </div>,
        document.body,
      )
    : null

  return (
    <>
      <div className="file-row">
        <div className="file-name-cell">
          <div className="file-type-icon">
            <Icon size={18} />
          </div>

          <div>
            <strong>{file.original_name}</strong>
            <span>{file.mime_type || 'Unknown type'}</span>
          </div>
        </div>

        <span>{formatSize(file.size_bytes)}</span>

        <span>{file.uploaded_at}</span>

        <div className={`file-actions ${menuOpen ? 'menu-open' : ''}`}>
          <button
            ref={buttonRef}
            className="icon-button"
            type="button"
            onClick={toggleMenu}
            aria-label={`Actions for ${file.original_name}`}
            aria-expanded={menuOpen}
          >
            <MoreHorizontal size={18} />
          </button>
        </div>
      </div>

      {menu}
    </>
  )
}
