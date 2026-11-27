import { describe, expect, it } from 'vitest'
import { safeUrl } from '../safeUrl'
import { formatAuthor, formatAuthors, formatDate } from '../format'

describe('safeUrl', () => {
  it('accepte les URL http et https', () => {
    expect(safeUrl('https://www.gutenberg.org/cover.jpg')).toBe('https://www.gutenberg.org/cover.jpg')
    expect(safeUrl('http://example.com/a.jpg')).toBe('http://example.com/a.jpg')
  })

  it('refuse les autres protocoles et les valeurs invalides', () => {
    expect(safeUrl('javascript:alert(1)')).toBeUndefined()
    expect(safeUrl('data:text/html,<script>alert(1)</script>')).toBeUndefined()
    expect(safeUrl('pas une url')).toBeUndefined()
    expect(safeUrl(null)).toBeUndefined()
  })
})

describe('format', () => {
  it('remet les auteurs dans l’ordre prénom nom', () => {
    expect(formatAuthor('Austen, Jane')).toBe('Jane Austen')
    expect(formatAuthor('Homer')).toBe('Homer')
    expect(formatAuthors([])).toBe('Auteur inconnu')
  })

  it('formate une date au format français', () => {
    expect(formatDate('2026-11-26')).toBe('26 novembre 2026')
    expect(formatDate(null)).toBe('')
  })
})
