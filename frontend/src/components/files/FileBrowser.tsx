import {
  Plus,
  Download,
  FileWarning,
  Grid2X2,
  List,
  RefreshCw,
  Search,
  Upload,
  X,
  File as FileIcon,

  Folder,
  Check,} from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import {
  checkDuplicateChecksums,
  deleteFile,
  downloadFile,
  moveFile,
  shareFile,
  updateFile,
} from '../../services/fileService'
import type { FileItem } from '../../types/file'
import { useFiles } from '../../hooks/useFiles'
import { useUploadFile } from '../../hooks/useUploadFile'
import { useFolders } from '../../hooks/useFolders'
import { useShareRecipients } from '../../hooks/useUsers'
import Breadcrumbs from '../folders/Breadcrumbs'
import FolderTree from '../folders/FolderTree'
import FileRow from './FileRow'
import { useAuth } from '../../hooks/useAuth'

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

function formatFileSize(bytes: number) {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  if (bytes < 1024 * 1024 * 1024) {
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
  }

  return `${(bytes / (1024 * 1024 * 1024)).toFixed(1)} GB`
}
export default function FileBrowser() {
  const {
    user,
    isGlobal,
    isAuditor,
    isDepartmentHead,
    isEmployee,
  } = useAuth()

  const currentUserId = user?.id ?? null


  const canUpload =
    !isAuditor &&
    (
      isGlobal ||
      isDepartmentHead ||
      isEmployee
    )

  const [searchParams, setSearchParams] = useSearchParams()
  const [selectedFolderId, setSelectedFolderId] = useState<number | null>(null)

  const [view, setView] = useState<'list' | 'grid'>(() => {
    const saved = localStorage.getItem('default-file-view')
    return saved === 'grid' ? 'grid' : 'list'
  })
  useEffect(() => {
    const syncViewPreference = () => {
      const saved = localStorage.getItem('default-file-view')

      if (saved === 'grid' || saved === 'list') {
        setView(saved)
      }
    }

    window.addEventListener(
      'file-view-preference-changed',
      syncViewPreference,
    )

    window.addEventListener('storage', syncViewPreference)

    return () => {
      window.removeEventListener(
        'file-view-preference-changed',
        syncViewPreference,
      )

      window.removeEventListener(
        'storage',
        syncViewPreference,
      )
    }
  }, [])


  const [searchQuery, setSearchQuery] = useState('')
  const [searchOpen, setSearchOpen] = useState(false)

  const MAX_UPLOAD_FILE_SIZE = 50 * 1024 * 1024

  const ALLOWED_UPLOAD_EXTENSIONS = new Set([
    'pdf',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'ppt',
    'pptx',
    'txt',
    'md',
    'csv',
    'jpg',
    'jpeg',
    'png',
    'gif',
    'webp',
    'mp4',
    'mov',
    'avi',
    'mkv',
    'zip',
    'rar',
    '7z',
  ])


  function validateUploadFile(file: File): string | null {
    if (file.size > MAX_UPLOAD_FILE_SIZE) {
      return 'File is larger than 50 MB.'
    }

    const dot = file.name.lastIndexOf('.')
    const extension =
      dot === -1
        ? ''
        : file.name.slice(dot + 1).toLowerCase()

    if (!ALLOWED_UPLOAD_EXTENSIONS.has(extension)) {
      return 'Unsupported file type.'
    }

    return null
  }




  const [uploadOpen, setUploadOpen] = useState(false)
  const [selectedFiles, setSelectedFiles] = useState<File[]>([])
  const [rejectedFiles, setRejectedFiles] = useState<
    Array<{ name: string; reason: string }>
  >([])
  const [validationToast, setValidationToast] = useState<string | null>(null)
  const [validationChecking, setValidationChecking] = useState(false)
  const [folderHint, setFolderHint] = useState(false)
  const [dragActive, setDragActive] = useState(false)
  const [editFile, setEditFile] = useState<FileItem | null>(null)
  const [editName, setEditName] = useState('')
  const [editSaving, setEditSaving] = useState(false)
  const [shareFileItem, setShareFileItem] = useState<FileItem | null>(null)
  const [shareSearch, setShareSearch] = useState('')
  const [shareUserIds, setShareUserIds] = useState<number[]>([])
  const [sharePermission, setSharePermission] = useState<'read' | 'write'>('read')
  const [shareSaving, setShareSaving] = useState(false)
  const [moveFileItem, setMoveFileItem] = useState<FileItem | null>(null)
  const [moveFolderId, setMoveFolderId] = useState<number | null>(null)
  const [moveSaving, setMoveSaving] = useState(false)

  const fileInputRef = useRef<HTMLInputElement>(null)
  const validationRunRef = useRef(0)
  const uploadMutation = useUploadFile()

  const {
    data: files = [],
    isLoading,
    isError,
    refetch,
  } = useFiles(selectedFolderId)

  const FILES_PER_PAGE = 10
  const [currentPage, setCurrentPage] = useState(1)

  useEffect(() => {
    setCurrentPage(1)
  }, [selectedFolderId])

  const filteredFiles = files.filter((file) => {
    const query = searchQuery.trim().toLowerCase()

    if (!query) return true

    return [
      file.original_name,
      file.name,
      String(file.id),
    ]
      .filter(Boolean)
      .some((value) =>
        String(value).toLowerCase().includes(query),
      )
  })

  const totalFilePages = Math.max(
    1,
    Math.ceil(filteredFiles.length / FILES_PER_PAGE),
  )

  const paginatedFiles = filteredFiles.slice(
    (currentPage - 1) * FILES_PER_PAGE,
    currentPage * FILES_PER_PAGE,
  )

  const {
    data: folders = [],
    isLoading: foldersLoading,
    isError: foldersError,
  } = useFolders()

  const {
    data: users = [],
    isLoading: usersLoading,
    isError: usersError,
  } = useShareRecipients(shareFileItem?.id ?? null)

  const currentFolder = folders.find(
    (folder) => folder.id === selectedFolderId,
  )

  const displayedFiles = filteredFiles



  useEffect(() => {
    if (selectedFolderId === null || selectedFiles.length === 0) {
      return
    }

    const filesAtStart = [...selectedFiles]

    void (async () => {
      const checkedFiles = await validateFilesAgainstFolder(
        filesAtStart,
        selectedFolderId,
      )

      /*
       * Only replace the queue if this validation still represents
       * the same selection. Never overwrite a newer user selection.
       */
      setSelectedFiles((current) => {
        const checkedKeys = new Set(
          checkedFiles.map(
            (file) =>
              `${file.name}-${file.size}-${file.lastModified}`,
          ),
        )

        /*
         * Keep files added after this validation started.
         */
        const newerFiles = current.filter((file) =>
          !filesAtStart.some(
            (original) =>
              original.name === file.name &&
              original.size === file.size &&
              original.lastModified === file.lastModified,
          ),
        )

        return [
          ...checkedFiles,
          ...newerFiles.filter((file) =>
            !checkedKeys.has(
              `${file.name}-${file.size}-${file.lastModified}`,
            ),
          ),
        ]
      })
    })()
  }, [selectedFolderId])

  useEffect(() => {
    if (searchParams.get('upload') !== '1') {
      return
    }

    setSelectedFiles([])
    setUploadOpen(true)

    const nextParams = new URLSearchParams(searchParams)
    nextParams.delete('upload')
    setSearchParams(nextParams, { replace: true })
  }, [searchParams, setSearchParams])

  function openUpload() {
    setSelectedFiles([])
    setRejectedFiles([])
    setUploadOpen(true)
  }

  function closeUpload() {
    if (uploadMutation.isPending) return

    setUploadOpen(false)
    setSelectedFiles([])
    setRejectedFiles([])
    setDragActive(false)

    if (fileInputRef.current) {
      fileInputRef.current.value = ''
    }
  }

  async function hashLocalFile(file: File): Promise<string> {
    const buffer = await file.arrayBuffer()
    const digest = await crypto.subtle.digest('SHA-256', buffer)

    return Array.from(new Uint8Array(digest))
      .map((byte) => byte.toString(16).padStart(2, '0'))
      .join('')
  }

  function showValidationToast(message: string) {
    setValidationToast(message)

    window.setTimeout(() => {
      setValidationToast(null)
    }, 3000)
  }

  async function validateFilesAgainstFolder(
    candidates: File[],
    folderId: number | null,
    existingFiles: File[] = [],
  ): Promise<File[]> {
    if (candidates.length === 0) {
      return []
    }

    if (folderId === null) {
      return candidates
    }

    const runId = ++validationRunRef.current
    setValidationChecking(true)

    try {
      /*
       * Hash ONLY the files involved in this validation.
       * This lets us detect:
       *
       * 1. duplicates already in the database
       * 2. duplicates already selected in the modal
       * 3. duplicates inside the newly selected batch
       *
       * The first occurrence wins.
       */
      const allFiles = [...existingFiles, ...candidates]

      const allChecksums = await Promise.all(
        allFiles.map((file) => hashLocalFile(file)),
      )

      if (runId !== validationRunRef.current) {
        return []
      }

      const existingCount = existingFiles.length

      const existingChecksumMap = new Map<string, File>()

      for (let index = 0; index < existingCount; index += 1) {
        existingChecksumMap.set(
          allChecksums[index],
          existingFiles[index],
        )
      }

      /*
       * Check the incoming files against the database.
       */
      const candidateChecksums = allChecksums.slice(existingCount)

      const duplicates = await checkDuplicateChecksums(
        candidateChecksums,
      )

      if (runId !== validationRunRef.current) {
        return []
      }

      const duplicateMap = new Map(
        duplicates.map((duplicate) => [
          duplicate.checksum,
          duplicate,
        ]),
      )

      const seenChecksums = new Set<string>()

      /*
       * Existing files are already accepted and therefore
       * should be considered occupied checksums.
       */
      for (const checksum of existingChecksumMap.keys()) {
        seenChecksums.add(checksum)
      }

      const validFiles: File[] = []
      const rejected: Array<{
        name: string
        reason: string
      }> = []

      for (
        let index = 0;
        index < candidates.length;
        index += 1
      ) {
        const file = candidates[index]
        const checksum = candidateChecksums[index]

        /*
         * Duplicate in the database.
         */
        const databaseDuplicate = duplicateMap.get(checksum)

        if (databaseDuplicate) {
          rejected.push({
            name: file.name,
            reason: databaseDuplicate.original_name
              ? `A document with the same content already exists as "${databaseDuplicate.original_name}".`
              : 'A document with the same content already exists in the system.',
          })

          continue
        }

        /*
         * Duplicate against a file already selected
         * in this upload modal.
         */
        if (existingChecksumMap.has(checksum)) {
          const original = existingChecksumMap.get(checksum)

          rejected.push({
            name: file.name,
            reason: original
              ? `This file has already been selected as "${original.name}".`
              : 'This file has already been selected.',
          })

          continue
        }

        /*
         * Duplicate inside the current selection batch.
         */
        if (seenChecksums.has(checksum)) {
          const original = validFiles.find(
            (_item, validIndex) =>
              candidateChecksums[
                validIndex
              ] === checksum,
          )

          rejected.push({
            name: file.name,
            reason: original
              ? `This file is already selected as "${original.name}".`
              : 'This file is already selected.',
          })

          continue
        }

        seenChecksums.add(checksum)
        validFiles.push(file)
      }

      if (rejected.length > 0) {
        setRejectedFiles((current) => {
          const next = [...current]

          for (const item of rejected) {
            const alreadyShown = next.some(
              (existing) =>
                existing.name === item.name &&
                existing.reason === item.reason,
            )

            if (!alreadyShown) {
              next.push(item)
            }
          }

          return next
        })

        const message =
          rejected.length === 1
            ? `${rejected[0].name}: ${rejected[0].reason}`
            : `${rejected.length} files rejected. See the details below.`

        showValidationToast(message)
      }

      return validFiles
    } catch (error) {
      console.error(
        'Upload validation failed:',
        error,
      )

      if (runId === validationRunRef.current) {
        showValidationToast(
          'Unable to verify the selected files. Nothing was added.',
        )
      }

      return []
    } finally {
      if (runId === validationRunRef.current) {
        setValidationChecking(false)
      }
    }
  }

  async function addFiles(
    files: FileList | File[] | undefined,
  ) {
    if (!files || uploadMutation.isPending) {
      return
    }

    const incoming = Array.from(files)

    if (incoming.length === 0) {
      return
    }

    /*
     * STEP 1:
     * Validate basic file rules BEFORE hashing or contacting
     * the backend.
     */
    const validByRules: File[] = []
    const invalidFiles: Array<{
      name: string
      reason: string
    }> = []

    for (const file of incoming) {
      const reason = validateUploadFile(file)

      if (reason) {
        invalidFiles.push({
          name: file.name,
          reason,
        })
      } else {
        validByRules.push(file)
      }
    }

    if (invalidFiles.length > 0) {
      setRejectedFiles((current) => [
        ...current,
        ...invalidFiles.filter(
          (item) =>
            !current.some(
              (existing) =>
                existing.name === item.name &&
                existing.reason === item.reason,
            ),
        ),
      ])

      showValidationToast(
        invalidFiles.length === 1
          ? `${invalidFiles[0].name}: ${invalidFiles[0].reason}`
          : `${invalidFiles.length} files rejected. See the details below.`,
      )
    }

    /*
     * Nothing passed the basic rules.
     */
    if (validByRules.length === 0) {
      if (fileInputRef.current) {
        fileInputRef.current.value = ''
      }

      return
    }

    /*
     * STEP 2:
     * Validate the NEW files against the files already present
     * in the upload queue.
     *
     * This is the part the old implementation was missing.
     */
    const currentSelectedFiles = selectedFiles

    const checkedFiles =
      selectedFolderId !== null
        ? await validateFilesAgainstFolder(
            validByRules,
            selectedFolderId,
            currentSelectedFiles,
          )
        : validByRules

    /*
     * Do not let an older async validation overwrite a newer
     * selection.
     */
    if (validationRunRef.current > 0 && validationChecking) {
      /*
       * The validation function itself owns the run ordering.
       * Nothing needs to be committed here when it returned
       * because a newer validation has taken over.
       */
    }

    /*
     * STEP 3:
     * Merge only files that actually passed validation.
     *
     * Do NOT silently filter them by filename/size/lastModified.
     */
    setSelectedFiles((current) => {
      const currentKeys = new Set(
        current.map(
          (file) =>
            `${file.name}-${file.size}-${file.lastModified}`,
        ),
      )

      const additions = checkedFiles.filter((file) => {
        const key = `${file.name}-${file.size}-${file.lastModified}`

        if (currentKeys.has(key)) {
          return false
        }

        currentKeys.add(key)
        return true
      })

      return [...current, ...additions]
    })

    if (fileInputRef.current) {
      fileInputRef.current.value = ''
    }
  }

  function removeSelectedFile(index: number) {
    setSelectedFiles((current) =>
      current.filter((_, fileIndex) => fileIndex !== index),
    )
  }

  function handleFileInput(
    event: React.ChangeEvent<HTMLInputElement>,
  ) {
    addFiles(event.target.files ?? undefined)
  }

  function handleDragOver(event: React.DragEvent<HTMLDivElement>) {
    event.preventDefault()
    setDragActive(true)
  }

  function handleDragLeave(event: React.DragEvent<HTMLDivElement>) {
    event.preventDefault()
    setDragActive(false)
  }

  async function handleDrop(
    event: React.DragEvent<HTMLDivElement>,
  ) {
    event.preventDefault()
    setDragActive(false)
    await addFiles(event.dataTransfer.files)
  }

  async function handleUpload() {
    if (selectedFiles.length === 0) return

    if (selectedFolderId === null) {
      setFolderHint(true)
      return
    }

    /*
     * Final live database validation immediately before upload.
     */
    const checkedFiles = await validateFilesAgainstFolder(
      selectedFiles,
      selectedFolderId,
    )

    if (checkedFiles.length !== selectedFiles.length) {
      setSelectedFiles(checkedFiles)

      if (checkedFiles.length === 0) {
        showValidationToast(
          'No valid files remain. Please select another file.',
        )
        return
      }

      showValidationToast(
        `${selectedFiles.length - checkedFiles.length} file(s) rejected. ${checkedFiles.length} valid file(s) will continue uploading.`,
      )
    }

    if (checkedFiles.length === 0) {
      return
    }

    const failedFiles: Array<{
      file: File
      reason: string
    }> = []

    for (const file of checkedFiles) {
      try {
        await uploadMutation.mutateAsync({
          file,
          folderId: selectedFolderId,
        })
      } catch (error: any) {
        failedFiles.push({
          file,
          reason:
            error?.response?.data?.message ||
            error?.response?.data?.error ||
            'Upload failed.',
        })
      }
    }

    await refetch()

    if (failedFiles.length > 0) {
      setSelectedFiles(failedFiles.map((item) => item.file))

      setRejectedFiles((current) => [
        ...current,
        ...failedFiles.map((item) => ({
          name: item.file.name,
          reason: item.reason,
        })),
      ])

      showValidationToast(
        failedFiles.length === 1
          ? `${failedFiles[0].file.name} removed — ${failedFiles[0].reason}`
          : `${failedFiles.length} files could not be uploaded`,
      )

      return
    }

    closeUpload()
  }



  async function handleDownload(file: FileItem) {
    try {
      const response = await downloadFile(file.id)
      saveBlob(response, file.original_name)
    } catch (error) {
      console.error('Download failed:', error)
      alert('Unable to download this file.')
    }
  }

  function openEdit(file: FileItem) {
    setEditFile(file)
    setEditName(file.original_name)
  }

  function closeEdit() {
    if (editSaving) return

    setEditFile(null)
    setEditName('')
  }

  async function handleEditSave() {
    if (!editFile) return

    const name = editName.trim()

    if (!name) {
      alert('File name cannot be empty.')
      return
    }

    try {
      setEditSaving(true)

      await updateFile(editFile.id, {
        original_name: name,
      })

      await refetch()
      closeEdit()
    } catch (error) {
      console.error('Update failed:', error)
      alert('Unable to rename the file.')
    } finally {
      setEditSaving(false)
    }
  }

  function openShare(file: FileItem) {
    setShareFileItem(file)
    setShareSearch('')
    setShareUserIds([])
    setSharePermission('read')
  }

  function closeShare() {
    if (shareSaving) return

    setShareFileItem(null)
    setShareSearch('')
    setShareUserIds([])
    setSharePermission('read')
  }

  function toggleShareUser(userId: number) {
    setShareUserIds((current) =>
      current.includes(userId)
        ? current.filter((id) => id !== userId)
        : [...current, userId],
    )
  }

  function getShareErrorMessage(error: unknown): string {
    const responseData = (
      error as {
        response?: {
          data?: {
            message?: string
            error?: string
          }
        }
      }
    )?.response?.data

    return (
      responseData?.message ||
      responseData?.error ||
      (error instanceof Error ? error.message : 'Unable to share the file.')
    )
  }

  async function handleShareSave() {
    if (shareSaving || !shareFileItem || shareUserIds.length === 0) {
      return
    }

    setShareSaving(true)

    let successful = 0
    let failed = 0
    const errors: string[] = []

    try {
      for (const userId of shareUserIds) {
        try {
          await shareFile(
            shareFileItem.id,
            userId,
            sharePermission,
          )
          successful += 1
        } catch (error) {
          failed += 1
          errors.push(getShareErrorMessage(error))
          console.error(
            `Share failed for file ${shareFileItem.id} and user ${userId}:`,
            error,
          )
        }
      }

      if (successful > 0 && failed === 0) {
        alert(
          `File "${shareFileItem.original_name}" was shared successfully with ${successful} user${successful === 1 ? '' : 's'}.`,
        )

        closeShare()
        await refetch()
        return
      }

      if (successful > 0 && failed > 0) {
        alert(
          `${successful} user${successful === 1 ? '' : 's'} shared successfully, but ${failed} failed.\n\nError: ${errors[0]}`,
        )
        await refetch()
        return
      }

      alert(
        `Sharing failed.\n\n${errors[0] || 'Unable to share the file.'}`,
      )
    } finally {
      setShareSaving(false)
    }
  }

  function openMove(file: FileItem) {
    setMoveFileItem(file)
    setMoveFolderId(file.folder_id)
  }

  function closeMove() {
    if (moveSaving) return

    setMoveFileItem(null)
    setMoveFolderId(null)
  }

  function renderMoveFolder(folder: (typeof folders)[number], level = 0): React.ReactNode {
    const children = folders.filter(
      (child) => child.parent_id === folder.id,
    )

    const selected = moveFolderId === folder.id

    return (
      <div key={folder.id}>
        <button
          type="button"
          className={`move-folder-option ${selected ? 'selected' : ''}`}
          style={{ paddingLeft: `${12 + level * 20}px` }}
          onClick={() => setMoveFolderId(folder.id)}
          disabled={moveSaving}
        >
          <Folder size={16} />
          <span>{folder.name}</span>
          {selected && <Check size={16} />}
        </button>

        {children.map((child) => renderMoveFolder(child, level + 1))}
      </div>
    )
  }

  async function handleMoveSave() {
    if (!moveFileItem || moveFolderId === null || moveSaving) return

    if (moveFolderId === moveFileItem.folder_id) {
      window.alert('The file is already in this folder.')
      return
    }

    setMoveSaving(true)

    try {
      await moveFile(moveFileItem.id, moveFolderId)
      await refetch()
      closeMove()
      window.alert('File moved successfully.')
    } catch (error) {
      console.error('Move failed:', error)
      window.alert('Unable to move the file.')
    } finally {
      setMoveSaving(false)
    }
  }

  async function handleDelete(file: FileItem) {
    const confirmed = window.confirm(
      `Are you sure you want to delete "${file.original_name}"?`,
    )

    if (!confirmed) return

    try {
      await deleteFile(file.id)
      await refetch()
    } catch (error) {
      console.error('Failed to delete file:', error)
      window.alert('Unable to delete the file. Please try again.')
    }
  }

  if (isLoading) {
    return (
      <div className="file-browser-state">
        <RefreshCw className="spin" size={20} />
        <span>Loading files...</span>
      </div>
    )
  }

  if (isError) {
    return (
      <div className="file-browser-state error">
        <FileWarning size={20} />
        <span>Unable to load files.</span>

        <button className="secondary-button" onClick={() => refetch()}>
          Try again
        </button>
      </div>
    )
  }

  function canManageFileActions(file: FileItem): boolean {
    if (isAuditor) {
      return false
    }

    if (isGlobal || isDepartmentHead) {
      return true
    }

    return user?.id === file.uploader_id
  }

  function canShareFile(file: FileItem): boolean {
    if (isAuditor) {
      return false
    }

    if (isGlobal || isDepartmentHead) {
      return true
    }

    return user?.id === file.uploader_id
  }

  return (
    <section className="file-browser">
      <div className="files-page-header">
        <div>
          <div className="eyebrow">Documents</div>
          <h1>My Files</h1>
          <p>Manage your documents and media.</p>
        </div>

        <div
              className="upload-button-wrapper"
              onMouseEnter={() => {
                if (
                  selectedFiles.length > 0 &&
                  selectedFolderId === null
                ) {
                  setFolderHint(true)
                }
              }}
              onMouseLeave={() => setFolderHint(false)}
            >
              {selectedFiles.length > 0 &&
                selectedFolderId === null &&
                folderHint && (
                  <div className="upload-folder-tooltip">
                    Choose a destination folder first
                  </div>
                )}

              <div className="files-page-actions">
        {searchOpen && (
          <input
            type="search"
            className="files-search-input"
            placeholder="Search files..."
            value={searchQuery}
            onChange={(event) => {
              setSearchQuery(event.target.value)
              setCurrentPage(1)
            }}
            autoFocus
          />
        )}

        <button
          className="secondary-button"
          type="button"
          onClick={() => {
            setSearchOpen((current) => !current)

            if (searchOpen) {
              setSearchQuery('')
              setCurrentPage(1)
            }
          }}
          aria-label="Search files"
        >
          <Search size={17} />
          Search
        </button>

        {canUpload && (
          <button
            className="primary-button"
            type="button"
            onClick={openUpload}
          >
            <Upload size={17} />
            Upload
          </button>
        )}
      </div>
      </div>

      </div>
      <div className="files-layout">
        <FolderTree
          folders={folders}
          selectedFolderId={selectedFolderId}
          onSelect={setSelectedFolderId}
        />

        <div className="files-main">
          <Breadcrumbs
            folders={folders}
            selectedFolderId={selectedFolderId}
            onSelect={setSelectedFolderId}
          />

          {foldersLoading && (
            <div className="file-browser-state">
              Loading folders...
            </div>
          )}

          {foldersError && (
            <div className="file-browser-state error">
              Unable to load folders.
            </div>
          )}

          <div className="file-browser-toolbar">
            <div>
              <h2>{currentFolder?.name ?? 'All Files'}</h2>
              <p>{displayedFiles.length} files available to you</p>
            </div>

            <div className="view-toggle">
              <button
                className={view === 'list' ? 'active' : ''}
                onClick={() => setView('list')}
                title="List view"
                type="button"
              >
                <List size={17} />
              </button>

              <button
                className={view === 'grid' ? 'active' : ''}
                onClick={() => setView('grid')}
                title="Grid view"
                type="button"
              >
                <Grid2X2 size={17} />
              </button>
            </div>
          </div>

          {displayedFiles.length === 0 ? (
            <div className="empty-files">
              <div className="empty-files-icon">
                <Upload size={22} />
              </div>

              <h3>No files yet</h3>
              <p>Upload a document or media file to get started.</p>

              {canUpload && (
                <button
                  className="secondary-button"
                  type="button"
                  onClick={openUpload}
                >
                  Upload a file
                </button>
              )}
            </div>
          ) : view === 'list' ? (
            <div className="file-table">
              <div className="file-table-header">
                <span>Name</span>
                <span>Size</span>
                <span>Uploaded</span>
                <span />
              </div>

              {paginatedFiles.map((file) => (
                <FileRow
                  key={file.id}
                  file={file}
                  onDownload={handleDownload}
                  onEdit={openEdit}
                  onShare={openShare}
                  onDelete={handleDelete}
                  onMove={openMove}
                  canManage={canManageFileActions(file)}
                  canShare={canShareFile(file)}
                />
              ))}
            </div>
          ) : (
            <div className="file-grid">
              {paginatedFiles.map((file) => (
                <button
                  className="file-card"
                  key={file.id}
                  onClick={() => handleDownload(file)}
                  type="button"
                >
                  <div className="file-card-icon">
                    <Download size={20} />
                  </div>

                  <strong>{file.original_name}</strong>
                  <span>{file.mime_type}</span>
                </button>
              ))}
            </div>
          )}
        </div>
      </div>

      {editFile && (
        <div
          className="modal-backdrop"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) closeEdit()
          }}
        >
          <div className="details-modal">
            <div className="details-modal-header">
              <div>
                <div className="eyebrow">File</div>
                <h2>Edit file name</h2>
                <p>Change the name shown in your workspace.</p>
              </div>

              <button
                className="icon-button"
                type="button"
                onClick={closeEdit}
                disabled={editSaving}
                aria-label="Close"
              >
                <X size={19} />
              </button>
            </div>

            <div className="details-modal-body">
              <label htmlFor="edit-file-name">File name</label>

              <input
                id="edit-file-name"
                className="form-input"
                value={editName}
                onChange={(event) => setEditName(event.target.value)}
                autoFocus
                disabled={editSaving}
                onKeyDown={(event) => {
                  if (event.key === 'Enter') {
                    void handleEditSave()
                  }
                }}
              />
            </div>

            <div className="details-modal-footer">
              <button
                className="secondary-button"
                type="button"
                onClick={closeEdit}
                disabled={editSaving}
              >
                Cancel
              </button>

              <button
                className="primary-button"
                type="button"
                onClick={() => void handleEditSave()}
                disabled={!editName.trim() || editSaving}
              >
                {editSaving ? 'Saving...' : 'Save changes'}
              </button>
            </div>
          </div>
        </div>
      )}

      {shareFileItem && (
        <div
          className="modal-backdrop"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) closeShare()
          }}
        >
          <div className="details-modal share-modal">
            <div className="details-modal-header">
              <div>
                <div className="eyebrow">Sharing</div>
                <h2>Share file</h2>
                <p>{shareFileItem.original_name}</p>
              </div>

              <button
                className="icon-button"
                type="button"
                onClick={closeShare}
                disabled={shareSaving}
                aria-label="Close"
              >
                <X size={19} />
              </button>
            </div>

            <div className="details-modal-body">
              <label htmlFor="share-user-search">Share with</label>

              <div className="share-search">
                <Search size={16} />
                <input
                  id="share-user-search"
                  value={shareSearch}
                  onChange={(event) => {
                    setShareSearch(event.target.value)
                  }}
                  placeholder="Search by name or email..."
                  autoFocus
                  disabled={shareSaving}
                />
              </div>

              <div className="share-user-list">
                {usersLoading && (
                  <div className="share-state">Loading users...</div>
                )}

                {usersError && (
                  <div className="share-state error">
                    Unable to load users.
                  </div>
                )}

                {!usersLoading &&
                  !usersError &&
                  users
                    .filter(
                      (candidate) =>
                        Number(candidate.id) !==
                          Number(currentUserId) &&
                        candidate.email.toLowerCase() !==
                          (user?.email ?? '').toLowerCase(),
                    )
                    .filter((candidate) => {
                      const query = shareSearch.trim().toLowerCase()

                      if (!query) return true

                      return (
                        candidate.name.toLowerCase().includes(query) ||
                        candidate.email.toLowerCase().includes(query) ||
                        candidate.username.toLowerCase().includes(query)
                      )
                    })
                    .slice(0, 8)
                    .map((user) => (
                      <button
                        key={user.id}
                        type="button"
                        className={`share-user-option ${
                          shareUserIds.includes(user.id) ? 'selected' : ''
                        }`}
                        onClick={() => toggleShareUser(user.id)}
                        disabled={shareSaving}
                      >
                        <div className="share-user-avatar">
                          {user.name.charAt(0).toUpperCase()}
                        </div>

                        <div className="share-user-info">
                          <strong>{user.name}</strong>
                          <span>{user.role?.name || 'No role assigned'}</span>
                          <small>{user.email}</small>
                        </div>

                        {shareUserIds.includes(user.id) && (
                          <span className="share-selected">Selected</span>
                        )}
                      </button>
                    ))}

                {!usersLoading &&
                  !usersError &&
                  users
                    .filter((candidate) => {
                      const query = shareSearch.trim().toLowerCase()

                      if (!query) return true

                      return (
                        candidate.name.toLowerCase().includes(query) ||
                        candidate.email.toLowerCase().includes(query) ||
                        candidate.username.toLowerCase().includes(query)
                      )
                    }).length === 0 && (
                    <div className="share-state">
                      No users found.
                    </div>
                  )}
              </div>

              <label className="share-permission-label">
                Permission
              </label>

              <div className="permission-options">
                <button
                  type="button"
                  className={sharePermission === 'read' ? 'active' : ''}
                  onClick={() => setSharePermission('read')}
                  disabled={shareSaving}
                >
                  <strong>Can view</strong>
                  <span>Read-only access</span>
                </button>

                <button
                  type="button"
                  className={sharePermission === 'write' ? 'active' : ''}
                  onClick={() => setSharePermission('write')}
                  disabled={shareSaving}
                >
                  <strong>Can edit</strong>
                  <span>View and modify</span>
                </button>
              </div>
            </div>

            <div className="details-modal-footer">
              <button
                className="secondary-button"
                type="button"
                onClick={closeShare}
                disabled={shareSaving}
              >
                Cancel
              </button>

              <button
                className="primary-button"
                type="button"
                onClick={() => void handleShareSave()}
                disabled={shareUserIds.length === 0 || shareSaving}
              >
                {shareSaving ? 'Sharing...' : 'Share file'}
              </button>
            </div>
          </div>
        </div>
      )}

      {moveFileItem && (
        <div
          className="modal-backdrop"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) closeMove()
          }}
        >
          <div className="details-modal move-modal">
            <div className="details-modal-header">
              <div>
                <div className="eyebrow">File location</div>
                <h2>Move file</h2>
                <p>{moveFileItem.original_name}</p>
              </div>

              <button
                className="icon-button"
                type="button"
                onClick={closeMove}
                disabled={moveSaving}
                aria-label="Close"
              >
                <X size={19} />
              </button>
            </div>

            <div className="details-modal-body">
              <label>Choose destination</label>

              <div className="move-folder-list">
                {folders.length === 0 ? (
                  <div className="share-state">
                    No folders available.
                  </div>
                ) : (
                  folders
                    .filter((folder) => folder.parent_id === null)
                    .map((folder) => renderMoveFolder(folder))
                )}
              </div>
            </div>

            <div className="details-modal-footer">
              <button
                className="secondary-button"
                type="button"
                onClick={closeMove}
                disabled={moveSaving}
              >
                Cancel
              </button>

              <button
                className="primary-button"
                type="button"
                onClick={() => void handleMoveSave()}
                disabled={
                  moveFolderId === null ||
                  moveFolderId === moveFileItem.folder_id ||
                  moveSaving
                }
              >
                {moveSaving ? 'Moving...' : 'Move here'}
              </button>
            </div>
          </div>
        </div>
      )}

      {files.length > 0 && totalFilePages > 1 && (
        <div className="file-pagination">
          <div className="file-pagination-info">
            Showing{' '}
            <strong>
              {(currentPage - 1) * FILES_PER_PAGE + 1}
            </strong>
            {'–'}
            <strong>
              {Math.min(
                currentPage * FILES_PER_PAGE,
                files.length,
              )}
            </strong>
            {' of '}
            <strong>{files.length}</strong>
            {' files'}
          </div>

          <div className="file-pagination-controls">
            <button
              type="button"
              className="pagination-button"
              onClick={() =>
                setCurrentPage((page) => Math.max(1, page - 1))
              }
              disabled={currentPage === 1}
            >
              Previous
            </button>

            <div className="pagination-pages">
              {Array.from(
                { length: totalFilePages },
                (_, index) => index + 1,
              ).map((page) => (
                <button
                  type="button"
                  key={page}
                  className={`pagination-page ${
                    page === currentPage ? 'active' : ''
                  }`}
                  onClick={() => setCurrentPage(page)}
                >
                  {page}
                </button>
              ))}
            </div>

            <button
              type="button"
              className="pagination-button"
              onClick={() =>
                setCurrentPage((page) =>
                  Math.min(totalFilePages, page + 1),
                )
              }
              disabled={currentPage === totalFilePages}
            >
              Next
            </button>
          </div>
        </div>
      )}

      {uploadOpen && (
        <div
          className="upload-modal-backdrop"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) closeUpload()
          }}
        >
          <div className="upload-modal">
            <div className="upload-modal-header">
              <div>
                <h2>Upload file</h2>
                <p>
                  Choose the destination folder for your files.
                </p>
              </div>

              <button
                className="icon-button"
                type="button"
                onClick={closeUpload}
                disabled={uploadMutation.isPending}
                aria-label="Close"
              >
                <X size={19} />
              </button>
            </div>

                        <input
              ref={fileInputRef}
              type="file"
              hidden
              multiple
              accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.md,.csv,.jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.avi,.mkv,.zip,.rar,.7z"
              onChange={handleFileInput}
            />

            <div className="upload-destination">
              <label htmlFor="upload-folder-select">
                Upload to
              </label>

              <select
                id="upload-folder-select"
                className={`upload-folder-select ${folderHint ? "folder-validation-error" : ""}`}
                value={selectedFolderId ?? ''}
                onChange={(event) => {
                  const value = event.target.value
                  setSelectedFolderId(value ? Number(value) : null)
                }}
                disabled={uploadMutation.isPending || foldersLoading}
              >
                <option value="">
                  {foldersLoading
                    ? 'Loading folders...'
                    : 'Select a destination folder'}
                </option>

                {folders.map((folder) => {
                  let depth = 0
                  let parentId = folder.parent_id

                  while (parentId !== null) {
                    depth += 1
                    const parent = folders.find(
                      (item) => item.id === parentId,
                    )
                    parentId = parent?.parent_id ?? null

                    if (!parent) break
                  }

                  return (
                    <option value={folder.id} key={folder.id}>
                      {`${'— '.repeat(depth)}${folder.name}`}
                    </option>
                  )
                })}
              </select>
            </div>

            <div className="upload-rules">
              <strong>Upload requirements</strong>
              <span>Maximum 50 MB per file</span>
              <span>Multiple files allowed</span>
              <span>
                PDF, Word, Excel, PowerPoint, images, video,
                text and ZIP/RAR/7z archives
              </span>
            </div>

            {rejectedFiles.length > 0 && (
              <div className="upload-rejected-files">
                <div className="upload-rejected-header">
                  <div>
                    <strong>
                      {rejectedFiles.length}{' '}
                      {rejectedFiles.length === 1
                        ? 'file'
                        : 'files'} rejected
                    </strong>
                    <span>
                      These files will not be uploaded.
                    </span>
                  </div>
                </div>

                <div className="upload-rejected-list">
                  {rejectedFiles.map((item, index) => (
                    <div
                      className="upload-rejected-item"
                      key={`${item.name}-${item.reason}-${index}`}
                    >
                      <div className="upload-rejected-icon">
                        <FileWarning size={16} />
                      </div>

                      <div className="upload-rejected-details">
                        <strong title={item.name}>
                          {item.name}
                        </strong>
                        <span>{item.reason}</span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {selectedFiles.length === 0 ? (
              <div
                className={`upload-dropzone ${
                  dragActive ? 'drag-active' : ''
                }`}
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onDrop={handleDrop}
                onClick={() => fileInputRef.current?.click()}
              >
                <div className="upload-dropzone-icon">
                  <Upload size={24} />
                </div>

                <h3>Drag and drop files here</h3>
                <p>or click to browse from your computer</p>

                <span className="upload-supported">
                  You can select multiple files at once
                </span>
              </div>
            ) : (
              <div className="upload-file-list">
                <div className="upload-file-list-header">
                  <div>
                    <strong>
                      {selectedFiles.length}{' '}
                      {selectedFiles.length === 1 ? 'file' : 'files'} selected
                    </strong>
                    <span>Ready to upload</span>
                  </div>

                  <button
                    type="button"
                    className="upload-add-more"
                    onClick={() => fileInputRef.current?.click()}
                    disabled={uploadMutation.isPending}
                  >
                    <Plus size={15} />
                    Add more
                  </button>
                </div>

                <div className="upload-file-list-scroll">
                  {selectedFiles.map((file, index) => (
                    <div className="upload-selected-file" key={`${file.name}-${file.size}-${file.lastModified}`}>
                      <div className="upload-file-icon">
                        <FileIcon size={20} />
                      </div>

                      <div className="upload-file-details">
                        <strong title={file.name}>{file.name}</strong>
                        <span>{formatFileSize(file.size)}</span>
                      </div>

                      <button
                        className="icon-button"
                        type="button"
                        onClick={() => removeSelectedFile(index)}
                        disabled={uploadMutation.isPending}
                        aria-label={`Remove ${file.name}`}
                      >
                        <X size={16} />
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {validationToast && (
              <div
                className="upload-validation-toast"
                role="status"
                aria-live="polite"
              >
                <FileWarning size={17} />
                <span>{validationToast}</span>
              </div>
            )}

            {selectedFolderId === null && (
              <div className="upload-warning">
                Choose a destination folder before uploading.
              </div>
            )}

            <div className="upload-modal-footer">
              <button
                className="secondary-button"
                type="button"
                onClick={closeUpload}
                disabled={uploadMutation.isPending}
              >
                Cancel
              </button>

              <button
                className="primary-button"
                type="button"
                onClick={handleUpload}
                disabled={
                  selectedFiles.length === 0 ||
                  selectedFolderId === null ||
                  validationChecking ||
                  uploadMutation.isPending
                }
              >
                {validationChecking ? (
                  <>
                    <RefreshCw className="spin" size={16} />
                    Checking files...
                  </>
                ) : uploadMutation.isPending ? (
                  <>
                    <RefreshCw className="spin" size={16} />
                    Uploading...
                  </>
                ) : (
                  <>
                    <Upload size={16} />
                    Upload {selectedFiles.length}{' '}
                    {selectedFiles.length === 1 ? 'file' : 'files'}
                  </>
                )}
              </button>
            </div>
            </div>
        </div>
      )}
    </section>
  )
}
