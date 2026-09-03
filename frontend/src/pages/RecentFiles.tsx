import {
  Download,
  File,
  FileImage,
  FileText,
  Film,
  Grid2X2,
  List,
  Search,
  Clock3,
  X,
} from 'lucide-react'
import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import {
  downloadFile,
  getFiles,
} from '../services/fileService'
import type { FileItem } from '../types/file'

function saveBlob(response: { data: Blob }, filename: string) {
  const url = URL.createObjectURL(response.data)
  const link = document.createElement('a')

  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()

  URL.revokeObjectURL(url)
}

function formatSize(bytes: number) {
  if (bytes < 1024) return `${bytes} B`

  const units = ['KB', 'MB', 'GB', 'TB']
  let size = bytes / 1024
  let unit = 0

  while (size >= 1024 && unit < units.length - 1) {
    size /= 1024
    unit += 1
  }

  return `${size.toFixed(size >= 10 ? 0 : 1)} ${units[unit]}`
}

function getFileIcon(file: FileItem) {
  const type = file.mime_type?.toLowerCase() ?? ''

  if (type.startsWith('image/')) return FileImage
  if (type.startsWith('video/')) return Film

  if (
    type.includes('pdf') ||
    type.includes('text') ||
    type.includes('word') ||
    type.includes('excel') ||
    type.includes('powerpoint')
  ) {
    return FileText
  }

  return File
}

export default function RecentFiles() {
  const [search, setSearch] = useState('')
  const [view, setView] =
    useState<'list' | 'grid'>('list')
  const [openFile, setOpenFile] =
    useState<FileItem | null>(null)

  const {
    data: files = [],
    isLoading,
    isError,
  } = useQuery({
    queryKey: ['recent-files'],
    queryFn: () => getFiles(null),
    refetchInterval: 5000,
    refetchOnWindowFocus: true,
    staleTime: 0,
  })

  const recentFiles = useMemo(() => {
    const sorted = [...files].sort(
      (a, b) =>
        new Date(b.uploaded_at).getTime() -
        new Date(a.uploaded_at).getTime(),
    )

    return sorted.slice(0, 50)
  }, [files])

  const filteredFiles = useMemo(() => {
    const query = search.trim().toLowerCase()

    if (!query) return recentFiles

    return recentFiles.filter((file) =>
      [
        file.original_name,
        file.mime_type,
        file.extension ?? '',
      ].some((value) =>
        value.toLowerCase().includes(query),
      ),
    )
  }, [recentFiles, search])

  async function confirmOpen() {
    if (!openFile) return

    const file = openFile
    setOpenFile(null)

    try {
      const response = await downloadFile(file.id)
      saveBlob(response, file.original_name)
    } catch (error) {
      console.error(
        'Recent file download failed:',
        error,
      )

      window.alert(
        `Unable to open "${file.original_name}".`,
      )
    }
  }

  return (
    <section className="recent-page">
      <div className="recent-page-header">
        <div>
          <span className="page-eyebrow">
            WORKSPACE
          </span>

          <h1>Recent Files</h1>

          <p>
            Your most recently uploaded files.
          </p>
        </div>
      </div>

      <div className="recent-toolbar">
        <div className="recent-search">
          <Search size={16} />

          <input
            type="search"
            placeholder="Search recent files..."
            value={search}
            onChange={(event) =>
              setSearch(event.target.value)
            }
          />
        </div>

        <div className="recent-toolbar-right">
          <span>
            {filteredFiles.length}{' '}
            {filteredFiles.length === 1
              ? 'file'
              : 'files'}
          </span>

          <div className="view-toggle">
            <button
              type="button"
              className={
                view === 'list' ? 'active' : ''
              }
              onClick={() => setView('list')}
            >
              <List size={16} />
            </button>

            <button
              type="button"
              className={
                view === 'grid' ? 'active' : ''
              }
              onClick={() => setView('grid')}
            >
              <Grid2X2 size={16} />
            </button>
          </div>
        </div>
      </div>

      <div className="recent-card">
        {isLoading ? (
          <div className="recent-state">
            Loading recent files...
          </div>
        ) : isError ? (
          <div className="recent-state recent-error">
            Unable to load recent files.
          </div>
        ) : filteredFiles.length === 0 ? (
          <div className="recent-empty">
            <div className="recent-empty-icon">
              <Clock3 size={22} />
            </div>

            <h3>
              {search
                ? 'No recent files found'
                : 'No recent files'}
            </h3>

            <p>
              {search
                ? 'Try a different search.'
                : 'Files you upload will appear here.'}
            </p>
          </div>
        ) : view === 'list' ? (
          <div className="recent-table">
            <div className="recent-table-header">
              <span>Name</span>
              <span>Type</span>
              <span>Size</span>
              <span>Uploaded</span>
            </div>

            {filteredFiles.map((file) => {
              const Icon = getFileIcon(file)

              return (
                <button
                  className="recent-row"
                  key={file.id}
                  type="button"
                  onClick={() => setOpenFile(file)}
                >
                  <span className="recent-file-name">
                    <span className="recent-file-icon">
                      <Icon size={18} />
                    </span>

                    <span>
                      <strong>
                        {file.original_name}
                      </strong>

                      <small>
                        {file.extension
                          ? file.extension.toUpperCase()
                          : 'FILE'}
                      </small>
                    </span>
                  </span>

                  <span className="recent-type">
                    {file.mime_type ||
                      'Unknown type'}
                  </span>

                  <span className="recent-size">
                    {formatSize(file.size_bytes)}
                  </span>

                  <span className="recent-date">
                    {file.uploaded_at}
                  </span>
                </button>
              )
            })}
          </div>
        ) : (
          <div className="recent-grid">
            {filteredFiles.map((file) => {
              const Icon = getFileIcon(file)

              return (
                <button
                  className="recent-file-card"
                  key={file.id}
                  type="button"
                  onClick={() => setOpenFile(file)}
                >
                  <div className="recent-card-icon">
                    <Icon size={22} />
                  </div>

                  <strong>
                    {file.original_name}
                  </strong>

                  <span>
                    {formatSize(file.size_bytes)}
                  </span>

                  <span>
                    {file.uploaded_at}
                  </span>
                </button>
              )
            })}
          </div>
        )}
      </div>

      {openFile && (
        <div
          className="modal-backdrop"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) {
              setOpenFile(null)
            }
          }}
        >
          <div className="details-modal recent-confirm-modal">
            <div className="details-modal-header">
              <div>
                <div className="eyebrow">
                  RECENT FILE
                </div>

                <h2>Open file?</h2>

                <p>
                  Do you want to open{' '}
                  <strong>
                    {openFile.original_name}
                  </strong>
                  ?
                </p>
              </div>

              <button
                className="icon-button"
                type="button"
                onClick={() => setOpenFile(null)}
                aria-label="Close"
              >
                <X size={18} />
              </button>
            </div>

            <div className="details-modal-footer">
              <button
                className="secondary-button"
                type="button"
                onClick={() => setOpenFile(null)}
              >
                Cancel
              </button>

              <button
                className="primary-button"
                type="button"
                onClick={() => void confirmOpen()}
              >
                <Download size={16} />
                Open
              </button>
            </div>
          </div>
        </div>
      )}
    </section>
  )
}
