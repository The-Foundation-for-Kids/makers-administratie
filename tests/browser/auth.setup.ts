import { test as setup } from '@playwright/test';
import { heeftAccount, login, rollenMetSessie, sessieBestand } from './helpers';

/**
 * Logt per rol één keer in en bewaart de sessie, zodat de tests niet steeds
 * opnieuw hoeven in te loggen (Filament staat 5 inlogpogingen per minuut toe).
 */
for (const rol of rollenMetSessie) {
    setup(`inloggen als ${rol}`, async ({ page }) => {
        setup.skip(!heeftAccount(rol, setup.info().project.name), `geen testaccount voor ${rol}`);
        await login(page, rol);
        await page.context().storageState({ path: sessieBestand(rol) });
    });
}
