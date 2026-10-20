import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-not-found',
  imports: [MatButtonModule, RouterLink],
  template: `
    <div class="page">
      <h1 class="page-title">Такой страницы нет</h1>
      <p class="page-subtitle">Возможно, ссылка устарела или в адресе опечатка.</p>
      <a mat-flat-button routerLink="/products">К списку товаров</a>
    </div>
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class NotFound {}
