export function extractIdFromIri(iri: string | null | undefined): string | null {
  if (!iri) return null
  const match = iri.match(/\/(\d+)$/)
  return match?.[1] ?? null
}

export function toIri(resource: string, id: string | number): string {
  return `/api/${resource}/${id}`
}
