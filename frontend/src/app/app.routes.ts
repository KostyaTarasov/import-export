import { Routes } from '@angular/router';

import { authGuard, guestGuard } from './core/auth.guard';

export const routes: Routes = [
  {
    path: 'login',
    title: 'Вход',
    canActivate: [guestGuard],
    loadComponent: () => import('./pages/login/login').then((m) => m.Login),
  },
  {
    path: '',
    canActivate: [authGuard],
    loadComponent: () => import('./core/shell/shell').then((m) => m.Shell),
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'products' },
      {
        path: 'products',
        title: 'Товары',
        loadComponent: () => import('./pages/product-list/product-list').then((m) => m.ProductList),
      },
      {
        path: 'products/:id',
        title: 'Карточка товара',
        loadComponent: () =>
          import('./pages/product-detail/product-detail').then((m) => m.ProductDetail),
      },
      {
        path: 'import',
        title: 'Импорт',
        loadComponent: () => import('./pages/import/import-page').then((m) => m.ImportPage),
      },
      {
        path: 'import/:jobId',
        title: 'Импорт',
        loadComponent: () => import('./pages/import/import-page').then((m) => m.ImportPage),
      },
    ],
  },
  {
    path: '**',
    title: 'Страница не найдена',
    loadComponent: () => import('./pages/not-found/not-found').then((m) => m.NotFound),
  },
];
