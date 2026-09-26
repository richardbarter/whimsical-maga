import { test, expect } from '@playwright/test'
import { loginAsAdmin } from './support/auth'

/**
 * Requires the app running on http://localhost:8000 (docker compose up)
 * and a seeded admin user — see ./support/auth.ts for credentials.
 *
 * Run with: npm run test:e2e
 */

test.describe('Admin Quotes', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsAdmin(page)
    })

    test('admin can view the quotes index page', async ({ page }) => {
        await page.goto('/admin/quotes')

        await expect(page.getByRole('heading', { name: 'Quotes' })).toBeVisible()
        await expect(page.getByRole('link', { name: 'Add Quote' })).toBeVisible()

        // Table headers are present
        await expect(page.getByRole('columnheader', { name: 'Quote' })).toBeVisible()
        await expect(page.getByRole('columnheader', { name: 'Speaker' })).toBeVisible()
        await expect(page.getByRole('columnheader', { name: 'Status' })).toBeVisible()
    })
})
