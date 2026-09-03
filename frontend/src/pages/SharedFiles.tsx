import {
  Clock3,
  Download,
  File,
  FileImage,
  FileText,
  Film,
  Grid2X2,
  List,
  Search,
  Share2,
  X,
} from 'lucide-react'
import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import {
  downloadFile,
  getMyShares,
  getSharedFiles,
  getSharedHistory,
  type ShareHistoryItem,
  type SharedFileItem,
} from '../services/fileService'

type Tab = 'received' | 'sent' | 'history'
type ViewMode = 'list' | 'grid'

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

function fileIcon(file: { mime_type?: string | null }) {
  const type = file.mime_type?.toLowerCase() ?? ''

  if (type.startsWith('image/')) return FileImage
  if (type.startsWith('video/')) return Film
  if (type.includes('pdf') || type.includes('text')) return FileText

  return File
}

function permissionLabel(permission: string | null | undefined) {
  if (permission === 'write') return 'Can edit'
  if (permission === 'manage') return 'Manage'
  return 'Can view'
}

export default function SharedFiles() {
  const [tab, setTab] = useState<Tab>('received')
  const [view, setView] = useState<ViewMode>('list')
  const [search, setSearch] = useState('')
  const [openFile, setOpenFile] = useState<SharedFileItem | null>(null)

  const receivedQuery = useQuery({
    queryKey: ['shared-files'],
    queryFn: getSharedFiles,
    enabled: tab === 'received',
    refetchInterval: 5000,
    refetchOnWindowFocus: true,
    staleTime: 0,
  })

  const sentQuery = useQuery({
    queryKey: ['my-shares'],
    queryFn: getMyShares,
    enabled: tab === 'sent',
    refetchInterval: 5000,
    refetchOnWindowFocus: true,
    staleTime: 0,
  })

  const historyQuery = useQuery({
    queryKey: ['shared-history'],
    queryFn: getSharedHistory,
    enabled: tab === 'history',
    refetchInterval: 5000,
    refetchOnWindowFocus: true,
    staleTime: 0,
  })

  const files =
    tab === 'received'
      ? receivedQuery.data ?? []
      : sentQuery.data ?? []

  const filteredFiles = useMemo(() => {
    const query = search.trim().toLowerCase()

    if (!query) return files

    return files.filter((file) =>
      [
        file.original_name,
        file.mime_type,
        file.share.shared_by.name,
        file.share.shared_by.email,
        file.share.shared_with?.name ?? '',
        file.share.shared_with?.email ?? '',
        file.share.permission,
      ].some((value) =>
        value.toLowerCase().includes(query),
      ),
    )
  }, [files, search])

  const filteredHistory = useMemo(() => {
    const query = search.trim().toLowerCase()

    if (!query) return historyQuery.data ?? []

    return (historyQuery.data ?? []).filter((item) =>
      [
        item.file.original_name ?? '',
        item.file.mime_type ?? '',
        item.shared_with.name ?? '',
        item.shared_with.email ?? '',
        item.permission ?? '',
        item.status,
        item.action,
      ].some((value) =>
        value.toLowerCase().includes(query),
      ),
    )
  }, [historyQuery.data, search])

  const isLoading =
    tab === 'received'
      ? receivedQuery.isLoading
      : tab === 'sent'
        ? sentQuery.isLoading
        : historyQuery.isLoading

  const isError =
    tab === 'received'
      ? receivedQuery.isError
      : tab === 'sent'
        ? sentQuery.isError
        : historyQuery.isError

  async function confirmOpenFile() {
    if (!openFile) return

    const file = openFile
    setOpenFile(null)

    try {
      const response = await downloadFile(file.id)
      saveBlob(response, file.original_name)
    } catch (error) {
      console.error('Shared file download failed:', error)

      window.alert(
        `Unable to open "${file.original_name}".`,
      )
    }
  }

  return (
    <section className="shared-page">
      <div className="shared-page-header">
        <div>
          <span className="page-eyebrow">WORKSPACE</span>
          <h1>Shared Files</h1>
          <p>
            View files you received, files you shared, and the
            complete sharing history.
          </p>
        </div>
      </div>

      <div className="shared-tabs">
        <button
          type="button"
          className={tab === 'received' ? 'active' : ''}
          onClick={() => {
            setTab('received')
            setSearch('')
          }}
        >
          Received
        </button>

        <button
          type="button"
          className={tab === 'sent' ? 'active' : ''}
          onClick={() => {
            setTab('sent')
            setSearch('')
          }}
        >
          My Shares
        </button>

        <button
          type="button"
          className={tab === 'history' ? 'active' : ''}
          onClick={() => {
            setTab('history')
            setSearch('')
          }}
        >
          History
        </button>
      </div>

      <div className="shared-toolbar">
        <div className="shared-search">
          <Search size={16} />
          <input
            type="search"
            placeholder={
              tab === 'history'
                ? 'Search share history...'
                : 'Search shared files...'
            }
            value={search}
            onChange={(event) => {
              setSearch(event.target.value)
            }}
          />
        </div>

        <div className="shared-toolbar-right">
          <span>
            {tab === 'history'
              ? filteredHistory.length
              : filteredFiles.length}{' '}
            {(
              tab === 'history'
                ? filteredHistory.length
                : filteredFiles.length
            ) === 1
              ? 'record'
              : 'records'}
          </span>

          {tab !== 'history' && (
            <div className="view-toggle">
              <button
                type="button"
                className={view === 'list' ? 'active' : ''}
                onClick={() => setView('list')}
              >
                <List size={16} />
              </button>

              <button
                type="button"
                className={view === 'grid' ? 'active' : ''}
                onClick={() => setView('grid')}
              >
                <Grid2X2 size={16} />
              </button>
            </div>
          )}
        </div>
      </div>

      <div className="shared-card">
        {isLoading ? (
          <div className="shared-state">
            Loading shared data...
          </div>
        ) : isError ? (
          <div className="shared-state shared-error">
            Unable to load shared data.
          </div>
        ) : tab === 'history' ? (
          filteredHistory.length === 0 ? (
            <div className="shared-empty">
              <div className="shared-empty-icon">
                <Clock3 size={22} />
              </div>

              <h3>
                {search
                  ? 'No history found'
                  : 'No sharing history'}
              </h3>

              <p>
                Share and revoke events will appear here.
              </p>
            </div>
          ) : (
            <div className="shared-table">
              <div className="shared-table-header">
                <span>File</span>
                <span>Direction</span>
                <span>Permission</span>
                <span>Status</span>
                <span>When</span>
              </div>

              {filteredHistory.map(
                (item: ShareHistoryItem) => {
                  const Icon = fileIcon(item.file)

                  return (
                    <div
                      className="shared-row history-row"
                      key={item.id}
                    >
                      <span className="shared-file-name">
                        <span className="shared-file-icon">
                          <Icon size={18} />
                        </span>

                        <span>
                          <strong>
                            {item.file.original_name ??
                              'Unknown file'}
                          </strong>

                          <small>
                            {item.file.size_bytes !== null
                              ? formatSize(
                                  item.file.size_bytes,
                                )
                              : 'Unknown size'}{' '}
                            ·{' '}
                            {item.file.mime_type ??
                              'Unknown type'}
                          </small>
                        </span>
                      </span>

                      <span className="shared-person">
                        <strong>
                          {item.status === 'shared'
                            ? 'Shared with'
                            : 'Unshared'}
                        </strong>

                        <small>
                          {item.shared_with.name ??
                            item.shared_with.email ??
                            'Unknown user'}
                        </small>
                      </span>

                      <span
                        className={`shared-permission ${
                          item.permission ?? 'read'
                        }`}
                      >
                        {permissionLabel(
                          item.permission,
                        )}
                      </span>

                      <span
                        className={`shared-status ${
                          item.status
                        }`}
                      >
                        {item.status}
                      </span>

                      <span className="shared-date">
                        {item.created_at}
                      </span>
                    </div>
                  )
                },
              )}
            </div>
          )
        ) : filteredFiles.length === 0 ? (
          <div className="shared-empty">
            <div className="shared-empty-icon">
              <Share2 size={22} />
            </div>

            <h3>
              {search
                ? 'No shared files found'
                : tab === 'received'
                  ? 'No files have been shared with you'
                  : 'You have not shared any files'}
            </h3>

            <p>
              {tab === 'received'
                ? 'Files shared with your account will appear here.'
                : 'Files you share with other users will appear here.'}
            </p>
          </div>
        ) : view === 'list' ? (
          <div className="shared-table">
            <div className="shared-table-header">
              <span>Name</span>
              <span>
                {tab === 'received'
                  ? 'Shared by'
                  : 'Shared with'}
              </span>
              <span>Permission</span>
              <span>Shared</span>
            </div>

            {filteredFiles.map((file) => {
              const Icon = fileIcon(file)

              const person =
                tab === 'received'
                  ? file.share.shared_by
                  : file.share.shared_with

              return (
                <button
                  className="shared-row"
                  key={`${file.id}-${file.share.id ?? file.share.shared_at}`}
                  type="button"
                  onClick={() => setOpenFile(file)}
                >
                  <span className="shared-file-name">
                    <span className="shared-file-icon">
                      <Icon size={18} />
                    </span>

                    <span>
                      <strong>
                        {file.original_name}
                      </strong>

                      <small>
                        {formatSize(file.size_bytes)} ·{' '}
                        {file.mime_type ||
                          'Unknown type'}
                      </small>
                    </span>
                  </span>

                  <span className="shared-person">
                    <strong>
                      {person?.name ?? 'Unknown user'}
                    </strong>
                    <small>
                      {person?.email ?? ''}
                    </small>
                  </span>

                  <span
                    className={`shared-permission ${file.share.permission}`}
                  >
                    {permissionLabel(
                      file.share.permission,
                    )}
                  </span>

                  <span className="shared-date">
                    {file.share.shared_at}
                  </span>
                </button>
              )
            })}
          </div>
        ) : (
          <div className="shared-grid">
            {filteredFiles.map((file) => {
              const Icon = fileIcon(file)

              const person =
                tab === 'received'
                  ? file.share.shared_by
                  : file.share.shared_with

              return (
                <button
                  className="shared-file-card"
                  key={`${file.id}-${file.share.id ?? file.share.shared_at}`}
                  type="button"
                  onClick={() => setOpenFile(file)}
                >
                  <div className="shared-card-icon">
                    <Icon size={22} />
                  </div>

                  <strong>
                    {file.original_name}
                  </strong>

                  <span>
                    {tab === 'received'
                      ? 'Shared by'
                      : 'Shared with'}{' '}
                    {person?.name ?? 'Unknown user'}
                  </span>

                  <span
                    className={`shared-permission ${file.share.permission}`}
                  >
                    {permissionLabel(
                      file.share.permission,
                    )}
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
          <div className="details-modal shared-confirm-modal">
            <div className="details-modal-header">
              <div>
                <div className="eyebrow">
                  SHARED FILE
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
                onClick={() =>
                  void confirmOpenFile()
                }
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
