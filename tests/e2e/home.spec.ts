import { test, expect } from '@playwright/test'

/**
 * The home page's rotating quote card, in a real browser (Inertia's history state can't be
 * unit tested). Skips when no published quote has full details yet.
 *
 * Requires the app running on http://localhost:8000 (docker compose up).
 */

test('"Full details" opens the quote page, and Back returns to the same quote', async ({ page }) => {
    const quoteText = page.locator('blockquote p').first()
    const fullDetails = page.getByRole('link', { name: 'Full details' })
    const next = page.getByRole('button', { name: 'Next quote' })

    await page.goto('/')
    await expect(quoteText).toBeVisible()

    // Step forward at least once (pausing the rotation) so the restored quote isn't simply the first one
    await next.click()
    let found = false
    for (let attempt = 0; attempt < 10 && !found; attempt++) {
        await page.waitForTimeout(900) // the quote fades in
        found = await fullDetails.isVisible()
        if (!found) {
            await next.click()
        }
    }
    test.skip(!found, 'No published quote with full details to follow')

    const shownQuote = await quoteText.innerText()
    await fullDetails.click()
    await expect(page).toHaveURL(/\/quotes\/\d+\/[\w-]+$/)
    await expect(page.getByRole('heading', { name: 'On this page' }).or(page.getByText('Full details').first())).toBeVisible()

    await page.goBack()
    await expect(page).toHaveURL(/\/$/)
    await expect(quoteText).toHaveText(shownQuote)
    await expect(page.getByRole('button', { name: 'Previous quote' })).toBeEnabled()
})
