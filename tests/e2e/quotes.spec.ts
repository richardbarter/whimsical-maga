import { test, expect } from '@playwright/test'

/**
 * Requires a running Laravel dev server on http://localhost:8000
 * and a seeded admin user. Credentials are read from environment variables:
 *
 *   ADMIN_EMAIL    (defaults to admin@example.com)
 *   ADMIN_PASSWORD (required — set via .env or shell before running)
 *
 * Run with: npm run test:e2e
 */

const ADMIN_EMAIL = process.env.ADMIN_EMAIL ?? 'admin@example.com'
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD ?? ''

async function loginAsAdmin(page: import('@playwright/test').Page) {
    await page.goto('/login')
    await page.getByLabel('Email').fill(ADMIN_EMAIL)
    await page.getByLabel('Password').fill(ADMIN_PASSWORD)
    await page.getByRole('button', { name: 'Log in' }).click()
    await page.waitForTimeout(5000)
}

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
