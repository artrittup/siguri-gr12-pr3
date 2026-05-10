# PHP 2FA Demo

Nise app-in:

```bash
php -S 127.0.0.1:8000
```

Pastaj hape:

```text
http://127.0.0.1:8000
```

Të dhënat ruhen lokalisht në JSON:

```text
data/users.json
```

Metodat e 2FA:

- `TOTP`: skano QR code me Google Authenticator ose Microsoft Authenticator.
- `SMS demo`: gjeneron kod 6-shifror dhe e shfaq në faqen e verifikimit, pa shërbim të jashtëm.
- `Hardware Token demo`: gjeneron një kod fizik demo që ruhet si hash.
