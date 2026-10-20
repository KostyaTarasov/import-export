import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { Page } from '../models/page';
import { Product, ProductListItem, ProductQuery } from '../models/product';

@Injectable({ providedIn: 'root' })
export class ProductService {
  private readonly http = inject(HttpClient);

  getList(query: ProductQuery): Observable<Page<ProductListItem>> {
    let params = new HttpParams().set('page', query.page).set('limit', query.limit);

    for (const key of ['name', 'priceFrom', 'priceTo'] as const) {
      const value = query[key];

      if (value) {
        params = params.set(key, value);
      }
    }

    return this.http.get<Page<ProductListItem>>('/api/products', { params });
  }

  getById(id: number): Observable<Product> {
    return this.http.get<{ data: Product }>(`/api/products/${id}`).pipe(map(({ data }) => data));
  }
}
