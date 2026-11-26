import type { Page } from '@playwright/test';
import { test, expect, kies, bewaakFouten, sessieBestand, verwachtMelding } from './helpers';

test.use({ storageState: sessieBestand('maakster1') });

/** De code van de eerste aanvraag in de lijst Beschikbaar. */
async function eersteBeschikbare(page: Page): Promise<string> {
    await page.goto('/beschikbaar');
    const code = (await page.locator('.fi-ta-record').first().locator('.fi-ta-text').first().innerText()).trim();
    expect(code).toMatch(/^[A-Z]+\d+$/);
    return code;
}

function kaart(page: Page, code: string) {
    return page.locator('.fi-ta-record').filter({ hasText: code });
}

test('maakster pakt een aanvraag op, zet hem op Klaar en verzendt hem', async ({ page }) => {
    const fouten = bewaakFouten(page);

    const code = await eersteBeschikbare(page);
    await kaart(page, code).getByRole('button', { name: 'Oppakken' }).click();
    await verwachtMelding(page, `Aanvraag ${code} is toegewezen aan jou`);
    await expect(kaart(page, code)).toHaveCount(0);

    // Staat nu onder Mijn aanvragen; open de detailpagina en zet hem op Klaar.
    await page.goto('/maakster');
    await kaart(page, code).click();
    await expect(page).toHaveURL(/\/maakster\/\d+\/edit/);
    await page.waitForLoadState('networkidle');
    await kies(page, page.locator('#form\\.Status'), 'Klaar');
    await expect(page.locator('#form\\.Status')).toHaveValue('Klaar');
    // Wachten tot de pagina klaar is met opslaan en laden, anders breekt het wegnavigeren dat af.
    await page.waitForLoadState('networkidle');

    // Terug naar de lijst en verzenden.
    await page.goto('/maakster');
    await kaart(page, code).getByRole('button', { name: 'Verzonden' }).click();
    await expect(kaart(page, code)).toHaveCount(0);

    // Verzonden aanvragen staan in het archief.
    await page.goto('/archief');
    await expect(page.locator('.fi-ta-row').filter({ hasText: code })).toHaveCount(1);

    expect(fouten).toEqual([]);
});

test('oppakken via de detailpagina', async ({ page }) => {
    const code = await eersteBeschikbare(page);

    await kaart(page, code).locator('.fi-ta-text').first().click();
    await expect(page).toHaveURL(/\/beschikbaar\/\d+\/edit/);
    await kies(page, page.locator('#form\\.Status'), 'Opgepakt');

    await expect(page).toHaveURL(/\/beschikbaar$/);
    await verwachtMelding(page, `Aanvraag ${code} is toegewezen aan jou`);
    await page.goto('/maakster');
    await expect(kaart(page, code)).toHaveCount(1);
});

test.describe('twee maaksters tegelijk', () => {
    test('via de knop: de tweede krijgt een melding en de aanvraag blijft bij de eerste', async ({ sessie }) => {
        const een = await sessie('maakster1');
        const twee = await sessie('maakster2');

        const code = await eersteBeschikbare(een);
        await twee.goto('/beschikbaar');
        await expect(kaart(twee, code)).toHaveCount(1);

        await kaart(een, code).getByRole('button', { name: 'Oppakken' }).click();
        await verwachtMelding(een, `Aanvraag ${code} is toegewezen aan jou`);

        // Maakster twee heeft de lijst nog open met de oude stand.
        await kaart(twee, code).getByRole('button', { name: 'Oppakken' }).click();
        await verwachtMelding(twee, 'net door iemand anders opgepakt');

        await een.goto('/maakster');
        await expect(kaart(een, code)).toHaveCount(1);
        await twee.goto('/maakster');
        await expect(kaart(twee, code)).toHaveCount(0);
    });

    test('via de detailpagina: de tweede krijgt een melding en de aanvraag blijft bij de eerste', async ({ sessie }) => {
        const een = await sessie('maakster1');
        const twee = await sessie('maakster2');

        const code = await eersteBeschikbare(twee);
        await kaart(twee, code).locator('.fi-ta-text').first().click();
        await expect(twee).toHaveURL(/\/beschikbaar\/\d+\/edit/);

        await eersteBeschikbare(een);
        await kaart(een, code).getByRole('button', { name: 'Oppakken' }).click();
        await verwachtMelding(een, `Aanvraag ${code} is toegewezen aan jou`);

        await kies(twee, twee.locator('#form\\.Status'), 'Opgepakt');
        await verwachtMelding(twee, 'net door iemand anders opgepakt');
        await expect(twee).toHaveURL(/\/beschikbaar$/);

        await een.goto('/maakster');
        await expect(kaart(een, code)).toHaveCount(1);
    });
});
