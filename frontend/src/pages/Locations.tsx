import {
  ChevronDown,
  ChevronRight,
  Edit3,
  Globe2,
  MapPin,
  Plus,
  Search,
  Trash2,
  X,
} from 'lucide-react'
import { useMemo, useState } from 'react'
import {
  createLocation,
  deleteLocation,
  getDepartmentsByLocation,
  getLocations,
  updateLocation,
  type DepartmentItem,
  type LocationItem,
} from '../services/locationService'
import { getCurrentUser } from '../services/authService'
import { useQuery, useQueryClient } from '@tanstack/react-query'

function LocationPage() {
  const queryClient = useQueryClient()

  const {
    data: locations = [],
    isLoading,
    isError,
  } = useQuery({
    queryKey: ['locations'],
    queryFn: getLocations,
    refetchInterval: 5000,
    refetchOnWindowFocus: true,
    staleTime: 0,
  })

  const {
    data: currentUser,
    isLoading: userLoading,
  } = useQuery({
    queryKey: ['current-user'],
    queryFn: getCurrentUser,
    staleTime: 30000,
    retry: 1,
  })

  const canManageLocations =
    !userLoading &&
    ['CEO', 'MD'].includes(
      (currentUser?.role?.code ?? '').toUpperCase(),
    )

  const [search, setSearch] = useState('')
  const [expandedId, setExpandedId] = useState<number | null>(null)
  const [departmentMap, setDepartmentMap] = useState<
    Record<number, DepartmentItem[]>
  >({})
  const [departmentLoadingId, setDepartmentLoadingId] =
    useState<number | null>(null)

  const [modalOpen, setModalOpen] = useState(false)
  const [editingLocation, setEditingLocation] =
    useState<LocationItem | null>(null)

  const [name, setName] = useState('')
  const [code, setCode] = useState('')
  const [country, setCountry] = useState('')
  const [saving, setSaving] = useState(false)

  const filteredLocations = useMemo(() => {
    const query = search.trim().toLowerCase()

    if (!query) return locations

    return locations.filter((location) =>
      [
        location.name,
        location.code,
        location.country,
      ].some((value) =>
        value.toLowerCase().includes(query),
      ),
    )
  }, [locations, search])

  async function toggleLocation(location: LocationItem) {
    if (expandedId === location.id) {
      setExpandedId(null)
      return
    }

    setExpandedId(location.id)

    if (departmentMap[location.id]) return

    try {
      setDepartmentLoadingId(location.id)

      const departments =
        await getDepartmentsByLocation(location.id)

      setDepartmentMap((current) => ({
        ...current,
        [location.id]: departments,
      }))
    } catch (error) {
      console.error(
        'Failed to load departments:',
        error,
      )
      setDepartmentMap((current) => ({
        ...current,
        [location.id]: [],
      }))
    } finally {
      setDepartmentLoadingId(null)
    }
  }

  function openCreate() {
    if (!canManageLocations) return

    setEditingLocation(null)
    setName('')
    setCode('')
    setCountry('')
    setModalOpen(true)
  }

  function openEdit(location: LocationItem) {
    if (!canManageLocations) return

    setEditingLocation(location)
    setName(location.name)
    setCode(location.code)
    setCountry(location.country)
    setModalOpen(true)
  }

  function closeModal() {
    if (saving) return
    setModalOpen(false)
    setEditingLocation(null)
  }

  async function handleSave() {
    const trimmedName = name.trim()
    const trimmedCode = code.trim().toUpperCase()
    const trimmedCountry = country.trim()

    if (!trimmedName || !trimmedCode || !trimmedCountry) {
      window.alert(
        'Name, code and country are required.',
      )
      return
    }

    try {
      setSaving(true)

      if (editingLocation) {
        await updateLocation(editingLocation.id, {
          name: trimmedName,
          code: trimmedCode,
          country: trimmedCountry,
        })
      } else {
        await createLocation({
          name: trimmedName,
          code: trimmedCode,
          country: trimmedCountry,
        })
      }

      await queryClient.invalidateQueries({
        queryKey: ['locations'],
      })

      setModalOpen(false)
      setEditingLocation(null)
    } catch (error) {
      console.error(
        'Failed to save location:',
        error,
      )

      window.alert(
        'Unable to save the location.',
      )
    } finally {
      setSaving(false)
    }
  }

  async function handleDelete(location: LocationItem) {
    if (!canManageLocations) return

    const confirmed = window.confirm(
      `Delete "${location.name}"?`,
    )

    if (!confirmed) return

    try {
      await deleteLocation(location.id)

      setExpandedId((current) =>
        current === location.id ? null : current,
      )

      await queryClient.invalidateQueries({
        queryKey: ['locations'],
      })
    } catch (error) {
      console.error(
        'Failed to delete location:',
        error,
      )

      window.alert(
        'Unable to delete this location.',
      )
    }
  }

  return (
    <section className="locations-page">
      <div className="locations-page-header">
        <div>
          <span className="page-eyebrow">
            WORKSPACE
          </span>
          <h1>Locations</h1>
          <p>
            Manage organizational locations and their
            departments.
          </p>
        </div>

        {canManageLocations && (
          <button
            className="primary-button"
            type="button"
            onClick={openCreate}
          >
            <Plus size={16} />
            Add location
          </button>
        )}
      </div>

      <div className="locations-toolbar">
        <div className="locations-search">
          <Search size={16} />
          <input
            type="search"
            value={search}
            onChange={(event) =>
              setSearch(event.target.value)
            }
            placeholder="Search locations..."
          />
        </div>

        <span className="locations-count">
          {filteredLocations.length}{' '}
          {filteredLocations.length === 1
            ? 'location'
            : 'locations'}
        </span>
      </div>

      <div className="locations-card">
        {isLoading ? (
          <div className="locations-state">
            Loading locations...
          </div>
        ) : isError ? (
          <div className="locations-state locations-error">
            Unable to load locations.
          </div>
        ) : filteredLocations.length === 0 ? (
          <div className="locations-empty">
            <div className="locations-empty-icon">
              <MapPin size={22} />
            </div>

            <h3>
              {search
                ? 'No locations found'
                : 'No locations yet'}
            </h3>

            <p>
              {search
                ? 'Try a different search.'
                : 'Create your first location to get started.'}
            </p>

            {!search && (
              <button
                className="secondary-button"
                type="button"
                onClick={openCreate}
              >
                <Plus size={15} />
                Add location
              </button>
            )}
          </div>
        ) : (
          <div className="location-list">
            {filteredLocations.map((location) => {
              const expanded =
                expandedId === location.id

              const departments =
                departmentMap[location.id] ?? []

              return (
                <div
                  className={`location-item ${
                    expanded ? 'expanded' : ''
                  }`}
                  key={location.id}
                >
                  <button
                    className="location-main"
                    type="button"
                    onClick={() =>
                      void toggleLocation(location)
                    }
                  >
                    <span className="location-expand">
                      {expanded ? (
                        <ChevronDown size={17} />
                      ) : (
                        <ChevronRight size={17} />
                      )}
                    </span>

                    <span className="location-icon">
                      <Globe2 size={19} />
                    </span>

                    <span className="location-info">
                      <strong>{location.name}</strong>
                      <span>
                        {location.code} ·{' '}
                        {location.country}
                      </span>
                    </span>

                    <span className="location-status">
                      Active
                    </span>

                    {canManageLocations && (
                      <span className="location-actions">
                        <button
                          className="icon-button"
                          type="button"
                          aria-label={`Edit ${location.name}`}
                          onClick={(event) => {
                            event.stopPropagation()
                            openEdit(location)
                          }}
                        >
                          <Edit3 size={15} />
                        </button>

                        <button
                          className="icon-button location-delete-button"
                          type="button"
                          aria-label={`Delete ${location.name}`}
                          onClick={(event) => {
                            event.stopPropagation()
                            void handleDelete(location)
                          }}
                        >
                          <Trash2 size={15} />
                        </button>
                      </span>
                    )}
                  </button>

                  {expanded && (
                    <div className="location-details">
                      {departmentLoadingId ===
                      location.id ? (
                        <div className="location-departments-state">
                          Loading departments...
                        </div>
                      ) : departments.length === 0 ? (
                        <div className="location-departments-state">
                          No departments in this location.
                        </div>
                      ) : (
                        departments.map(
                          (department) => (
                            <div
                              className="location-department"
                              key={department.id}
                            >
                              <div className="department-icon">
                                <MapPin size={14} />
                              </div>

                              <div>
                                <strong>
                                  {department.name}
                                </strong>
                                <span>
                                  {department.code}
                                </span>
                              </div>
                            </div>
                          ),
                        )
                      )}
                    </div>
                  )}
                </div>
              )
            })}
          </div>
        )}
      </div>

      {modalOpen && (
        <div
          className="location-modal-backdrop"
          onMouseDown={(event) => {
            if (
              event.target === event.currentTarget
            ) {
              closeModal()
            }
          }}
        >
          <div className="location-modal">
            <div className="location-modal-header">
              <div>
                <span className="page-eyebrow">
                  ORGANIZATION
                </span>
                <h2>
                  {editingLocation
                    ? 'Edit location'
                    : 'Add location'}
                </h2>
                <p>
                  {editingLocation
                    ? 'Update the location details.'
                    : 'Create a new organizational location.'}
                </p>
              </div>

              <button
                className="icon-button"
                type="button"
                onClick={closeModal}
                disabled={saving}
                aria-label="Close"
              >
                <X size={18} />
              </button>
            </div>

            <div className="location-form">
              <label>
                Location name
                <input
                  value={name}
                  onChange={(event) =>
                    setName(event.target.value)
                  }
                  placeholder="e.g. Leeds Office"
                />
              </label>

              <label>
                Location code
                <input
                  value={code}
                  onChange={(event) =>
                    setCode(event.target.value)
                  }
                  placeholder="e.g. LEE"
                  maxLength={20}
                />
              </label>

              <label>
                Country
                <input
                  value={country}
                  onChange={(event) =>
                    setCountry(event.target.value)
                  }
                  placeholder="e.g. United Kingdom"
                />
              </label>
            </div>

            <div className="location-modal-footer">
              <button
                className="secondary-button"
                type="button"
                onClick={closeModal}
                disabled={saving}
              >
                Cancel
              </button>

              <button
                className="primary-button"
                type="button"
                onClick={() => void handleSave()}
                disabled={saving}
              >
                {saving
                  ? 'Saving...'
                  : editingLocation
                    ? 'Save changes'
                    : 'Create location'}
              </button>
            </div>
          </div>
        </div>
      )}
    </section>
  )
}

export default LocationPage
