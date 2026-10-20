import { expect, test } from '@playwright/test';

import { STORAGE_STATE } from './support';

test.use({ storageState: STORAGE_STATE });

function price(text: string): number {
  return Number(text.replace(/[^\d,]/g, '').replace(',', '.'));
}

test.describe('Каталог', () => {
  test('ищет по названию без учёта регистра', async ({ page }) => {
    await page.goto('/products');

    await page.getByRole('textbox', { name: 'Название' }).fill('ЛЕГИНСЫ');
    await page.getByRole('button', { name: 'Найти' }).click();

    await expect(page).toHaveURL(/name=/);
    await expect(page.getByText('Найден 31 товар')).toBeVisible();
    await expect(page.getByRole('row')).toHaveCount(21);
  });

  test('фильтрует по диапазону цен', async ({ page }) => {
    await page.goto('/products');

    await page.getByRole('textbox', { name: 'Цена от' }).fill('800');
    await page.getByRole('textbox', { name: 'Цена до' }).fill('1000');
    await page.getByRole('button', { name: 'Найти' }).click();

    await expect(page).toHaveURL(/priceFrom=800&priceTo=1000/);
    const prices = await page.locator('td.mat-column-price').allTextContents();

    expect(prices.length).toBeGreaterThan(0);
    for (const value of prices) {
      expect(price(value)).toBeGreaterThanOrEqual(800);
      expect(price(value)).toBeLessThanOrEqual(1000);
    }
  });

  test('листает страницы и запоминает их в адресе', async ({ page }) => {
    await page.goto('/products');
    const firstPage = await page.locator('td.mat-column-name').allTextContents();

    await page.getByRole('button', { name: 'Следующая страница' }).click();

    await expect(page).toHaveURL(/page=2/);
    await expect(page.getByText(/страница 2 из/)).toBeVisible();
    expect(await page.locator('td.mat-column-name').allTextContents()).not.toEqual(firstPage);

    await page.goBack();
    await expect(page.getByText(/страница 1 из/)).toBeVisible();
  });

  test('открывает карточку с характеристиками и фото', async ({ page }) => {
    await page.goto('/products?name=' + encodeURIComponent('Grigio/Verde, OMSA, 46'));

    await page.getByRole('link', { name: /Бермуды мужские, Grigio\/Verde/ }).click();

    await expect(
      page.getByRole('heading', { name: /Бермуды мужские, Grigio\/Verde, OMSA, 46\(M\)/ }),
    ).toBeVisible();
    await expect(page.getByText('3UHfAid1jaMiwgBuNvnsf3')).toBeVisible();
    await expect(page.getByText('скидка 33,33 %')).toBeVisible();
    await expect(page.locator('dt').getByText('Цвет', { exact: true })).toBeVisible();
    await expect(page.locator('dd').getByText('Grigio/Verde', { exact: true })).toBeVisible();

    const image = page.locator('.main-image img');
    await expect(image).toBeVisible();
    expect(
      await image.evaluate((element: HTMLImageElement) => element.naturalWidth),
    ).toBeGreaterThan(0);

    await page.getByRole('link', { name: 'К списку товаров' }).click();
    await expect(page).toHaveURL(/\/products\?name=/);
  });
});
