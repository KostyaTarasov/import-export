import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { ProductService } from './product.service';

describe('ProductService', () => {
  let service: ProductService;
  let backend: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(ProductService);
    backend = TestBed.inject(HttpTestingController);
  });

  afterEach(() => backend.verify());

  it('передаёт только заполненные фильтры', () => {
    service
      .getList({ page: 2, limit: 50, name: 'легинсы', priceFrom: null, priceTo: '1000' })
      .subscribe();

    const request = backend.expectOne((r) => r.url === '/api/products');

    expect(request.request.params.keys()).toEqual(['page', 'limit', 'name', 'priceTo']);
    expect(request.request.params.get('name')).toBe('легинсы');
    request.flush({ data: [], meta: { page: 2, limit: 50, total: 0, totalPages: 0 } });
  });

  it('достаёт товар из обёртки data', () => {
    let name = '';

    service.getById(42).subscribe((product) => (name = product.name));
    backend.expectOne('/api/products/42').flush({ data: { id: 42, name: 'Боди женское' } });

    expect(name).toBe('Боди женское');
  });
});
