# PHP 2FA Demo

Ky projekt është punuar në kuadër të lëndës **Siguria e të Dhënave**. 
Qëllimi i projektit është demonstrimi i autentikimit me dy faktorë në PHP duke përdorur metoda të ndryshme verifikimi si:

- TOTP me Google Authenticator ose Microsoft Authenticator
- SMS OTP përmes Twilio
- Hardware Token demo

## Studentët

Projekti është punuar nga:

- Artiola Dushi
- Artrit Telaku
- Aulona Xhema


## Nisja e aplikacionit

Nëse përdor PHP built-in server, nga root i projektit ekzekuto:

```bash
php -S 127.0.0.1:8000
```

Pastaj hape në browser:

```text
http://127.0.0.1:8000
```

Nëse përdor XAMPP, vendose projektin brenda:

```text
C:\xampp\htdocs\siguri-gr12-pr3
```

Pastaj hape:

```text
http://localhost/siguri-gr12-pr3
```

## Instalimi i varësive

Projekti përdor Twilio SDK për dërgimin e SMS-ve reale.

Instalo varësitë me Composer:

```bash
composer install
```

Nëse `composer.json` nuk ekziston ende, instalo Twilio SDK me:

```bash
composer require twilio/sdk
```

Pas instalimit duhet të ekzistojnë:

```text
vendor/
composer.json
composer.lock
```

## Konfigurimi i `.env`

Brenda `.env` vendos kredencialet e Twilio:

```env
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_FROM=+1xxxxxxxxxx
```

Kuptimi i tyre:

- `TWILIO_ACCOUNT_SID`: Account SID nga Twilio Console
- `TWILIO_AUTH_TOKEN`: Auth Token nga Twilio Console
- `TWILIO_FROM`: numri i telefonit i marrë nga Twilio

## Ruajtja e të dhënave

Të dhënat e përdoruesve ruhen lokalisht në JSON:

```text
data/users.json
```

Ky file ruan të dhëna si:

- username
- password hash
- phone
- metoda 2FA
- TOTP secret
- hardware token hash

Kredencialet e Twilio nuk ruhen në `users.json`. Ato ruhen vetëm në `.env`.

## Metodat e 2FA

### TOTP

Metoda `TOTP` përdor aplikacione si:

- Google Authenticator
- Microsoft Authenticator
- Authy

Gjatë regjistrimit krijohet një secret dhe shfaqet QR code. Përdoruesi e skanon QR code dhe pastaj përdor kodin 6-shifror për verifikim gjatë login.

### SMS me Twilio

Metoda `SMS` gjeneron një kod 6-shifror OTP dhe e dërgon te numri i telefonit i përdoruesit përmes Twilio.

Rrjedha është:

1. Përdoruesi bën login me username dhe password.
2. Nëse metoda e tij është `sms`, ruhet `pending_user_id` në session.
3. Përdoruesi dërgohet te faqja `verify_sms`.
4. Sistemi gjeneron një kod 6-shifror.
5. Kodi ruhet në `$_SESSION['sms_otp']`.
6. Koha e dërgimit ruhet në `$_SESSION['sms_otp_sent_at']`.
7. Kodi dërgohet me Twilio te numri i përdoruesit.
8. Përdoruesi e shkruan kodin në formë.
9. Nëse kodi është i saktë dhe nuk ka skaduar, login përfundon me sukses.

Në Twilio free trial, zakonisht SMS mund të dërgohet vetëm te numrat e verifikuar në Twilio Console.

### Hardware Token demo

Metoda `Hardware Token demo` gjeneron një token demo gjatë regjistrimit. Token-i shfaqet vetëm një herë dhe ruhet si hash.

Kjo metodë është vetëm për demonstrim dhe nuk lidhet me pajisje fizike reale.

## Struktura kryesore e projektit

```text
app/
  core.php
  auth.php
  two_factor.php

data/
  users.json
  sessions/

static/
  style.css
  base.css
  forms.css
  twofactor.css

index.php
.env
composer.json
composer.lock
```

## Probleme të zakonshme

### SMS nuk dërgohet

Kontrollo:

- `.env` ka `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM`
- Auth Token është i saktë
- Numri i përdoruesit është në format ndërkombëtar, p.sh. `+38344123456`
- Në Twilio trial, numri pranues është i verifikuar
- `TWILIO_FROM` është numri i Twilio-s, jo numri personal

Për më shumë detaje kontrollo PHP error log:

```text
C:\xampp\apache\logs\error.log
```
