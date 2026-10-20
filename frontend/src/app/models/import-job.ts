export type ImportStatus = 'pending' | 'processing' | 'completed' | 'failed';

export interface ImportJob {
  id: string;
  status: ImportStatus;
  fileName: string;
  totalRows: number;
  processedRows: number;
  createdCount: number;
  updatedCount: number;
  failedCount: number;
  warningCount: number;
  error: string | null;
  createdAt: string;
  startedAt: string | null;
  finishedAt: string | null;
}

export interface ImportIssue {
  row: number;
  externalCode: string | null;
  level: 'error' | 'warning';
  message: string;
}
