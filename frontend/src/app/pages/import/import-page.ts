import { AsyncPipe, DatePipe, I18nPluralPipe } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import {
  ChangeDetectionStrategy,
  Component,
  DestroyRef,
  inject,
  input,
  signal,
} from '@angular/core';
import { takeUntilDestroyed, toObservable } from '@angular/core/rxjs-interop';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { Router, RouterLink } from '@angular/router';
import { Observable, catchError, map, of, startWith, switchMap } from 'rxjs';

import { apiErrorMessage } from '../../core/api-error';
import { ImportJob } from '../../models/import-job';
import { ImportService } from '../../services/import.service';
import { ImportReport } from './import-report';

type JobState =
  | { kind: 'loading' }
  | { kind: 'loaded'; job: ImportJob }
  | { kind: 'not-found' }
  | { kind: 'error'; message: string };

const MAX_FILE_SIZE = 10 * 1024 * 1024;

@Component({
  selector: 'app-import-page',
  imports: [
    AsyncPipe,
    DatePipe,
    I18nPluralPipe,
    ImportReport,
    MatButtonModule,
    MatIconModule,
    MatProgressBarModule,
    RouterLink,
  ],
  templateUrl: './import-page.html',
  styleUrl: './import-page.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ImportPage {
  private readonly imports = inject(ImportService);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  readonly jobId = input<string>();

  protected readonly file = signal<File | null>(null);
  protected readonly fileError = signal<string | null>(null);
  protected readonly uploadPercent = signal<number | null>(null);
  protected readonly dragging = signal(false);
  protected readonly rowsLabels = { one: 'из # строки', other: 'из # строк' };

  protected readonly state$: Observable<JobState | null> = toObservable(this.jobId).pipe(
    switchMap((id) =>
      id === undefined
        ? of(null)
        : this.imports.watch(id).pipe(
            map((job): JobState => ({ kind: 'loaded', job })),
            catchError((error: unknown) =>
              of<JobState>(
                error instanceof HttpErrorResponse && error.status === 404
                  ? { kind: 'not-found' }
                  : {
                      kind: 'error',
                      message: apiErrorMessage(error, 'Не удалось получить статус импорта'),
                    },
              ),
            ),
            startWith<JobState>({ kind: 'loading' }),
          ),
    ),
  );

  protected pick(input: HTMLInputElement): void {
    this.select(input.files?.[0]);
    input.value = '';
  }

  protected dragOver(event: DragEvent): void {
    event.preventDefault();
    this.dragging.set(true);
  }

  protected drop(event: DragEvent): void {
    event.preventDefault();
    this.dragging.set(false);
    this.select(event.dataTransfer?.files[0]);
  }

  protected upload(): void {
    const file = this.file();

    if (file === null || this.uploadPercent() !== null) {
      return;
    }

    this.uploadPercent.set(0);
    this.fileError.set(null);

    this.imports
      .upload(file)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (event) => {
          if (event.type === 'progress') {
            this.uploadPercent.set(event.percent);

            return;
          }

          this.uploadPercent.set(null);
          this.file.set(null);
          void this.router.navigate(['/import', event.job.id]);
        },
        error: (error: unknown) => {
          this.uploadPercent.set(null);
          this.fileError.set(apiErrorMessage(error, 'Не удалось загрузить файл'));
        },
      });
  }

  protected progress(job: ImportJob): number {
    return job.totalRows > 0 ? Math.round((job.processedRows / job.totalRows) * 100) : 0;
  }

  protected title(job: ImportJob): string {
    switch (job.status) {
      case 'pending':
        return 'Файл в очереди';
      case 'processing':
        return 'Идёт импорт';
      case 'failed':
        return 'Импорт не выполнен';
      case 'completed':
        return job.failedCount > 0 ? 'Импорт завершён, часть строк пропущена' : 'Импорт завершён';
    }
  }

  protected size(bytes: number): string {
    return bytes < 1024 * 1024
      ? `${Math.max(1, Math.round(bytes / 1024))} КБ`
      : `${(bytes / 1024 / 1024).toFixed(1).replace('.', ',')} МБ`;
  }

  private select(file: File | undefined): void {
    this.fileError.set(null);

    if (file === undefined) {
      return;
    }

    if (!file.name.toLowerCase().endsWith('.xlsx')) {
      this.file.set(null);
      this.fileError.set('Нужен файл в формате .xlsx');

      return;
    }

    if (file.size > MAX_FILE_SIZE) {
      this.file.set(null);
      this.fileError.set('Файл больше 10 МБ');

      return;
    }

    this.file.set(file);
  }
}
