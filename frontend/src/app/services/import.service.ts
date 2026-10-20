import { HttpClient, HttpEventType } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, exhaustMap, filter, map, takeWhile, timer } from 'rxjs';

import { ImportIssue, ImportJob } from '../models/import-job';
import { Page } from '../models/page';

export type UploadEvent =
  { type: 'progress'; percent: number } | { type: 'queued'; job: ImportJob };

export const POLL_INTERVAL = 1000;

@Injectable({ providedIn: 'root' })
export class ImportService {
  private readonly http = inject(HttpClient);

  upload(file: File): Observable<UploadEvent> {
    const body = new FormData();
    body.append('file', file, file.name);

    return this.http
      .post<{ data: ImportJob }>('/api/imports', body, { observe: 'events', reportProgress: true })
      .pipe(
        map((event): UploadEvent | null => {
          if (event.type === HttpEventType.UploadProgress) {
            return {
              type: 'progress',
              percent: event.total ? Math.round((event.loaded / event.total) * 100) : 0,
            };
          }

          if (event.type === HttpEventType.Response && event.body !== null) {
            return { type: 'queued', job: event.body.data };
          }

          return null;
        }),
        filter((event): event is UploadEvent => event !== null),
      );
  }

  getJob(id: string): Observable<ImportJob> {
    return this.http.get<{ data: ImportJob }>(`/api/imports/${id}`).pipe(map(({ data }) => data));
  }

  watch(id: string): Observable<ImportJob> {
    return timer(0, POLL_INTERVAL).pipe(
      exhaustMap(() => this.getJob(id)),
      takeWhile((job) => !isFinished(job), true),
    );
  }

  getIssues(id: string, page: number, limit: number): Observable<Page<ImportIssue>> {
    return this.http.get<Page<ImportIssue>>(`/api/imports/${id}/errors`, {
      params: { page, limit },
    });
  }
}

export function isFinished(job: ImportJob): boolean {
  return job.status === 'completed' || job.status === 'failed';
}
