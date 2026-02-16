/**
 * Transforms API Platform JSON-LD responses to simple objects
 */
export function transformJsonLd<T>(data: any): T {
  if (Array.isArray(data)) {
    return data.map((item) => transformJsonLd(item)) as T
  }

  if (data && typeof data === 'object') {
    const transformed: any = {}

    // Handle collection payloads
    if (data.member) {
      return transformJsonLd(data.member) as T
    }

    if (data['hydra:member']) {
      return transformJsonLd(data['hydra:member']) as T
    }

    // Remove @context, @id, @type
    Object.keys(data).forEach((key) => {
      if (key.startsWith('@') || key.startsWith('hydra:')) {
        return
      }
      transformed[key] = transformJsonLd(data[key])
    })

    // Extract ID from IRI if present
    if (data['@id']) {
      const match = data['@id'].match(/\/(\d+)$/)
      if (match) {
        transformed.id = parseInt(match[1], 10)
      }
    }

    return transformed
  }

  return data
}

/**
 * Extracts ID from IRI (e.g., "/api/applications/123" => "123")
 */
export function extractIdFromIri(iri: string | null | undefined): string | null {
  if (!iri) return null
  const match = iri.match(/\/(\d+)$/)
  return match?.[1] ?? null
}

/**
 * Converts ID to IRI (e.g., "123" => "/api/applications/123")
 */
export function toIri(resource: string, id: string | number): string {
  return `/api/${resource}/${id}`
}
