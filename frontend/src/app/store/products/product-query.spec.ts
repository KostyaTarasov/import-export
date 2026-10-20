import { DEFAULT_QUERY, queryFromParams } from './product-query';

describe('queryFromParams', () => {
  it('без параметров возвращает запрос по умолчанию', () => {
    expect(queryFromParams({})).toEqual(DEFAULT_QUERY);
  });

  it('разбирает все параметры', () => {
    expect(
      queryFromParams({
        page: '3',
        limit: '50',
        name: ' легинсы ',
        priceFrom: '800',
        priceTo: '1000',
      }),
    ).toEqual({ page: 3, limit: 50, name: 'легинсы', priceFrom: '800', priceTo: '1000' });
  });

  it('игнорирует некорректные значения', () => {
    expect(queryFromParams({ page: '-1', limit: '1000', name: '  ' })).toEqual(DEFAULT_QUERY);
    expect(queryFromParams({ page: '1.5', limit: 'abc' })).toEqual(DEFAULT_QUERY);
  });
});
