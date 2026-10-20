import { initialState, ProductsState } from './products.reducer';
import {
  selectAllProducts,
  selectProductsLoading,
  selectProductsTotal,
  selectTotalPages,
} from './products.selectors';

describe('products selectors', () => {
  const state = (products: ProductsState) => ({ products });

  it('отдают товары и признак загрузки', () => {
    const products: ProductsState = {
      ...initialState,
      loading: true,
      items: [{ id: 7, externalCode: 'Pp7', name: 'Легинсы', price: '1599.00', discount: null }],
    };

    expect(selectAllProducts(state(products))).toEqual(products.items);
    expect(selectProductsLoading(state(products))).toBe(true);
  });

  it('считают страницы по meta', () => {
    const products: ProductsState = {
      ...initialState,
      meta: { page: 2, limit: 20, total: 52, totalPages: 3 },
    };

    expect(selectTotalPages(state(products))).toBe(3);
    expect(selectProductsTotal(state(products))).toBe(52);
  });

  it('без meta возвращают ноль', () => {
    expect(selectTotalPages(state(initialState))).toBe(0);
    expect(selectProductsTotal(state(initialState))).toBe(0);
  });
});
