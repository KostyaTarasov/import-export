import { ChangeDetectionStrategy, Component, inject, input, signal } from '@angular/core';
import { FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { Router } from '@angular/router';

import { apiErrorMessage } from '../../core/api-error';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-login',
  imports: [
    MatButtonModule,
    MatCardModule,
    MatFormFieldModule,
    MatIconModule,
    MatInputModule,
    MatProgressBarModule,
    ReactiveFormsModule,
  ],
  templateUrl: './login.html',
  styleUrl: './login.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class Login {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  readonly returnUrl = input<string>();

  protected readonly form = new FormGroup({
    email: new FormControl('', {
      nonNullable: true,
      validators: [Validators.required, Validators.email],
    }),
    password: new FormControl('', { nonNullable: true, validators: [Validators.required] }),
  });
  protected readonly submitting = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly passwordHidden = signal(true);

  protected submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();

      return;
    }

    const { email, password } = this.form.getRawValue();
    this.submitting.set(true);
    this.error.set(null);

    this.auth.login(email, password).subscribe({
      next: () => void this.router.navigateByUrl(this.redirectUrl()),
      error: (error: unknown) => {
        this.submitting.set(false);
        this.error.set(apiErrorMessage(error, 'Не удалось войти, попробуйте ещё раз'));
      },
    });
  }

  private redirectUrl(): string {
    const url = this.returnUrl();

    return url?.startsWith('/') && !url.startsWith('//') ? url : '/products';
  }
}
