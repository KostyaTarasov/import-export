import { Page, expect, test } from '@playwright/test';

import { ADMIN } from './support';

async function submitLogin(page: Page, password: string): Promise<void> {
  await page.getByRole('textbox', { name: 'Email' }).fill(ADMIN.email);
  await page.getByRole('textbox', { name: 'Пароль' }).fill(password);
  await page.getByRole('button', { name: 'Войти' }).click();
}

test.describe('Вход', () => {
  test('без входа отправляет на страницу логина и возвращает обратно', async ({ page }) => {
    await page.goto('/products?name=бермуды');

    await expect(page).toHaveURL(/\/login\?returnUrl=/);
    await submitLogin(page, ADMIN.password);

    await expect(page).toHaveURL(/\/products\?name=/);
    await expect(page.getByRole('heading', { name: 'Товары' })).toBeVisible();
    await expect(page.getByText(ADMIN.email)).toBeVisible();
  });

  test('показывает ошибку при неверном пароле', async ({ page }) => {
    await page.goto('/login');
    await submitLogin(page, 'неверный-пароль');

    await expect(page.getByRole('alert')).toHaveText('Неверный email или пароль');
    await expect(page).toHaveURL(/\/login/);
  });

  test('выход завершает сессию', async ({ page }) => {
    await page.goto('/login');
    await submitLogin(page, ADMIN.password);
    await expect(page).toHaveURL(/\/products/);

    await page.getByRole('button', { name: 'Выйти' }).click();

    await expect(page).toHaveURL(/\/login/);
    await page.goto('/products');
    await expect(page).toHaveURL(/\/login/);
  });
});
