import { Page } from '../../models/page';
import { ProductListItem } from '../../models/product';
import { DEFAULT_QUERY } from './product-query';
import { ProductsActions } from './products.actions';
import { initialState, productsFeature } from './products.reducer';

const page: Page<ProductListItem> = {
  data: [
    {
      id: 1,
      externalCode: '3UHfAid1jaMiwgBuNvnsf3',
      name: 'Бермуды мужские, Grigio/Verde, OMSA, 46(M), РОССИЯ',
      price: '1320.00',
      discount: '33.33',
    },
  ],
  meta: { page: 1, limit: 20, total: 1, totalPages: 1 },
};

describe('productsFeature.reducer', () => {
  const reducer = productsFeature.reducer;

  it('запоминает запрос и включает загрузку', () => {
    const query = { ...DEFAULT_QUERY, name: 'бермуды' };

    const state = reducer(initialState, ProductsActions.loadProducts({ query }));

    expect(state.query).toEqual(query);
    expect(state.loading).toBe(true);
    expect(state.error).toBeNull();
  });

  it('сохраняет страницу товаров', () => {
    const loading = reducer(initialState, ProductsActions.loadProducts({ query: DEFAULT_QUERY }));

    const state = reducer(loading, ProductsActions.loadProductsSuccess({ page }));

    expect(state.items).toEqual(page.data);
    expect(state.meta).toEqual(page.meta);
    expect(state.loading).toBe(false);
  });

  it('при ошибке очищает список и сохраняет сообщение', () => {
    const loaded = reducer(initialState, ProductsActions.loadProductsSuccess({ page }));

    const state = reducer(
      loaded,
      ProductsActions.loadProductsFailure({ error: 'Сервер недоступен' }),
    );

    expect(state.items).toEqual([]);
    expect(state.meta).toBeNull();
    expect(state.error).toBe('Сервер недоступен');
    expect(state.loading).toBe(false);
  });
});
