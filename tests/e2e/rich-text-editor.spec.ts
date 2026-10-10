import { test, expect, type Locator, type Page } from '@playwright/test'
import { loginAsAdmin } from './support/auth'

/**
 * The full details editor on the Add Subject page, in a real browser: toolbar, link popover,
 * table menu and pasting. Nothing is submitted, so no data is created.
 *
 * Requires the app running on http://localhost:8000 (docker compose up).
 */

let editor: Locator

/** TipTap updates its Vue state on the next animation frame, so give the toolbar a moment. */
async function settle(page: Page): Promise<void> {
    await page.waitForTimeout(100)
}

/** Wait until the browser's selection is exactly the given text (typing can lag under load). */
async function expectSelection(page: Page, text: string): Promise<void> {
    await expect.poll(() => page.evaluate(() => window.getSelection()?.toString())).toBe(text)
}

async function pasteHtml(page: Page, html: string): Promise<void> {
    await page.evaluate((pasted) => {
        const data = new DataTransfer()
        data.setData('text/html', pasted)
        data.setData('text/plain', pasted.replace(/<[^>]+>/g, ''))
        document.querySelector('.ProseMirror')!.dispatchEvent(
            new ClipboardEvent('paste', { clipboardData: data, bubbles: true, cancelable: true }),
        )
    }, html)
    await settle(page)
}

test.describe('Rich text editor', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsAdmin(page)
        await page.goto('/admin/subjects/create')
        editor = page.locator('.ProseMirror')
        await editor.click()
        await expect(editor).toBeFocused()
    })

    test('formats the selection and shows the active state', async ({ page }) => {
        await page.keyboard.type('Ballots are safe')
        await expect(editor).toHaveText('Ballots are safe')
        await page.keyboard.press('Shift+Home')
        await expectSelection(page, 'Ballots are safe')
        await page.getByRole('button', { name: 'Bold', exact: true }).click()
        await settle(page)

        await expect(editor.locator('strong')).toHaveText('Ballots are safe')
        await expect(page.getByRole('button', { name: 'Bold', exact: true })).toHaveAttribute('aria-pressed', 'true')

        await page.getByRole('button', { name: 'Heading', exact: true }).click()
        await expect(editor.locator('h3')).toHaveText('Ballots are safe')
    })

    test('links the selected text, adding https://', async ({ page }) => {
        await page.keyboard.type('See the study')
        await expect(editor).toHaveText('See the study')
        // One keystroke selects the word; the editor reads the browser's selection a moment later
        await page.keyboard.press('Control+Shift+ArrowLeft')
        await expectSelection(page, 'study')
        await page.waitForTimeout(300)

        await page.getByRole('button', { name: 'Link', exact: true }).click()
        await page.getByLabel('Link address').fill('example.com/study')
        await page.getByRole('button', { name: 'Save link' }).click()

        const link = editor.locator('a')
        await expect(link).toHaveText('study')
        await expect(link).toHaveAttribute('href', 'https://example.com/study')
        await expect(link).toHaveAttribute('rel', 'nofollow noopener noreferrer')
    })

    test('rejects javascript: links', async ({ page }) => {
        await page.getByRole('button', { name: 'Link', exact: true }).click()
        await page.getByLabel('Link address').fill('javascript:alert(1)')
        await page.getByRole('button', { name: 'Save link' }).click()

        await expect(page.getByText('Enter a web address')).toBeVisible()
        await expect(editor.locator('a')).toHaveCount(0)
    })

    test('inserts and edits a table', async ({ page }) => {
        await page.keyboard.type('Intro')
        await expect(editor).toHaveText('Intro')
        await page.getByRole('button', { name: 'Table', exact: true }).click()
        await page.getByRole('menuitem', { name: 'Insert table' }).click()
        await page.keyboard.type('Year')
        await settle(page)

        await expect(editor.locator('p').first()).toHaveText('Intro')
        await expect(editor.locator('th').first()).toHaveText('Year')
        await expect(editor.locator('tr')).toHaveCount(3)

        await page.getByRole('button', { name: 'Table', exact: true }).click()
        await expect(page.getByRole('menuitem', { name: 'Insert table' })).toHaveAttribute('data-disabled', '')
        await page.getByRole('menuitem', { name: 'Add row below' }).click()
        await expect(editor.locator('tr')).toHaveCount(4)
    })

    test('merges cells selected by dragging', async ({ page }) => {
        await page.getByRole('button', { name: 'Table', exact: true }).click()
        await page.getByRole('menuitem', { name: 'Insert table' }).click()
        await settle(page)

        const first = (await editor.locator('td').nth(0).boundingBox())!
        const second = (await editor.locator('td').nth(1).boundingBox())!
        await page.mouse.move(first.x + 10, first.y + first.height / 2)
        await page.mouse.down()
        await page.mouse.move(second.x + 20, second.y + second.height / 2, { steps: 8 })
        await page.mouse.up()
        await settle(page)

        await page.getByRole('button', { name: 'Table', exact: true }).click()
        await page.getByRole('menuitem', { name: 'Merge cells' }).click()

        await expect(editor.locator('td[colspan="2"]')).toHaveCount(1)
    })

    test('pasting from Google Docs keeps structure and drops styling and images', async ({ page }) => {
        await pasteHtml(page,
            '<meta charset="utf-8"><b style="font-weight:normal;" id="docs-internal-guid-1">'
            + '<h1><span style="font-size:20pt">Pasted title</span></h1>'
            + '<p><span style="font-weight:400;color:#ff0000">Normal </span><span style="font-weight:700">bold</span></p>'
            + '<ul><li><p><span>Point one</span></p></li></ul>'
            + '<p><a href="https://example.com/report">the report</a></p>'
            + '<img src="https://lh7-us.googleusercontent.com/x.png">'
            + '</b>',
        )

        await expect(editor.locator('h3')).toHaveText('Pasted title')
        await expect(editor.locator('strong')).toHaveText('bold')
        await expect(editor.locator('li')).toHaveText('Point one')
        await expect(editor.locator('a')).toHaveAttribute('href', 'https://example.com/report')
        await expect(editor.locator('img')).toHaveCount(0)
        await expect(editor.locator('[style*="color"]')).toHaveCount(0)
    })

    test('toolbar buttons never submit the form', async ({ page }) => {
        for (const name of ['Bold', 'Italic', 'Strikethrough', 'Heading', 'Subheading', 'Bullet list', 'Numbered list', 'Quote block', 'Divider', 'Undo']) {
            await page.getByRole('button', { name, exact: true }).click()
        }

        await expect(page).toHaveURL(/\/admin\/subjects\/create$/)
        await expect(page.getByText('The title field is required.')).toHaveCount(0)
    })
})

