import path from 'node:path';
import type { Page } from '@playwright/test';
import { test, expect, kies, bewaakFouten, verwachtMelding } from './helpers';

function rij(page: Page, code: string) {
    return page.locator('.fi-ta-row').filter({ hasText: code });
}

async function codesInLijst(page: Page): Promise<string[]> {
    const teksten = await page.locator('.fi-ta-row .fi-ta-text').allInnerTexts();
    return teksten.map((t) => t.trim()).filter((t) => /^[A-Z]+\d{6}$/.test(t));
}

test('bulkactie Opgepakt => Verzonden', async ({ sessie }) => {
    const page = await sessie('admin');
    const fouten = bewaakFouten(page);
    await page.goto('/clothing?filters[Status][values][0]=Opgepakt');

    const codes = (await codesInLijst(page)).slice(0, 2);
    expect(codes.length).toBe(2);
    for (const code of codes) {
        await rij(page, code).locator('.fi-ta-record-checkbox').check();
    }
    await page.getByRole('button', { name: /Opgepakt\s*=>\s*verzonden/i }).click();
    for (const code of codes) {
        await expect(rij(page, code)).toHaveCount(0);
    }

    await page.goto('/clothing?filters[Status][values][0]=Verzonden');
    for (const code of codes) {
        await expect(rij(page, code)).toHaveCount(1);
    }
    expect(fouten).toEqual([]);
});

test('status van Afgehandeld terugzetten en weer afhandelen stuurt door naar de juiste lijst', async ({ sessie }) => {
    const page = await sessie('admin');
    const fouten = bewaakFouten(page);

    await page.goto('/completeds');
    const code = (await codesInLijst(page))[0];
    await rij(page, code).locator('a').first().click();
    await expect(page).toHaveURL(/\/completeds\/\d+\/edit/);
    const id = page.url().match(/completeds\/(\d+)/)![1];

    await kies(page, page.locator('#form\\.Status'), 'Ontvangen');
    await expect(page).toHaveURL(new RegExp(`/clothing/${id}/edit`));
    await verwachtMelding(page, `Aanvraag ${code} staat nu onder Aanvragen`);
    await expect(page.locator('#form\\.Status')).toHaveValue('Ontvangen');

    // Een andere wijziging op de nieuwe pagina wordt gewoon opgeslagen.
    await page.locator('#form\\.Kleuren').fill('browsertest-kleur');
    await page.getByRole('button', { name: 'Opslaan' }).click();
    await verwachtMelding(page, 'Opgeslagen');

    await page.goto(`/clothing/${id}/edit`);
    await expect(page.locator('#form\\.Kleuren')).toHaveValue('browsertest-kleur');
    await kies(page, page.locator('#form\\.Status'), 'Afgehandeld');
    await expect(page).toHaveURL(new RegExp(`/completeds/${id}/edit`));
    await verwachtMelding(page, `Aanvraag ${code} staat nu onder Afgehandeld`);

    expect(fouten).toEqual([]);
});

test('in het venster bij een instantie sluit afhandelen het venster', async ({ sessie }) => {
    const page = await sessie('admin');

    // Een ontvangen aanvraag van Test Instantie Alfa (codes beginnen met TA).
    await page.goto('/clothing?filters[Status][values][0]=Ontvangen');
    const code = (await codesInLijst(page)).find((c) => c.startsWith('TA'));
    expect(code, 'geen ontvangen aanvraag van Test Instantie Alfa').toBeTruthy();

    await page.goto('/agencies');
    await page.locator('.fi-ta-row').filter({ hasText: 'Test Instantie Alfa' }).locator('a').first().click();
    await expect(page).toHaveURL(/\/agencies\/\d+\/edit/);

    // In de tab Aanvragen zoeken, zodat de aanvraag zeker op de eerste pagina staat.
    const tabel = page.locator('.fi-resource-relation-manager');
    await tabel.locator('input[type="search"]').fill(code!);
    const ontvangen = tabel.locator('.fi-ta-row').filter({ hasText: code! });
    await expect(ontvangen).toHaveCount(1);
    await ontvangen.locator('.fi-ta-actions .fi-ac-link-action').click();

    const venster = page.locator('.fi-modal-window').filter({ has: page.locator('select') }).first();
    await expect(venster).toBeVisible();
    await kies(page, venster.locator('select[id$="Status"]'), 'Afgehandeld');

    await verwachtMelding(page, `Aanvraag ${code} staat nu onder Afgehandeld`);
    await expect(venster).toBeHidden();
    await expect(tabel.locator('.fi-ta-row').filter({ hasText: code })).toHaveCount(0);
});

test('inzamelpunt markeert een verzonden aanvraag als ontvangen', async ({ sessie }) => {
    const page = await sessie('inzamelpunt');
    const fouten = bewaakFouten(page);
    await page.goto('/Inzamelpunt');

    const code = (await codesInLijst(page))[0];
    expect(code).toBeTruthy();
    await rij(page, code).getByRole('button', { name: 'Ontvangen' }).click();
    await expect(rij(page, code)).toHaveCount(0);
    expect(fouten).toEqual([]);
});

test('instanties exporteren levert een Excel-bestand op', async ({ sessie }) => {
    const page = await sessie('admin');
    await page.goto('/agencies');

    const download = page.waitForEvent('download');
    await page.getByRole('button', { name: 'Exporteren' }).first().click();
    // Sommige exports tonen eerst een venster met een bevestigknop.
    const bevestig = page.locator('.fi-modal-window').getByRole('button', { name: /Exporteren|Verzenden|Export/ });
    if (await bevestig.isVisible({ timeout: 2000 }).catch(() => false)) {
        await bevestig.click();
    }
    const bestand = await download;
    expect(bestand.suggestedFilename()).toMatch(/^Instanties - \d{4}-\d{2}-\d{2}\.xlsx$/);
});

test('aanvragen importeren uit Excel', async ({ sessie }) => {
    const page = await sessie('admin');
    const fouten = bewaakFouten(page);
    await page.goto('/clothing');

    await page.getByRole('button', { name: 'Aanvragen importeren' }).click();
    const venster = page.locator('.fi-modal-window').filter({ hasText: 'Aanvragen importeren' });
    await expect(venster).toBeVisible();

    // Instantie kiezen (zoekbare keuzelijst).
    await venster.locator('.fi-fo-field').filter({ hasText: 'Instantie' }).locator('.fi-select-input-btn, button, [role="combobox"]').first().click();
    await page.getByRole('option', { name: 'Test Instantie Beta' }).click();

    await venster.locator('input[type="file"]').setInputFiles(path.resolve('tests/browser/fixtures/import-aanvragen.xlsx'));
    await expect(venster.locator('.filepond--file-info-main')).toHaveText('import-aanvragen.xlsx');
    await expect(venster.locator('.filepond--item[data-filepond-item-state="processing-complete"]')).toHaveCount(1, { timeout: 30_000 });

    await venster.getByRole('button', { name: 'Import File' }).click();
    await verwachtMelding(page, 'Importeren gelukt');

    await page.goto('/clothing?filters[Status][values][0]=Ingevoerd');
    await expect(page.locator('.fi-ta-row').filter({ hasText: 'Browsertest trui' })).toHaveCount(1);
    await expect(page.locator('.fi-ta-row').filter({ hasText: 'Browsertest jurk' })).toHaveCount(1);
    expect(fouten).toEqual([]);
});
