import { defineConfig, devices } from '@playwright/test';

/**
 * Browsertests voor de aanvraagadministratie.
 *
 * - Project "ci": alle tests, tegen een lege testdatabase gevuld met
 *   BrowserTestSeeder. Draait in GitHub Actions vóór elke deploy.
 * - Project "test-site": alleen de tests met @alleen-lezen, tegen de echte
 *   test-site. Deze wijzigen niets.
 *
 * Zie tests/browser/README.md.
 */
export default defineConfig({
    testDir: './tests/browser',
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: 0, // tests wijzigen gegevens; een herhaling zou op andere gegevens draaien
    timeout: 120_000, // lokaal verwerkt de PHP-server één verzoek tegelijk; tests met twee browsers duren dan langer
    expect: { timeout: 10_000 },
    reporter: process.env.CI
        ? [['github'], ['html', { open: 'never' }]]
        : [['list'], ['html', { open: 'never' }]],
    use: {
        baseURL: process.env.BASE_URL ?? 'http://127.0.0.1:8123',
        locale: 'nl-NL',
        timezoneId: 'Europe/Amsterdam',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        // Eerst per rol inloggen en de sessie bewaren (zie auth.setup.ts).
        { name: 'setup-ci', testMatch: /auth\.setup\.ts/ },
        { name: 'setup-test-site', testMatch: /auth\.setup\.ts/ },
        {
            name: 'ci',
            dependencies: ['setup-ci'],
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'test-site',
            dependencies: ['setup-test-site'],
            grep: /@alleen-lezen/,
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
