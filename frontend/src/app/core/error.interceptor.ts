import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';

import { AuthService } from '../services/auth.service';
import { apiErrorMessage } from './api-error';

export const errorInterceptor: HttpInterceptorFn = (request, next) => {
  const auth = inject(AuthService);
  const router = inject(Router);
  const snackBar = inject(MatSnackBar);

  return next(request).pipe(
    catchError((error: unknown) => {
      if (error instanceof HttpErrorResponse) {
        if (error.status === 401 && request.url !== '/api/auth/login') {
          snackBar.open('Сессия истекла, войдите заново', 'Закрыть', { duration: 5000 });
          auth.logout(router.url);
        } else if (error.status === 0) {
          snackBar.open('Сервер недоступен. Проверьте подключение', 'Закрыть', { duration: 5000 });
        } else if (error.status >= 500) {
          snackBar.open(apiErrorMessage(error, 'Ошибка на сервере, попробуйте позже'), 'Закрыть', {
            duration: 5000,
          });
        }
      }

      return throwError(() => error);
    }),
  );
};
