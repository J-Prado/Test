import { test, expect } from '@playwright/test';

/**
 * Flow A at the browser level (E2E).
 *
 * Runs serially against a real server + DB, using one unique account so the
 * suite is repeatable even against a persistent SQLite database.
 */
test.describe.configure({ mode: 'serial' });

const email = `e2e_${Date.now()}@opcionyo.test`;
const password = 'password123';

test('a patient can register and lands on the dashboard', async ({ page }) => {
  await page.goto('/register');
  await page.getByTestId('name').fill('E2E Paciente');
  await page.getByTestId('email').fill(email);
  await page.getByTestId('password').fill(password);
  await page.getByTestId('password_confirmation').fill(password);
  await page.getByTestId('submit').click();

  await expect(page).toHaveURL(/\/dashboard$/);
  await expect(page.getByTestId('welcome')).toContainText(email);

  // Log out to test the login path next.
  await page.getByTestId('logout').click();
  await expect(page).toHaveURL(/\/login$/);
});

test('login with invalid credentials shows an error', async ({ page }) => {
  await page.goto('/login');
  await page.getByTestId('email').fill(email);
  await page.getByTestId('password').fill('wrong-password');
  await page.getByTestId('submit').click();

  await expect(page).toHaveURL(/\/login$/);
  await expect(page.getByTestId('error')).toContainText('Credenciales inválidas');
});

test('login with valid credentials reaches the dashboard', async ({ page }) => {
  await page.goto('/login');
  await page.getByTestId('email').fill(email);
  await page.getByTestId('password').fill(password);
  await page.getByTestId('submit').click();

  await expect(page).toHaveURL(/\/dashboard$/);
  await expect(page.getByTestId('welcome')).toContainText(email);
});
