import { createActionGroup, props } from '@ngrx/store';

import { Page } from '../../models/page';
import { ProductListItem, ProductQuery } from '../../models/product';

export const ProductsActions = createActionGroup({
  source: 'Products',
  events: {
    'Load Products': props<{ query: ProductQuery }>(),
    'Load Products Success': props<{ page: Page<ProductListItem> }>(),
    'Load Products Failure': props<{ error: string }>(),
  },
});
