import { APIRequestContext, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';

export const STORAGE_STATE = join(__dirname, '.auth', 'admin.json');
export const FIXTURE = join(__dirname, 'fixtures', 'products.xlsx');

export const ADMIN = {
  email: process.env['E2E_EMAIL'] ?? 'admin@example.com',
  password: process.env['E2E_PASSWORD'] ?? 'admin123',
};

export async function login(api: APIRequestContext): Promise<{ token: string; expiresAt: string }> {
  const response = await api.post('/api/auth/login', { data: ADMIN });
  expect(response.status()).toBe(200);

  return (await response.json()).data;
}

export async function importFixture(api: APIRequestContext, token: string): Promise<void> {
  const headers = { Authorization: `Bearer ${token}` };
  const upload = await api.post('/api/imports', {
    headers,
    multipart: {
      file: {
        name: 'products.xlsx',
        mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        buffer: readFileSync(FIXTURE),
      },
    },
  });
  expect(upload.status()).toBe(202);

  const { id } = (await upload.json()).data;

  await expect
    .poll(
      async () => (await (await api.get(`/api/imports/${id}`, { headers })).json()).data.status,
      { timeout: 60_000, intervals: [1_000] },
    )
    .toBe('completed');
}
