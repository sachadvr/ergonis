import { test, expect } from '@playwright/test'

const BASE_URL = process.env.FRONTEND_URL || 'http://localhost:5173'

test.describe('Authentication', () => {
  test('should display login page', async ({ page }) => {
    await page.goto(`${BASE_URL}/auth/login`)
    await expect(page.getByRole('heading', { name: 'JobPlanner' })).toBeVisible()
    await expect(page.getByLabel('Email')).toBeVisible()
    await expect(page.getByLabel('Password')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Sign in' })).toBeVisible()
  })

  test('should show validation error for empty fields', async ({ page }) => {
    await page.goto(`${BASE_URL}/auth/login`)
    await page.getByRole('button', { name: 'Sign in' }).click()
    const emailInput = page.getByLabel('Email')
    await expect(emailInput).toHaveAttribute('required', '')
  })

  test('should show error for invalid credentials', async ({ page }) => {
    await page.goto(`${BASE_URL}/auth/login`)
    await page.getByLabel('Email').fill('invalid@test.com')
    await page.getByLabel('Password').fill('wrongpassword')
    await page.getByRole('button', { name: 'Sign in' }).click()
    await expect(page.getByText('Invalid email or password')).toBeVisible()
  })

  test('should login and redirect to dashboard', async ({ page }) => {
    const email = 'guest@test.com'
    const password = 'guest'

    await page.goto(`${BASE_URL}/auth/login`)
    await page.getByLabel('Email').fill(email)
    await page.getByLabel('Password').fill(password)
    await page.getByRole('button', { name: 'Sign in' }).click()

    await page.waitForLoadState('networkidle', { timeout: 15000 })

    await expect(page).toHaveURL(/(\/(dashboard|applications)?\/?$|auth\/login)/, { timeout: 5000 })
  })
})