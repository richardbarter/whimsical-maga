import { afterAll, beforeAll, describe, expect, it, vi } from 'vitest'
import { formatDate } from '@/lib/utils'

describe('formatDate', () => {
    // Most visitors are in the Americas, where a UTC-midnight date used to render a day early.
    beforeAll(() => {
        vi.stubEnv('TZ', 'America/New_York')
    })

    afterAll(() => {
        vi.unstubAllEnvs()
    })

    it('formats a date-only value as that calendar date, whatever the viewer time zone', () => {
        expect(formatDate('2024-01-15')).toBe('Jan 15, 2024')
    })

    it('does not shift the first day of the year back into the previous year', () => {
        expect(formatDate('2025-01-01')).toBe('Jan 1, 2025')
    })

    it('formats a datetime in the viewer local time zone', () => {
        // 03:00 UTC on the 15th is still the evening of the 14th in New York.
        expect(formatDate('2024-01-15T03:00:00.000000Z')).toBe('Jan 14, 2024')
    })
})
