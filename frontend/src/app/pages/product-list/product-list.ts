import { AsyncPipe, CurrencyPipe, DecimalPipe, I18nPluralPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import {
  AbstractControl,
  FormControl,
  FormGroup,
  ReactiveFormsModule,
  ValidationErrors,
  Validators,
} from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatPaginatorModule, PageEvent } from '@angular/material/paginator';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatTableModule } from '@angular/material/table';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Store } from '@ngrx/store';

import { ProductListItem, ProductQuery } from '../../models/product';
import { DEFAULT_QUERY, PAGE_SIZES, queryFromParams } from '../../store/products/product-query';
import { ProductsActions } from '../../store/products/products.actions';
import {
  selectAllProducts,
  selectProductsError,
  selectProductsLoading,
  selectProductsQuery,
  selectProductsTotal,
  selectTotalPages,
} from '../../store/products/products.selectors';

const PRICE_PATTERN = /^\d{1,10}([.,]\d{1,2})?$/;

function priceRange(group: AbstractControl): ValidationErrors | null {
  const from = Number(String(group.get('priceFrom')?.value ?? '').replace(',', '.'));
  const to = Number(String(group.get('priceTo')?.value ?? '').replace(',', '.'));
  const filled = group.get('priceFrom')?.value !== '' && group.get('priceTo')?.value !== '';

  return filled && from > to ? { priceRange: true } : null;
}

@Component({
  selector: 'app-product-list',
  imports: [
    AsyncPipe,
    CurrencyPipe,
    DecimalPipe,
    I18nPluralPipe,
    MatButtonModule,
    MatFormFieldModule,
    MatIconModule,
    MatInputModule,
    MatPaginatorModule,
    MatProgressBarModule,
    MatTableModule,
    ReactiveFormsModule,
    RouterLink,
  ],
  templateUrl: './product-list.html',
  styleUrl: './product-list.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ProductList {
  private readonly store = inject(Store);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);

  protected readonly products$ = this.store.select(selectAllProducts);
  protected readonly loading$ = this.store.select(selectProductsLoading);
  protected readonly error$ = this.store.select(selectProductsError);
  protected readonly total$ = this.store.select(selectProductsTotal);
  protected readonly totalPages$ = this.store.select(selectTotalPages);
  protected readonly query$ = this.store.select(selectProductsQuery);

  protected readonly columns = ['name', 'externalCode', 'price', 'discount'];
  protected readonly pageSizes = PAGE_SIZES;
  protected readonly defaultQuery = DEFAULT_QUERY;
  protected readonly totalLabels = {
    '=0': 'Товаров не найдено',
    one: 'Найден # товар',
    few: 'Найдено # товара',
    other: 'Найдено # товаров',
  };

  private readonly initial = queryFromParams(this.route.snapshot.queryParams);

  protected readonly filters = new FormGroup(
    {
      name: new FormControl(this.initial.name ?? '', { nonNullable: true }),
      priceFrom: new FormControl(this.initial.priceFrom ?? '', {
        nonNullable: true,
        validators: [Validators.pattern(PRICE_PATTERN)],
      }),
      priceTo: new FormControl(this.initial.priceTo ?? '', {
        nonNullable: true,
        validators: [Validators.pattern(PRICE_PATTERN)],
      }),
    },
    { validators: priceRange },
  );

  protected search(): void {
    if (this.filters.invalid) {
      this.filters.markAllAsTouched();

      return;
    }

    const { name, priceFrom, priceTo } = this.filters.getRawValue();

    this.navigate({
      name: name.trim() || null,
      priceFrom: priceFrom.trim() || null,
      priceTo: priceTo.trim() || null,
      page: null,
    });
  }

  protected reset(): void {
    this.filters.reset();
    this.navigate({ name: null, priceFrom: null, priceTo: null, page: null });
  }

  protected changePage(event: PageEvent): void {
    this.navigate({
      page: event.pageIndex > 0 ? event.pageIndex + 1 : null,
      limit: event.pageSize !== DEFAULT_QUERY.limit ? event.pageSize : null,
    });
  }

  protected retry(query: ProductQuery): void {
    this.store.dispatch(ProductsActions.loadProducts({ query }));
  }

  protected trackById(_index: number, product: ProductListItem): number {
    return product.id;
  }

  private navigate(queryParams: Record<string, string | number | null>): void {
    void this.router.navigate([], {
      relativeTo: this.route,
      queryParams,
      queryParamsHandling: 'merge',
    });
  }
}
