import { registerLocaleData } from '@angular/common';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import localeRu from '@angular/common/locales/ru';
import {
  ApplicationConfig,
  LOCALE_ID,
  inject,
  provideAppInitializer,
  provideBrowserGlobalErrorListeners,
} from '@angular/core';
import { MAT_FORM_FIELD_DEFAULT_OPTIONS } from '@angular/material/form-field';
import { MatIconRegistry } from '@angular/material/icon';
import { MatPaginatorIntl } from '@angular/material/paginator';
import { TitleStrategy, provideRouter, withComponentInputBinding } from '@angular/router';
import { provideEffects } from '@ngrx/effects';
import { provideRouterStore, routerReducer } from '@ngrx/router-store';
import { provideStore } from '@ngrx/store';

import { routes } from './app.routes';
import { authInterceptor } from './core/auth.interceptor';
import { errorInterceptor } from './core/error.interceptor';
import { RussianPaginatorIntl } from './core/paginator-intl';
import { AppTitleStrategy } from './core/title-strategy';
import * as productsEffects from './store/products/products.effects';
import { productsFeature } from './store/products/products.reducer';

registerLocaleData(localeRu);

export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideRouter(routes, withComponentInputBinding()),
    provideHttpClient(withInterceptors([authInterceptor, errorInterceptor])),
    provideStore({ router: routerReducer, [productsFeature.name]: productsFeature.reducer }),
    provideEffects(productsEffects),
    provideRouterStore(),
    provideAppInitializer(() => {
      inject(MatIconRegistry).setDefaultFontSetClass('material-symbols-outlined');
    }),
    { provide: LOCALE_ID, useValue: 'ru' },
    { provide: TitleStrategy, useClass: AppTitleStrategy },
    { provide: MatPaginatorIntl, useClass: RussianPaginatorIntl },
    { provide: MAT_FORM_FIELD_DEFAULT_OPTIONS, useValue: { appearance: 'outline' } },
  ],
};
