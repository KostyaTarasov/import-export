import { FullConfig, chromium, request } from '@playwright/test';

import { ADMIN, STORAGE_STATE, importFixture, login } from './support';

export default async function globalSetup(config: FullConfig): Promise<void> {
  const baseURL = config.projects[0].use.baseURL;
  const api = await request.newContext({ baseURL });

  const session = await login(api);
  await importFixture(api, session.token);
  await api.dispose();

  const browser = await chromium.launch();
  const page = await browser.newPage({ baseURL });
  await page.goto('/login');
  await page.evaluate(
    (value) => localStorage.setItem('session', value),
    JSON.stringify({ ...session, email: ADMIN.email }),
  );
  await page.context().storageState({ path: STORAGE_STATE });
  await browser.close();
}
