import { HttpEventType, provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { ImportJob, ImportStatus } from '../models/import-job';
import { ImportService, POLL_INTERVAL, UploadEvent } from './import.service';

const ID = '01a15b1e-f537-7aaf-8dd5-7189521e15d1';

function job(status: ImportStatus, processedRows = 0): ImportJob {
  return {
    id: ID,
    status,
    fileName: 'import.xlsx',
    totalRows: 40,
    processedRows,
    createdCount: processedRows,
    updatedCount: 0,
    failedCount: 0,
    warningCount: 0,
    error: null,
    createdAt: '2026-10-21T01:01:00+00:00',
    startedAt: null,
    finishedAt: null,
  };
}

describe('ImportService', () => {
  let service: ImportService;
  let backend: HttpTestingController;

  beforeEach(() => {
    vi.useFakeTimers();
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(ImportService);
    backend = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    backend.verify();
    vi.useRealTimers();
  });

  it('опрашивает статус, пока импорт не закончится', () => {
    const statuses: ImportStatus[] = [];
    let completed = false;

    service.watch(ID).subscribe({
      next: (value) => statuses.push(value.status),
      complete: () => (completed = true),
    });

    vi.advanceTimersByTime(0);
    backend.expectOne(`/api/imports/${ID}`).flush({ data: job('pending') });
    vi.advanceTimersByTime(POLL_INTERVAL);
    backend.expectOne(`/api/imports/${ID}`).flush({ data: job('processing', 18) });
    vi.advanceTimersByTime(POLL_INTERVAL);
    backend.expectOne(`/api/imports/${ID}`).flush({ data: job('completed', 40) });
    vi.advanceTimersByTime(POLL_INTERVAL * 3);
    backend.expectNone(`/api/imports/${ID}`);

    expect(statuses).toEqual(['pending', 'processing', 'completed']);
    expect(completed).toBe(true);
  });

  it('не дублирует запрос, пока предыдущий не ответил', () => {
    service.watch(ID).subscribe();

    vi.advanceTimersByTime(POLL_INTERVAL * 3);
    const request = backend.expectOne(`/api/imports/${ID}`);

    request.flush({ data: job('failed') });
  });

  it('отдаёт прогресс отправки и созданную задачу', () => {
    const events: UploadEvent[] = [];

    service.upload(new File(['xlsx'], 'import.xlsx')).subscribe((event) => events.push(event));

    const request = backend.expectOne('/api/imports');
    expect(request.request.body instanceof FormData).toBe(true);
    request.event({ type: HttpEventType.UploadProgress, loaded: 50, total: 200 });
    request.flush({ data: job('pending') }, { status: 202, statusText: 'Accepted' });

    expect(events).toEqual([
      { type: 'progress', percent: 25 },
      { type: 'queued', job: job('pending') },
    ]);
  });
});
