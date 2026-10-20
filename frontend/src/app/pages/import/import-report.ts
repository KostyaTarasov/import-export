import { AsyncPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, computed, inject, input, signal } from '@angular/core';
import { toObservable } from '@angular/core/rxjs-interop';
import { MatIconModule } from '@angular/material/icon';
import { MatPaginatorModule, PageEvent } from '@angular/material/paginator';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatTableModule } from '@angular/material/table';
import { catchError, map, of, startWith, switchMap } from 'rxjs';

import { ImportIssue } from '../../models/import-job';
import { Page } from '../../models/page';
import { ImportService } from '../../services/import.service';

type ReportState = { loading: true } | { loading: false; page: Page<ImportIssue> | null };

@Component({
  selector: 'app-import-report',
  imports: [AsyncPipe, MatIconModule, MatPaginatorModule, MatProgressBarModule, MatTableModule],
  templateUrl: './import-report.html',
  styleUrl: './import-report.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ImportReport {
  private readonly imports = inject(ImportService);

  readonly jobId = input.required<string>();

  protected readonly columns = ['row', 'level', 'externalCode', 'message'];
  protected readonly pageIndex = signal(0);
  protected readonly pageSize = signal(20);

  private readonly request = computed(() => ({
    id: this.jobId(),
    page: this.pageIndex() + 1,
    limit: this.pageSize(),
  }));

  protected readonly state$ = toObservable(this.request).pipe(
    switchMap(({ id, page, limit }) =>
      this.imports.getIssues(id, page, limit).pipe(
        map((result): ReportState => ({ loading: false, page: result })),
        catchError(() => of<ReportState>({ loading: false, page: null })),
        startWith<ReportState>({ loading: true }),
      ),
    ),
  );

  protected changePage(event: PageEvent): void {
    this.pageSize.set(event.pageSize);
    this.pageIndex.set(event.pageIndex);
  }
}
