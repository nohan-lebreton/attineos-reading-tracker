export function formatAuthor(name: string): string {
  const comma = name.indexOf(', ')
  return comma === -1 ? name : `${name.slice(comma + 2)} ${name.slice(0, comma)}`
}

export function formatAuthors(authors: string[]): string {
  return authors.length > 0 ? authors.map(formatAuthor).join(', ') : 'Auteur inconnu'
}

const dateFormat = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })

export function formatDate(value: string | null): string {
  if (!value) {
    return ''
  }
  const date = new Date(/^\d{4}-\d{2}-\d{2}$/.test(value) ? `${value}T00:00:00` : value)
  return Number.isNaN(date.getTime()) ? '' : dateFormat.format(date)
}

const languageNames = new Intl.DisplayNames(['fr'], { type: 'language' })

export function formatLanguage(code: string): string {
  const name = languageNames.of(code) ?? code
  return name.charAt(0).toUpperCase() + name.slice(1)
}

export const numberFormat = new Intl.NumberFormat('fr-FR')
