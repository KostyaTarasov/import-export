import { HttpClient, provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { MatSnackBar } from '@angular/material/snack-bar';
import { provideRouter } from '@angular/router';

import { AuthService } from '../services/auth.service';
import { errorInterceptor } from './error.interceptor';

describe('errorInterceptor', () => {
  const auth = { logout: vi.fn() };
  const snackBar = { open: vi.fn() };
  let http: HttpClient;
  let backend: HttpTestingController;

  beforeEach(() => {
    auth.logout.mockReset();
    snackBar.open.mockReset();

    TestBed.configureTestingModule({
      providers: [
        provideRouter([]),
        provideHttpClient(withInterceptors([errorInterceptor])),
        provideHttpClientTesting(),
        { provide: AuthService, useValue: auth },
        { provide: MatSnackBar, useValue: snackBar },
      ],
    });

    http = TestBed.inject(HttpClient);
    backend = TestBed.inject(HttpTestingController);
  });

  afterEach(() => backend.verify());

  it('при 401 завершает сессию', () => {
    http.get('/api/products').subscribe({ error: () => undefined });
    backend.expectOne('/api/products').flush(null, { status: 401, statusText: 'Unauthorized' });

    expect(auth.logout).toHaveBeenCalledOnce();
    expect(snackBar.open).toHaveBeenCalledWith(
      'Сессия истекла, войдите заново',
      'Закрыть',
      expect.anything(),
    );
  });

  it('не разлогинивает при неверном пароле на странице входа', () => {
    http.post('/api/auth/login', {}).subscribe({ error: () => undefined });
    backend.expectOne('/api/auth/login').flush(null, { status: 401, statusText: 'Unauthorized' });

    expect(auth.logout).not.toHaveBeenCalled();
  });

  it('показывает сообщение сервера при 500', () => {
    http.get('/api/products').subscribe({ error: () => undefined });
    backend
      .expectOne('/api/products')
      .flush(
        { error: { code: 'internal_error', message: 'Внутренняя ошибка сервера' } },
        { status: 500, statusText: 'Server Error' },
      );

    expect(snackBar.open).toHaveBeenCalledWith(
      'Внутренняя ошибка сервера',
      'Закрыть',
      expect.anything(),
    );
  });

  it('пробрасывает ошибку дальше', () => {
    const errors: number[] = [];

    http
      .get('/api/products')
      .subscribe({ error: (error: { status: number }) => errors.push(error.status) });
    backend
      .expectOne('/api/products')
      .flush(null, { status: 422, statusText: 'Unprocessable Entity' });

    expect(errors).toEqual([422]);
    expect(snackBar.open).not.toHaveBeenCalled();
  });
});
