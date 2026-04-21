import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/Playwright',
  timeout: 30_000,
  fullyParallel: true,
  retries: 0,
  reporter: 'list',
  use: {
    baseURL: 'http://127.0.0.1:8000',
    trace: 'on-first-retry',
    headless: true,
  },
  webServer: {
    command: 'composer server:start:test',
    url: 'http://127.0.0.1:8000/health/live',
    reuseExistingServer: true,
    timeout: 120_000,
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
