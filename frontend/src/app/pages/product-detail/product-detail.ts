import { AsyncPipe, CurrencyPipe, DatePipe, DecimalPipe } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, inject, input, signal } from '@angular/core';
import { toObservable } from '@angular/core/rxjs-interop';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { RouterLink } from '@angular/router';
import { Store } from '@ngrx/store';
import { Observable, catchError, map, of, startWith, switchMap, tap } from 'rxjs';

import { apiErrorMessage } from '../../core/api-error';
import { Product, ProductQuery } from '../../models/product';
import { ProductService } from '../../services/product.service';
import { DEFAULT_QUERY } from '../../store/products/product-query';
import { selectProductsQuery } from '../../store/products/products.selectors';

type DetailState =
  | { status: 'loading' }
  | { status: 'loaded'; product: Product }
  | { status: 'not-found' }
  | { status: 'error'; message: string };

@Component({
  selector: 'app-product-detail',
  imports: [
    AsyncPipe,
    CurrencyPipe,
    DatePipe,
    DecimalPipe,
    MatButtonModule,
    MatIconModule,
    MatProgressBarModule,
    RouterLink,
  ],
  templateUrl: './product-detail.html',
  styleUrl: './product-detail.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ProductDetail {
  private readonly products = inject(ProductService);
  private readonly store = inject(Store);

  readonly id = input.required<string>();

  protected readonly selectedImage = signal(0);
  protected readonly listParams$ = this.store
    .select(selectProductsQuery)
    .pipe(map((query) => this.toListParams(query)));

  protected readonly state$: Observable<DetailState> = toObservable(this.id).pipe(
    switchMap((id) =>
      this.products.getById(Number(id)).pipe(
        tap(() => this.selectedImage.set(0)),
        map((product): DetailState => ({ status: 'loaded', product })),
        catchError((error: unknown) =>
          of<DetailState>(
            error instanceof HttpErrorResponse && error.status === 404
              ? { status: 'not-found' }
              : { status: 'error', message: apiErrorMessage(error, 'Не удалось загрузить товар') },
          ),
        ),
        startWith<DetailState>({ status: 'loading' }),
      ),
    ),
  );

  private toListParams(query: ProductQuery): Record<string, string | number> {
    const params: Record<string, string | number> = {};

    if (query.page !== DEFAULT_QUERY.page) {
      params['page'] = query.page;
    }

    if (query.limit !== DEFAULT_QUERY.limit) {
      params['limit'] = query.limit;
    }

    for (const key of ['name', 'priceFrom', 'priceTo'] as const) {
      const value = query[key];

      if (value !== null) {
        params[key] = value;
      }
    }

    return params;
  }
}
