export interface ApiResponse<T> {
  data: T
  meta?: {
    total: number
    page: number
    itemsPerPage: number
  }
}

export interface HydraCollection<T> {
  member?: T[]
  totalItems?: number
  'hydra:member'?: T[]
  'hydra:totalItems'?: number
  'hydra:view'?: {
    '@id': string
    'hydra:first'?: string
    'hydra:last'?: string
    'hydra:previous'?: string
    'hydra:next'?: string
  }
}

export interface ApiError {
  message: string
  code?: string
  statusCode?: number
  violations?: Array<{
    propertyPath: string
    message: string
  }>
}

// Alias for HydraCollection for easier usage
export type PaginatedResponse<T> = HydraCollection<T>
