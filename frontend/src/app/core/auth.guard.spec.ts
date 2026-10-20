import { TestBed } from '@angular/core/testing';
import {
  ActivatedRouteSnapshot,
  Router,
  RouterStateSnapshot,
  UrlTree,
  provideRouter,
} from '@angular/router';

import { AuthService } from '../services/auth.service';
import { authGuard, guestGuard } from './auth.guard';

describe('auth guards', () => {
  let authenticated: boolean;

  beforeEach(() => {
    authenticated = false;

    TestBed.configureTestingModule({
      providers: [
        provideRouter([]),
        { provide: AuthService, useValue: { isAuthenticated: () => authenticated } },
      ],
    });
  });

  function run(guard: typeof authGuard, url: string): boolean | UrlTree {
    return TestBed.runInInjectionContext(
      () =>
        guard({} as ActivatedRouteSnapshot, { url } as RouterStateSnapshot) as boolean | UrlTree,
    );
  }

  it('без входа отправляет на логин и запоминает адрес', () => {
    const result = run(authGuard, '/products?name=легинсы');

    expect(result instanceof UrlTree).toBe(true);
    expect(TestBed.inject(Router).serializeUrl(result as UrlTree)).toBe(
      '/login?returnUrl=%2Fproducts%3Fname%3D%D0%BB%D0%B5%D0%B3%D0%B8%D0%BD%D1%81%D1%8B',
    );
  });

  it('пропускает вошедшего пользователя', () => {
    authenticated = true;

    expect(run(authGuard, '/products')).toBe(true);
  });

  it('не показывает страницу входа тому, кто уже вошёл', () => {
    authenticated = true;

    expect(TestBed.inject(Router).serializeUrl(run(guestGuard, '/login') as UrlTree)).toBe(
      '/products',
    );
  });
});
