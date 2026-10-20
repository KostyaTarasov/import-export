import { ProductAttribute } from './product-attribute';
import { ProductImage } from './product-image';

export interface ProductListItem {
  id: number;
  externalCode: string;
  name: string;
  price: string;
  discount: string | null;
}

export interface Product extends ProductListItem {
  description: string | null;
  attributes: ProductAttribute[];
  images: ProductImage[];
  createdAt: string;
  updatedAt: string;
}

export interface ProductQuery {
  page: number;
  limit: number;
  name: string | null;
  priceFrom: string | null;
  priceTo: string | null;
}
