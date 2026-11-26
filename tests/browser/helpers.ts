import path from 'node:path';
import { test as base, expect, type BrowserContext, type Locator, type Page } from '@playwright/test';

export { expect };

export type Rol = 'admin' | 'beheerder' | 'maakster1' | 'maakster2' | 'inzamelpunt' | 'geenrol';

/** De rollen waarvoor auth.setup.ts vooraf een sessie aanmaakt. */
export const rollenMetSessie: Rol[] = ['admin', 'maakster1', 'maakster2', 'inzamelpunt'];

/**
 * Inloggegevens per rol. In CI komen ze uit BrowserTestSeeder; voor de
 * test-site uit omgevingsvariabelen (GitHub secrets), bijvoorbeeld
 * TEST_MAAKSTER1_EMAIL en TEST_MAAKSTER1_PASSWORD.
 */
export function account(rol: Rol): { email: string; password: string } {
    const key = rol.toUpperCase();
    return {
        email: process.env[`TEST_${key}_EMAIL`] ?? `${rol}@example.test`,
        password: process.env[`TEST_${key}_PASSWORD`] ?? process.env.BROWSER_TEST_PASSWORD ?? 'Test-wachtwoord-1',
    };
}

/** Heeft deze rol een account in de huidige omgeving? Op de test-site alleen met secrets. */
export function heeftAccount(rol: Rol, project: string): boolean {
    return project !== 'test-site' && project !== 'setup-test-site'
        ? true
        : !!process.env[`TEST_${rol.toUpperCase()}_EMAIL`];
}

/** Bestand met de opgeslagen sessie van een rol (aangemaakt door auth.setup.ts). */
export function sessieBestand(rol: Rol): string {
    return path.resolve('tests/browser/.auth', `${rol}.json`);
}

/**
 * Filament-meldingen verdwijnen na een paar seconden. Dit script onthoudt elke
 * melding die in beeld komt, zodat een test hem niet mist op een trage pagina.
 */
const onthoudMeldingen = () => {
    const w = window as any;
    w.__meldingen = w.__meldingen ?? [];
    // textContent (niet innerText): een melding is bij het invoegen nog onzichtbaar.
    new MutationObserver(() => {
        document.querySelectorAll<HTMLElement>('.fi-no-notification').forEach((n) => {
            const tekst = (n.textContent ?? '').replace(/\s+/g, ' ').trim();
            if (tekst && !w.__meldingen.includes(tekst)) {
                w.__meldingen.push(tekst);
            }
        });
    }).observe(document, { subtree: true, childList: true, characterData: true, attributes: true });
};

async function bereidContextVoor(context: BrowserContext): Promise<void> {
    await context.addInitScript(onthoudMeldingen);
}

/**
 * Gebruik in de specs `test` uit dit bestand in plaats van uit Playwright:
 * - elke pagina onthoudt meldingen (zie verwachtMelding);
 * - `sessie(rol)` opent een extra browsersessie die al ingelogd is en na de
 *   test weer wordt gesloten.
 */
export const test = base.extend<{ sessie: (rol: Rol) => Promise<Page> }>({
    context: async ({ context }, use) => {
        await bereidContextVoor(context);
        await use(context);
    },
    sessie: async ({ browser }, use) => {
        const contexten: BrowserContext[] = [];
        await use(async (rol: Rol) => {
            const context = await browser.newContext({ storageState: sessieBestand(rol) });
            await bereidContextVoor(context);
            contexten.push(context);
            return context.newPage();
        });
        for (const context of contexten) {
            await context.close();
        }
    },
});

export type InlogUitkomst = { uitkomst: 'gelukt' | 'veldfout' | 'geblokkeerd'; veldfout: string };

/**
 * Probeert in te loggen via het formulier. Filament staat 5 pogingen per minuut
 * per adres toe; daarboven blijft de pagina op /login staan zónder veldfout
 * (alleen een kortstondige melding). Bij een blokkade wachten we een minuut en
 * proberen we het opnieuw, zodat tests daar niet op vastlopen.
 */
export async function probeerInloggen(page: Page, email: string, password: string): Promise<InlogUitkomst> {
    let laatste: InlogUitkomst = { uitkomst: 'geblokkeerd', veldfout: '' };
    for (let poging = 1; poging <= 3; poging++) {
        await page.goto('/login');
        // Was een vorige (trage) poging toch gelukt, dan stuurt /login door.
        if (!new URL(page.url()).pathname.startsWith('/login')) {
            return { uitkomst: 'gelukt', veldfout: '' };
        }
        await page.locator('#form\\.email').fill(email);
        await page.locator('#form\\.password').fill(password);
        await page.locator('button[type="submit"]').click();

        const fout = page.locator('.fi-fo-field-wrp-error-message').first();
        // Ruim wachten: op een verse installatie duurt het eerste inloggen soms 25+ seconden.
        const gelukt = await Promise.race([
            page.waitForURL((url) => !url.pathname.startsWith('/login'), { timeout: 45_000 }).then(() => true),
            fout.waitFor({ state: 'visible', timeout: 45_000 }).then(() => false),
        ]).catch(() => false);
        if (gelukt) {
            return { uitkomst: 'gelukt', veldfout: '' };
        }

        const veldfout = await fout.innerText({ timeout: 500 }).catch(() => '');
        laatste = { uitkomst: veldfout ? 'veldfout' : 'geblokkeerd', veldfout };
        console.log(`Inloggen als ${email}, poging ${poging}: ${laatste.uitkomst}${veldfout ? ` ("${veldfout}")` : ''}`);
        if (laatste.uitkomst === 'veldfout' || poging === 3) {
            return laatste;
        }
        await page.waitForTimeout(61_000);
    }
    return laatste;
}

/** Inloggen als deze rol; faalt de test als dat niet lukt. */
export async function login(page: Page, rol: Rol): Promise<void> {
    const { email, password } = account(rol);
    const resultaat = await probeerInloggen(page, email, password);
    expect(resultaat, `inloggen als ${rol}`).toEqual({ uitkomst: 'gelukt', veldfout: '' });
}

/** Wacht tot de pagina klaar is met laden, zodat Livewire de velden heeft gekoppeld. */
export async function wachtTotKlaar(page: Page): Promise<void> {
    await page.waitForLoadState('networkidle');
}

/** Kies een waarde in een keuzelijst die direct opslaat (wire:model.live). */
export async function kies(page: Page, veld: Locator, waarde: string): Promise<void> {
    await wachtTotKlaar(page);
    await veld.selectOption(waarde);
}

/** Verwacht een Filament-melding met deze tekst, ook als hij alweer verdwenen is. */
export async function verwachtMelding(page: Page, tekst: string | RegExp): Promise<void> {
    const past = (t: string) => (typeof tekst === 'string' ? t.includes(tekst) : tekst.test(t));
    await expect
        .poll(async () => {
            const gezien: string[] = await page.evaluate(() => (window as any).__meldingen ?? []).catch(() => []);
            return gezien.some(past);
        }, { message: `melding "${tekst}"`, timeout: 15_000 })
        .toBe(true);
}

/** Verzamelt serverfouten en JavaScript-fouten; verwacht aan het eind een lege lijst. */
export function bewaakFouten(page: Page): string[] {
    const fouten: string[] = [];
    page.on('pageerror', (e) => fouten.push(`JS: ${e.message}`));
    page.on('response', (r) => {
        if (r.status() >= 500) fouten.push(`HTTP ${r.status()}: ${r.url()}`);
    });
    return fouten;
}
