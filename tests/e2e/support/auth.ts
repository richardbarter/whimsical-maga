import type { Page } from '@playwright/test'

/**
 * Credentials are read from .env (loaded by playwright.config.ts):
 *
 *   ADMIN_EMAIL        seeded admin (defaults to admin@example.com)
 *   ADMIN_PASSWORD     required
 *   E2E_USER_EMAIL     optional non-admin account; regular-user tests are skipped without it
 *   E2E_USER_PASSWORD  optional
 */
export const ADMIN_EMAIL = process.env.ADMIN_EMAIL ?? 'admin@example.com'
export const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD ?? ''
export const USER_EMAIL = process.env.E2E_USER_EMAIL ?? ''
export const USER_PASSWORD = process.env.E2E_USER_PASSWORD ?? ''

export async function login(page: Page, email: string, password: string): Promise<void> {
    await page.goto('/login')
    await page.getByLabel('Email').fill(email)
    await page.getByLabel('Password').fill(password)
    await page.getByRole('button', { name: 'Sign in' }).click()
    await page.waitForURL((url) => url.pathname !== '/login')
}

export async function loginAsAdmin(page: Page): Promise<void> {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)
}
