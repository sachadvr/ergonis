export const stripHtml = (value?: string | null) => {
  if (!value) return ''

  if (typeof window === 'undefined') {
    return value.replace(/<[^>]*>/g, ' ')
  }

  const parser = new DOMParser()
  const doc = parser.parseFromString(value, 'text/html')
  return (doc.body.textContent || '').replace(/\s+/g, ' ').trim()
}
