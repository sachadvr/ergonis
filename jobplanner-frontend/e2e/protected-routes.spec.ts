import { test, expect } from '@playwright/test'

const BASE_URL = process.env.FRONTEND_URL || 'http://localhost:5173'

test.describe('Protected Routes', () => {
  test('should redirect to login when accessing dashboard without auth', async ({ page }) => {
    await page.goto(`${BASE_URL}/`)
    await expect(page).toHaveURL(/\/auth\/login/)
  })

  test('should redirect to login when accessing applications without auth', async ({ page }) => {
    await page.goto(`${BASE_URL}/applications`)
    await expect(page).toHaveURL(/\/auth\/login/)
  })

  test('should redirect to login when accessing protected routes directly', async ({ page }) => {
    await page.goto(`${BASE_URL}/job-offers`)
    await expect(page).toHaveURL(/\/auth\/login/)

    await page.goto(`${BASE_URL}/interviews`)
    await expect(page).toHaveURL(/\/auth\/login/)

    await page.goto(`${BASE_URL}/emails`)
    await expect(page).toHaveURL(/\/auth\/login/)

    await page.goto(`${BASE_URL}/settings`)
    await expect(page).toHaveURL(/\/auth\/login/)
  })
})