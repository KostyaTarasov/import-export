export interface AccessToken {
  token: string;
  expiresAt: string;
}

export interface Session extends AccessToken {
  email: string;
}
