import { inject } from '@angular/core';
import { Actions, createEffect, ofType } from '@ngrx/effects';
import { routerNavigatedAction } from '@ngrx/router-store';
import { catchError, filter, map, of, switchMap } from 'rxjs';

import { apiErrorMessage } from '../../core/api-error';
import { ProductService } from '../../services/product.service';
import { queryFromParams } from './product-query';
import { ProductsActions } from './products.actions';

export const loadProductsOnNavigation = createEffect(
  (actions$ = inject(Actions)) =>
    actions$.pipe(
      ofType(routerNavigatedAction),
      filter(({ payload }) => payload.routerState.url.split('?')[0] === '/products'),
      map(({ payload }) =>
        ProductsActions.loadProducts({
          query: queryFromParams(payload.routerState.root.queryParams),
        }),
      ),
    ),
  { functional: true },
);

export const loadProducts = createEffect(
  (actions$ = inject(Actions), products = inject(ProductService)) =>
    actions$.pipe(
      ofType(ProductsActions.loadProducts),
      switchMap(({ query }) =>
        products.getList(query).pipe(
          map((page) => ProductsActions.loadProductsSuccess({ page })),
          catchError((error: unknown) =>
            of(
              ProductsActions.loadProductsFailure({
                error: apiErrorMessage(error, 'Не удалось загрузить товары'),
              }),
            ),
          ),
        ),
      ),
    ),
  { functional: true },
);
