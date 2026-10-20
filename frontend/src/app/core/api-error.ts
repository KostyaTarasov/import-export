import { HttpErrorResponse } from '@angular/common/http';

import { ApiErrorBody } from '../models/api-error';

export function apiErrorMessage(error: unknown, fallback: string): string {
  if (error instanceof HttpErrorResponse && isApiErrorBody(error.error)) {
    return error.error.error.message;
  }

  return fallback;
}

export function apiFieldErrors(error: unknown): Record<string, string> {
  if (error instanceof HttpErrorResponse && isApiErrorBody(error.error)) {
    return error.error.error.fields ?? {};
  }

  return {};
}

function isApiErrorBody(body: unknown): body is ApiErrorBody {
  return (
    typeof body === 'object' &&
    body !== null &&
    'error' in body &&
    typeof (body as ApiErrorBody).error?.message === 'string'
  );
}
