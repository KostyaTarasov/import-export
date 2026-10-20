import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { AuthService } from './auth.service';

describe('AuthService', () => {
  let backend: HttpTestingController;

  beforeEach(() => {
    localStorage.clear();
    TestBed.configureTestingModule({
      providers: [
        provideRouter([{ path: 'login', children: [] }]),
        provideHttpClient(),
        provideHttpClientTesting(),
      ],
    });
    backend = TestBed.inject(HttpTestingController);
  });

  afterEach(() => backend.verify());

  it('после входа хранит токен и email', () => {
    const auth = TestBed.inject(AuthService);
    const expiresAt = new Date(Date.now() + 3_600_000).toISOString();

    auth.login(' Admin@Example.com ', 'admin123').subscribe();
    backend.expectOne('/api/auth/login').flush({ data: { token: 'jwt', expiresAt } });

    expect(auth.token()).toBe('jwt');
    expect(auth.email()).toBe('admin@example.com');
    expect(JSON.parse(localStorage.getItem('session') ?? '{}')).toMatchObject({ token: 'jwt' });
  });

  it('не считает просроченный токен действующим', () => {
    localStorage.setItem(
      'session',
      JSON.stringify({
        token: 'old',
        expiresAt: '2020-01-01T00:00:00+00:00',
        email: 'admin@example.com',
      }),
    );

    const auth = TestBed.inject(AuthService);

    expect(auth.token()).toBeNull();
    expect(auth.isAuthenticated()).toBe(false);
  });

  it('выход очищает сессию', () => {
    localStorage.setItem(
      'session',
      JSON.stringify({
        token: 'jwt',
        expiresAt: '2999-01-01T00:00:00+00:00',
        email: 'admin@example.com',
      }),
    );
    const auth = TestBed.inject(AuthService);

    auth.logout();

    expect(auth.isAuthenticated()).toBe(false);
    expect(localStorage.getItem('session')).toBeNull();
  });
});
