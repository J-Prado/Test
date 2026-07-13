import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright config for the Opcion Yo QA case.
 *
 * `webServer` boots the Laravel app with `php artisan serve` and reuses an
 * already-running server if you started one yourself. The database must be
 * migrated first (see RUN.md / CI).
 */
export default defineConfig({
  testDir: './tests',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
  use: {
    baseURL: process.env.APP_URL ?? 'http://127.0.0.1:8000',
    trace: 'on-first-retry',
    // Fake media devices so getUserMedia-based UI can be driven without hardware.
    launchOptions: {
      args: [
        '--use-fake-ui-for-media-stream',
        '--use-fake-device-for-media-stream',
      ],
    },
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
  webServer: {
    command: 'php ../artisan serve --host=127.0.0.1 --port=8000',
    url: 'http://127.0.0.1:8000',
    reuseExistingServer: !process.env.CI,
    timeout: 120_000,
  },
});
