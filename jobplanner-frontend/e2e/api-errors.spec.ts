import { test, expect } from '@playwright/test'

const BASE_URL = process.env.FRONTEND_URL

test.describe('API Error Handling', () => {
  test('should display error message on login failure', async ({ page }) => {
    await page.route('**/api/login', async (route) => {
      await route.abort('failed')
    })

    await page.goto(`${BASE_URL}/auth/login`)
    await page.getByLabel('Email').fill('test@test.com')
    await page.getByLabel('Password').fill('password123')
    await page.getByRole('button', { name: 'Sign in' }).click()

    await expect(page.getByText('Invalid email or password')).toBeVisible()
  })

  test('should redirect to login on 401 during auth check', async ({ page }) => {
    await page.route('**/api/me', async (route) => {
      await route.fulfill({
        status: 401,
        body: JSON.stringify({ error: 'Unauthorized' }),
      })
    })

    await page.addInitScript(() => {
      localStorage.setItem('auth_token', 'invalid-token')
    })

    await page.goto(`${BASE_URL}/`)
    await expect(page).toHaveURL(/\/auth\/login/)
  })
})