import { expect, test } from '@playwright/test';

import { FIXTURE, STORAGE_STATE } from './support';

test.use({ storageState: STORAGE_STATE });

test.describe('Импорт', () => {
  test('не принимает файл не в формате xlsx', async ({ page }) => {
    await page.goto('/import');

    await page.locator('input[type=file]').setInputFiles({
      name: 'товары.csv',
      mimeType: 'text/csv',
      buffer: Buffer.from('Внешний код;Наименование'),
    });

    await expect(page.getByRole('alert')).toHaveText('Нужен файл в формате .xlsx');
  });

  test('загружает файл и показывает отчёт по строкам', async ({ page }) => {
    await page.goto('/import');

    await page.locator('input[type=file]').setInputFiles(FIXTURE);
    await expect(page.getByText('products.xlsx')).toBeVisible();
    await page.getByRole('button', { name: 'Загрузить' }).click();

    await expect(page).toHaveURL(/\/import\/[0-9a-f-]{36}$/);
    await expect(
      page.getByRole('heading', { name: 'Импорт завершён, часть строк пропущена' }),
    ).toBeVisible({
      timeout: 60_000,
    });

    const counter = (label: string) =>
      page.locator('.counters div', { hasText: label }).locator('dd');
    await expect(counter('Обновлено')).toHaveText('40');
    await expect(counter('С ошибками')).toHaveText('2');
    await expect(counter('Предупреждения')).toHaveText('1');

    const report = page.locator('app-import-report');
    await expect(report.getByRole('row')).toHaveCount(4);
    await expect(report).toContainText(
      'Не удалось скачать изображение http://assets/missing/SNL-504038_p.jpg',
    );
    await expect(report).toContainText('Не заполнен внешний код');
    await expect(report).toContainText('Цена продажи «договорная» не является числом');
  });
});
