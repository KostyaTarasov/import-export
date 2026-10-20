import { createFeature, createReducer, on } from '@ngrx/store';

import { PageMeta } from '../../models/page';
import { ProductListItem, ProductQuery } from '../../models/product';
import { DEFAULT_QUERY } from './product-query';
import { ProductsActions } from './products.actions';

export interface ProductsState {
  items: ProductListItem[];
  meta: PageMeta | null;
  query: ProductQuery;
  loading: boolean;
  error: string | null;
}

export const initialState: ProductsState = {
  items: [],
  meta: null,
  query: DEFAULT_QUERY,
  loading: false,
  error: null,
};

export const productsFeature = createFeature({
  name: 'products',
  reducer: createReducer(
    initialState,
    on(ProductsActions.loadProducts, (state, { query }) => ({
      ...state,
      query,
      loading: true,
      error: null,
    })),
    on(ProductsActions.loadProductsSuccess, (state, { page }) => ({
      ...state,
      items: page.data,
      meta: page.meta,
      loading: false,
    })),
    on(ProductsActions.loadProductsFailure, (state, { error }) => ({
      ...state,
      items: [],
      meta: null,
      loading: false,
      error,
    })),
  ),
});
