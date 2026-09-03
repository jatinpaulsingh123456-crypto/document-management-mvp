import {
  Mail,
  MapPin,
  Plus,
  Search,
  ShieldCheck,
  UserPlus,
  Users as UsersIcon,
  X,
} from 'lucide-react'
import { useMemo, useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import {
  createUser,
  getRoles,
  getUsers,
  type UserItem,
} from '../services/userService'
import {
  getLocations,
  getDepartmentsByLocation,
  type LocationItem,
  type DepartmentItem,
} from '../services/locationService'
import { getCurrentUser } from '../services/authService'

function Users() {
  const queryClient = useQueryClient()

  const { data: currentUser } = useQuery({
    queryKey: ['current-user'],
    queryFn: getCurrentUser,
    staleTime: 30000,
  })

  const {
    data: users = [],
    isLoading,
    isError,
  } = useQuery({
    queryKey: ['users'],
    queryFn: getUsers,
    refetchInterval: 5000,
    refetchOnWindowFocus: true,
    staleTime: 0,
  })

  const { data: roles = [] } = useQuery({
    queryKey: ['user-roles'],
    queryFn: getRoles,
    staleTime: 300000,
  })

  const { data: locations = [] } = useQuery({
    queryKey: ['locations'],
    queryFn: getLocations,
    staleTime: 30000,
  })

  const canManageUsers = ['CEO', 'MD'].includes(
    (currentUser?.role?.code ?? '').toUpperCase(),
  )

  const [search, setSearch] = useState('')
  const [roleFilter, setRoleFilter] = useState('')
  const [locationFilter, setLocationFilter] = useState('')

  const [modalOpen, setModalOpen] = useState(false)
  const [saving, setSaving] = useState(false)

  const [name, setName] = useState('')
  const [username, setUsername] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [roleId, setRoleId] = useState('')
  const [locationId, setLocationId] = useState('')

  const {
    data: departments = [],
  } = useQuery({
    queryKey: ['departments', locationId],
    queryFn: () =>
      getDepartmentsByLocation(Number(locationId)),
    enabled: Boolean(locationId),
    staleTime: 30000,
  })

  const [departmentId, setDepartmentId] = useState('')

  const filteredUsers = useMemo(() => {
    const query = search.trim().toLowerCase()

    return users.filter((user) => {
      const matchesSearch =
        !query ||
        [
          user.name,
          user.username,
          user.email,
          user.role?.name ?? '',
          user.location?.name ?? '',
          user.department?.name ?? '',
        ].some((value) =>
          value.toLowerCase().includes(query),
        )

      const matchesRole =
        !roleFilter ||
        String(user.role?.id ?? '') === roleFilter

      const matchesLocation =
        !locationFilter ||
        String(user.location?.id ?? '') === locationFilter

      return (
        matchesSearch &&
        matchesRole &&
        matchesLocation
      )
    })
  }, [users, search, roleFilter, locationFilter])

  function resetForm() {
    setName('')
    setUsername('')
    setEmail('')
    setPassword('')
    setRoleId('')
    setLocationId('')
    setDepartmentId('')
  }

  function openCreate() {
    if (!canManageUsers) return

    resetForm()
    setModalOpen(true)
  }

  function closeModal() {
    if (saving) return
    setModalOpen(false)
  }

  async function handleSave() {
    if (
      !name.trim() ||
      !username.trim() ||
      !email.trim() ||
      !password ||
      !roleId
    ) {
      window.alert(
        'Name, username, email, password and role are required.',
      )
      return
    }

    try {
      setSaving(true)

      await createUser({
        name: name.trim(),
        username: username.trim(),
        email: email.trim(),
        password,
        role_id: Number(roleId),
        location_id: locationId
          ? Number(locationId)
          : null,
        department_id: departmentId
          ? Number(departmentId)
          : null,
      })

      await queryClient.invalidateQueries({
        queryKey: ['users'],
      })

      closeModal()
      resetForm()
    } catch (error) {
      console.error(
        'Failed to create user:',
        error,
      )

      window.alert(
        'Unable to create the user.',
      )
    } finally {
      setSaving(false)
    }
  }

  function roleName(user: UserItem) {
    return user.role?.name || 'Unassigned'
  }

  function initials(name: string) {
    return name
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part[0].toUpperCase())
      .join('')
  }

  return (
    <section className="users-page">
      <div className="users-page-header">
        <div>
          <span className="page-eyebrow">
            MANAGEMENT
          </span>

          <h1>Users</h1>

          <p>
            Manage people, roles and organizational assignments.
          </p>
        </div>

        {canManageUsers && (
          <button
            className="primary-button"
            type="button"
            onClick={openCreate}
          >
            <Plus size={16} />
            Add user
          </button>
        )}
      </div>

      <div className="users-toolbar">
        <div className="users-search">
          <Search size={16} />

          <input
            type="search"
            placeholder="Search users..."
            value={search}
            onChange={(event) =>
              setSearch(event.target.value)
            }
          />
        </div>

        <select
          value={roleFilter}
          onChange={(event) =>
            setRoleFilter(event.target.value)
          }
        >
          <option value="">All roles</option>

          {roles.map((role) => (
            <option
              value={role.id}
              key={role.id}
            >
              {role.name}
            </option>
          ))}
        </select>

        <select
          value={locationFilter}
          onChange={(event) =>
            setLocationFilter(event.target.value)
          }
        >
          <option value="">All locations</option>

          {locations.map((location: LocationItem) => (
            <option
              value={location.id}
              key={location.id}
            >
              {location.name}
            </option>
          ))}
        </select>

        <span className="users-count">
          {filteredUsers.length}{' '}
          {filteredUsers.length === 1
            ? 'user'
            : 'users'}
        </span>
      </div>

      <div className="users-card">
        {isLoading ? (
          <div className="users-state">
            Loading users...
          </div>
        ) : isError ? (
          <div className="users-state users-error">
            Unable to load users.
          </div>
        ) : filteredUsers.length === 0 ? (
          <div className="users-empty">
            <div className="users-empty-icon">
              <UsersIcon size={22} />
            </div>

            <h3>
              {search ||
              roleFilter ||
              locationFilter
                ? 'No users found'
                : 'No users yet'}
            </h3>

            <p>
              {search ||
              roleFilter ||
              locationFilter
                ? 'Try changing your filters.'
                : 'Add your first user to get started.'}
            </p>

            {canManageUsers &&
              !search &&
              !roleFilter &&
              !locationFilter && (
                <button
                  className="secondary-button"
                  type="button"
                  onClick={openCreate}
                >
                  <Plus size={15} />
                  Add user
                </button>
              )}
          </div>
        ) : (
          <div className="users-table">
            <div className="users-table-header">
              <span>User</span>
              <span>Role</span>
              <span>Location</span>
              <span>Department</span>
              <span>Status</span>
            </div>

            {filteredUsers.map((user) => (
              <div
                className="user-row"
                key={user.id}
              >
                <div className="user-primary">
                  <div className="user-avatar">
                    {initials(
                      user.name ||
                        user.username,
                    )}
                  </div>

                  <div>
                    <strong>
                      {user.name ||
                        user.username}
                    </strong>

                    <span>
                      <Mail size={11} />
                      {user.email}
                    </span>
                  </div>
                </div>

                <div className="user-role">
                  <ShieldCheck size={14} />
                  {roleName(user)}
                </div>

                <div className="user-meta">
                  <MapPin size={13} />
                  {user.location?.name ||
                    '—'}
                </div>

                <div className="user-meta">
                  {user.department?.name ||
                    '—'}
                </div>

                <span
                  className={`user-status ${
                    user.status === 10
                      ? 'active'
                      : 'inactive'
                  }`}
                >
                  {user.status === 10
                    ? 'Active'
                    : 'Inactive'}
                </span>
              </div>
            ))}
          </div>
        )}
      </div>

      {modalOpen && (
        <div
          className="user-modal-backdrop"
          onMouseDown={(event) => {
            if (
              event.target ===
              event.currentTarget
            ) {
              closeModal()
            }
          }}
        >
          <div className="user-modal">
            <div className="user-modal-header">
              <div>
                <span className="page-eyebrow">
                  MANAGEMENT
                </span>

                <h2>Add user</h2>

                <p>
                  Create an account and assign its
                  organizational scope.
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

            <div className="user-form">
              <label>
                Full name
                <input
                  value={name}
                  onChange={(event) =>
                    setName(event.target.value)
                  }
                  placeholder="e.g. John Smith"
                />
              </label>

              <label>
                Username
                <input
                  value={username}
                  onChange={(event) =>
                    setUsername(event.target.value)
                  }
                  placeholder="e.g. john.smith"
                />
              </label>

              <label>
                Email
                <input
                  type="email"
                  value={email}
                  onChange={(event) =>
                    setEmail(event.target.value)
                  }
                  placeholder="john.smith@example.com"
                />
              </label>

              <label>
                Password
                <input
                  type="password"
                  value={password}
                  onChange={(event) =>
                    setPassword(event.target.value)
                  }
                  placeholder="Create a password"
                />
              </label>

              <label>
                Role
                <select
                  value={roleId}
                  onChange={(event) =>
                    setRoleId(event.target.value)
                  }
                >
                  <option value="">
                    Select role
                  </option>

                  {roles.map((role) => (
                    <option
                      value={role.id}
                      key={role.id}
                    >
                      {role.name}
                    </option>
                  ))}
                </select>
              </label>

              <label>
                Location
                <select
                  value={locationId}
                  onChange={(event) => {
                    setLocationId(
                      event.target.value,
                    )
                    setDepartmentId('')
                  }}
                >
                  <option value="">
                    Select location
                  </option>

                  {locations.map(
                    (location: LocationItem) => (
                      <option
                        value={location.id}
                        key={location.id}
                      >
                        {location.name}
                      </option>
                    ),
                  )}
                </select>
              </label>

              <label>
                Department
                <select
                  value={departmentId}
                  onChange={(event) =>
                    setDepartmentId(
                      event.target.value,
                    )
                  }
                  disabled={!locationId}
                >
                  <option value="">
                    {locationId
                      ? 'Select department'
                      : 'Select location first'}
                  </option>

                  {departments.map(
                    (
                      department: DepartmentItem,
                    ) => (
                      <option
                        value={department.id}
                        key={department.id}
                      >
                        {department.name}
                      </option>
                    ),
                  )}
                </select>
              </label>
            </div>

            <div className="user-modal-footer">
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
                onClick={() =>
                  void handleSave()
                }
                disabled={saving}
              >
                <UserPlus size={16} />
                {saving
                  ? 'Creating...'
                  : 'Create user'}
              </button>
            </div>
          </div>
        </div>
      )}
    </section>
  )
}

export default Users
