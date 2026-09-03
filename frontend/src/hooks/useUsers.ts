import { useQuery } from '@tanstack/react-query'
import { getShareRecipients, getUsers } from '../services/userService'

export function useUsers() {
  return useQuery({
    queryKey: ['users'],
    queryFn: getUsers,
  })
}

export function useShareRecipients(fileId: number | null) {
  return useQuery({
    queryKey: ['share-recipients', fileId],
    queryFn: () => getShareRecipients(fileId as number),
    enabled: fileId !== null,
  })
}
