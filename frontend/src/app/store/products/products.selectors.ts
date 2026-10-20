import { createSelector } from '@ngrx/store';

import { productsFeature } from './products.reducer';

export const selectAllProducts = productsFeature.selectItems;
export const selectProductsLoading = productsFeature.selectLoading;
export const selectProductsError = productsFeature.selectError;
export const selectProductsQuery = productsFeature.selectQuery;

export const selectTotalPages = createSelector(
  productsFeature.selectMeta,
  (meta) => meta?.totalPages ?? 0,
);
export const selectProductsTotal = createSelector(
  productsFeature.selectMeta,
  (meta) => meta?.total ?? 0,
);