/**
 * Images: the upload request is answered by the test (page.route), so nothing is stored.
 * Needs at least one subject to edit.
 */
test.describe('Rich text editor images', () => {
    // A 1×1 PNG; the upload is faked, so only its type matters
    const PNG = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', 'base64')

    /** Opens the first subject's edit page with a full page load, and returns its imageUrlPrefix prop. */
    async function openFirstSubjectEditor(page: Page): Promise<string> {
        await loginAsAdmin(page)
        await page.goto('/admin/subjects')
        const edit = page.getByRole('link', { name: 'Edit', exact: true }).first()
        test.skip((await edit.count()) === 0, 'No subject to edit')
        await page.goto((await edit.getAttribute('href'))!)
        editor = page.locator('.ProseMirror')
        await editor.click()
        await page.keyboard.press('Control+Home')

        return page.evaluate(() => JSON.parse(document.getElementById('app')!.dataset.page!).props.imageUrlPrefix as string)
    }

    async function pasteFiles(page: Page, html: string | null): Promise<void> {
        await page.evaluate(async ({ bytes, pastedHtml }) => {
            const data = new DataTransfer()
            data.items.add(new File([new Uint8Array(bytes)], 'screenshot.png', { type: 'image/png' }))
            if (pastedHtml) {
                data.setData('text/html', pastedHtml)
            }
            document.querySelector('.ProseMirror')!.dispatchEvent(
                new ClipboardEvent('paste', { clipboardData: data, bubbles: true, cancelable: true }),
            )
        }, { bytes: [...PNG], pastedHtml: html })
    }

    test('images need a saved subject', async ({ page }) => {
        await loginAsAdmin(page)
        await page.goto('/admin/subjects/create')

        await expect(page.getByRole('button', { name: 'Insert image' })).toBeDisabled()
    })

    test('uploads through the dialog, requiring alt text, and inserts a figure', async ({ page }) => {
        const prefix = await openFirstSubjectEditor(page)
        let uploads = 0
        await page.route('**/admin/subjects/*/images', (route) => {
            uploads++
            return route.fulfill({ status: 201, json: { id: 1, url: `${prefix}e2e-chart.png`, width: 900, height: 500 } })
        })

        await page.getByRole('button', { name: 'Insert image' }).click()
        const dialog = page.getByRole('dialog')
        await dialog.locator('input[type="file"]').setInputFiles({ name: 'chart.png', mimeType: 'image/png', buffer: PNG })
        await dialog.getByRole('button', { name: 'Upload and insert' }).click()
        await expect(dialog.getByText("Describe the image for people who can't see it.")).toBeVisible()
        expect(uploads).toBe(0)

        await dialog.getByLabel('Alt text *').fill('A bar chart')
        await dialog.getByLabel('Caption').fill('Fraud rates')
        await dialog.getByLabel('Credit').fill('Brennan Center')
        await dialog.getByRole('button', { name: 'Upload and insert' }).click()
        await expect(dialog).toBeHidden()

        const figure = editor.locator('figure', { has: page.locator(`img[src="${prefix}e2e-chart.png"]`) })
        await expect(figure.locator('img')).toHaveAttribute('alt', 'A bar chart')
        await expect(figure.locator('img')).toHaveAttribute('width', '900')
        await expect(figure.locator('figcaption')).toHaveText('Fraud rates — Brennan Center')
    })

    test('editing a selected image changes its alt text and caption', async ({ page }) => {
        const prefix = await openFirstSubjectEditor(page)
        await page.route('**/admin/subjects/*/images', (route) =>
            route.fulfill({ status: 201, json: { id: 1, url: `${prefix}e2e-chart.png`, width: 900, height: 500 } }))

        await page.getByRole('button', { name: 'Insert image' }).click()
        const dialog = page.getByRole('dialog')
        await dialog.locator('input[type="file"]').setInputFiles({ name: 'chart.png', mimeType: 'image/png', buffer: PNG })
        await dialog.getByLabel('Alt text *').fill('First alt')
        await dialog.getByRole('button', { name: 'Upload and insert' }).click()
        await expect(dialog).toBeHidden()

        const figure = editor.locator('figure', { has: page.locator(`img[src="${prefix}e2e-chart.png"]`) })
        await figure.click()
        await settle(page)
        await page.getByRole('button', { name: 'Edit image' }).click()
        await expect(dialog.getByLabel('Alt text *')).toHaveValue('First alt')
        await dialog.getByLabel('Alt text *').fill('Better alt')
        await dialog.getByLabel('Caption').fill('Now with a caption')
        await dialog.getByRole('button', { name: 'Save changes' }).click()

        await expect(figure.locator('img')).toHaveAttribute('alt', 'Better alt')
        await expect(figure.locator('figcaption')).toHaveText('Now with a caption')
    })

    test('pasting an image file opens the dialog with it', async ({ page }) => {
        await openFirstSubjectEditor(page)
        const figuresBefore = await editor.locator('figure').count()
        await pasteFiles(page, null)

        const dialog = page.getByRole('dialog')
        await expect(dialog.getByText('Selected: screenshot.png')).toBeVisible()
        await dialog.getByRole('button', { name: 'Cancel' }).click()
        await expect(dialog).toBeHidden()
        await expect(editor.locator('figure')).toHaveCount(figuresBefore)
    })

    test('pasting from Word pastes the text, not the picture Word adds to the clipboard', async ({ page }) => {
        await openFirstSubjectEditor(page)
        await pasteFiles(page, '<p class="MsoNormal">Pasted from Word</p>')

        await expect(page.getByRole('dialog')).toHaveCount(0)
        await expect(editor.getByText('Pasted from Word')).toBeVisible()
    })
})
