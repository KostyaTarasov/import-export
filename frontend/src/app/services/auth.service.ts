import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, map } from 'rxjs';

import { AccessToken, Session } from '../models/auth';

const STORAGE_KEY = 'session';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly router = inject(Router);
  private readonly session = signal<Session | null>(this.restore());

  readonly email = computed(() => this.session()?.email ?? null);

  token(): string | null {
    const session = this.session();

    return session !== null && Date.parse(session.expiresAt) > Date.now() ? session.token : null;
  }

  isAuthenticated(): boolean {
    return this.token() !== null;
  }

  login(email: string, password: string): Observable<void> {
    return this.http
      .post<{ data: AccessToken }>('/api/auth/login', { email, password })
      .pipe(map(({ data }) => this.store({ ...data, email: email.trim().toLowerCase() })));
  }

  logout(returnUrl?: string): void {
    this.store(null);
    void this.router.navigate(['/login'], {
      queryParams: returnUrl && returnUrl !== '/' ? { returnUrl } : {},
    });
  }

  private store(session: Session | null): void {
    this.session.set(session);

    try {
      if (session === null) {
        localStorage.removeItem(STORAGE_KEY);
      } else {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(session));
      }
    } catch {
      return;
    }
  }

  private restore(): Session | null {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);

      return raw === null ? null : (JSON.parse(raw) as Session);
    } catch {
      return null;
    }
  }
}
