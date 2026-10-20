import { Params } from '@angular/router';

import { ProductQuery } from '../../models/product';

export const PAGE_SIZES = [10, 20, 50];
export const DEFAULT_QUERY: ProductQuery = {
  page: 1,
  limit: 20,
  name: null,
  priceFrom: null,
  priceTo: null,
};

export function queryFromParams(params: Params): ProductQuery {
  const page = Number(params['page']);
  const limit = Number(params['limit']);

  return {
    page: Number.isInteger(page) && page > 0 ? page : DEFAULT_QUERY.page,
    limit: PAGE_SIZES.includes(limit) ? limit : DEFAULT_QUERY.limit,
    name: text(params['name']),
    priceFrom: text(params['priceFrom']),
    priceTo: text(params['priceTo']),
  };
}

function text(value: unknown): string | null {
  return typeof value === 'string' && value.trim() !== '' ? value.trim() : null;
}
