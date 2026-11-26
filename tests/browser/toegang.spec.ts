import { test, expect, account, bewaakFouten, heeftAccount, probeerInloggen, type Rol } from './helpers';

/**
 * Per rol: het menu en de pagina's die je wel en niet mag zien.
 * De tests met @alleen-lezen wijzigen niets en draaien ook tegen de test-site.
 */
const rollen: { rol: Rol; menu: string[]; paginas: string[]; nietToegestaan: string[] }[] = [
    {
        rol: 'admin',
        menu: ['Beschikbaar', 'Aanvragen', 'Instanties', 'Afgehandeld', 'Gebruikers'],
        paginas: ['/', '/clothing', '/agencies', '/agency-report', '/completeds', '/user', '/beschikbaar', '/maakster'],
        nietToegestaan: [],
    },
    {
        rol: 'maakster1',
        menu: ['Beschikbaar', 'Mijn aanvragen', 'Archief verzonden', 'Handleiding maakster'],
        paginas: ['/', '/beschikbaar', '/maakster', '/archief', '/informatie-maaksters'],
        nietToegestaan: ['/clothing', '/agencies', '/agency-report', '/completeds', '/user'],
    },
    {
        rol: 'inzamelpunt',
        menu: ['Inzamelpunt'],
        paginas: ['/', '/Inzamelpunt'],
        nietToegestaan: ['/clothing', '/agency-report', '/beschikbaar', '/maakster'],
    },
];

for (const { rol, menu, paginas, nietToegestaan } of rollen) {
    test.describe(rol, () => {
        test.skip(() => !heeftAccount(rol, test.info().project.name), `geen testaccount voor ${rol}`);

        test(`ziet het juiste menu en de pagina's laden @alleen-lezen`, async ({ sessie }) => {
            const page = await sessie(rol);
            const fouten = bewaakFouten(page);
            await page.goto('/');

            const sidebar = page.locator('.fi-sidebar');
            for (const item of menu) {
                await expect(sidebar.getByText(item, { exact: true }).first()).toBeVisible();
            }

            for (const pad of paginas) {
                const response = await page.goto(pad);
                expect(response?.status(), pad).toBe(200);
                await expect(page.locator('.fi-header-heading, h1').first()).toBeVisible();
            }

            expect(fouten).toEqual([]);
        });

        if (nietToegestaan.length > 0) {
            test(`kan niet bij pagina's buiten de eigen rol @alleen-lezen`, async ({ sessie }) => {
                const page = await sessie(rol);
                for (const pad of nietToegestaan) {
                    const response = await page.goto(pad);
                    expect(response?.status(), pad).toBe(403);
                }
            });
        }
    });
}

// Inloggen via het formulier wordt al getest door auth.setup.ts: lukt dat niet,
// dan falen alle tests. Een aparte inlogtest zou alleen een extra poging kosten
// (Filament staat er 5 per minuut toe).

test('verkeerd wachtwoord geeft een foutmelding @alleen-lezen', async ({ page }) => {
    const resultaat = await probeerInloggen(page, account('maakster1').email, 'dit-is-niet-het-wachtwoord');
    expect(resultaat.uitkomst).toBe('veldfout');
    await expect(page).toHaveURL(/\/login/);
});

test('navigeren via het menu laadt de pagina niet opnieuw (SPA) @alleen-lezen', async ({ sessie }) => {
    test.skip(!heeftAccount('maakster1', test.info().project.name), 'geen testaccount voor maakster1');
    const page = await sessie('maakster1');
    await page.goto('/');
    await page.evaluate(() => ((window as any).__spaMarker = 'nog-dezelfde-pagina'));
    await page.locator('.fi-sidebar').getByText('Mijn aanvragen', { exact: true }).click();
    await expect(page).toHaveURL(/\/maakster/);
    await page.locator('.fi-sidebar').getByText('Beschikbaar', { exact: true }).click();
    await expect(page).toHaveURL(/\/beschikbaar/);
    expect(await page.evaluate(() => (window as any).__spaMarker)).toBe('nog-dezelfde-pagina');
});
