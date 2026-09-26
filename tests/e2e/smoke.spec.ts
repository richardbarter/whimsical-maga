import { test, expect, type Page } from '@playwright/test'
import { login, loginAsAdmin, USER_EMAIL, USER_PASSWORD } from './support/auth'

/**
 * Loads every page and fails on uncaught errors, console errors, or Vue warnings.
 *
 * PHPUnit's Inertia assertions only check the props a controller sends, so they
 * can't see client-side render failures such as a Ziggy route() call to a route
 * that doesn't exist, or a component used in a template without being imported.
 *
 * Requires the app running on http://localhost:8000 (docker compose up).
 */

const PUBLIC_PATHS = ['/', '/login', '/forgot-password']

/** Index pages whose first row links to an edit page (skipped when the list is empty). */
const EDITABLE_INDEXES = [
    { index: '/admin/quotes', editLink: 'Edit quote' },
    { index: '/admin/backgrounds', editLink: 'Edit background' },
    { index: '/admin/saved-contexts', editLink: 'Edit' },
]

const ADMIN_PATHS = [
    '/admin',
    '/admin/quotes',
    '/admin/quotes/create',
    '/admin/backgrounds',
    '/admin/backgrounds/create',
    '/admin/saved-contexts',
    '/admin/saved-contexts/create',
    '/admin/tags',
    '/admin/categories',
    '/profile',
]

function collectClientErrors(page: Page): string[] {
    const errors: string[] = []

    page.on('pageerror', (error) => errors.push(`Uncaught: ${error.message}`))
    page.on('console', (message) => {
        const isVueWarning = message.type() === 'warning' && message.text().startsWith('[Vue warn]')

        if (message.type() === 'error' || isVueWarning) {
            errors.push(`${message.type()}: ${message.text()}`)
        }
    })

    return errors
}

async function expectPageRendersCleanly(page: Page, path: string, errors: string[]): Promise<void> {
    await page.goto(path)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('#app')).not.toBeEmpty()
    expect(errors, `client errors on ${path}`).toEqual([])
}

test.describe('Public pages render without client errors', () => {
    for (const path of PUBLIC_PATHS) {
        test(path, async ({ page }) => {
            const errors = collectClientErrors(page)
            await expectPageRendersCleanly(page, path, errors)
        })
    }
})

test.describe('Admin pages render without client errors', () => {
    for (const path of ADMIN_PATHS) {
        test(path, async ({ page }) => {
            await loginAsAdmin(page)
            const errors = collectClientErrors(page)
            await expectPageRendersCleanly(page, path, errors)
        })
    }

    for (const { index, editLink } of EDITABLE_INDEXES) {
        test(`${index} edit page`, async ({ page }) => {
            await loginAsAdmin(page)
            await page.goto(index)

            const link = page.getByRole('link', { name: editLink, exact: true }).first()
            test.skip((await link.count()) === 0, `No records on ${index} to edit`)

            const href = await link.getAttribute('href')
            const errors = collectClientErrors(page)
            await expectPageRendersCleanly(page, new URL(href!, page.url()).pathname, errors)
        })
    }

    test('/profile uses the admin layout for admins', async ({ page }) => {
        await loginAsAdmin(page)
        await page.goto('/profile')

        await expect(page.getByRole('link', { name: 'Quotes' }).first()).toBeVisible()
    })
})

test.describe('Regular user pages', () => {
    test.skip(!USER_EMAIL || !USER_PASSWORD, 'Set E2E_USER_EMAIL and E2E_USER_PASSWORD to run')

    test('/profile renders the non-admin layout without client errors', async ({ page }) => {
        await login(page, USER_EMAIL, USER_PASSWORD)
        const errors = collectClientErrors(page)
        await expectPageRendersCleanly(page, '/profile', errors)

        await expect(page.getByRole('heading', { name: 'Profile', exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'Quotes' })).toHaveCount(0)
    })
})
