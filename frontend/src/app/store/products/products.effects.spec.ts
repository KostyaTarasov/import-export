import { HttpErrorResponse } from '@angular/common/http';
import { Action } from '@ngrx/store';
import { routerNavigatedAction } from '@ngrx/router-store';
import { Observable, firstValueFrom, of, throwError, toArray } from 'rxjs';

import { Page } from '../../models/page';
import { ProductListItem } from '../../models/product';
import { ProductService } from '../../services/product.service';
import { DEFAULT_QUERY } from './product-query';
import { ProductsActions } from './products.actions';
import { loadProducts, loadProductsOnNavigation } from './products.effects';

const page: Page<ProductListItem> = {
  data: [],
  meta: { page: 1, limit: 20, total: 0, totalPages: 0 },
};

function navigated(url: string, queryParams: Record<string, string> = {}): Action {
  return routerNavigatedAction({
    payload: {
      routerState: { url, root: { queryParams } },
      event: { id: 1, url, urlAfterRedirects: url },
    },
  } as unknown as Parameters<typeof routerNavigatedAction>[0]);
}

function collect(effect: Observable<Action>): Promise<Action[]> {
  return firstValueFrom(effect.pipe(toArray()));
}

describe('products effects', () => {
  it('загружают товары при переходе на список с параметрами из URL', async () => {
    const actions = await collect(
      loadProductsOnNavigation(
        of(navigated('/products?name=бермуды&page=2', { name: 'бермуды', page: '2' })),
      ),
    );

    expect(actions).toEqual([
      ProductsActions.loadProducts({ query: { ...DEFAULT_QUERY, name: 'бермуды', page: 2 } }),
    ]);
  });

  it('не реагируют на переход в карточку', async () => {
    expect(await collect(loadProductsOnNavigation(of(navigated('/products/42'))))).toEqual([]);
  });

  it('запрашивают страницу через ProductService', async () => {
    const service = { getList: vi.fn(() => of(page)) } as unknown as ProductService;

    const actions = await collect(
      loadProducts(of(ProductsActions.loadProducts({ query: DEFAULT_QUERY })), service),
    );

    expect(service.getList).toHaveBeenCalledWith(DEFAULT_QUERY);
    expect(actions).toEqual([ProductsActions.loadProductsSuccess({ page })]);
  });

  it('отдают сообщение API при ошибке', async () => {
    const error = new HttpErrorResponse({
      status: 422,
      error: { error: { code: 'validation_failed', message: 'Проверьте параметры запроса' } },
    });
    const service = { getList: () => throwError(() => error) } as unknown as ProductService;

    const actions = await collect(
      loadProducts(of(ProductsActions.loadProducts({ query: DEFAULT_QUERY })), service),
    );

    expect(actions).toEqual([
      ProductsActions.loadProductsFailure({ error: 'Проверьте параметры запроса' }),
    ]);
  });
});
