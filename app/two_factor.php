<?php
declare(strict_types=1);

const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
const ISSUER_NAME = '2FA Demo PHP';

function verify_totp_page(): void
{
    $user = pending_user();
    if (!$user || empty($user['totp_secret'])) {
        redirect_to('login');
    }

    if (is_post()) {
        $code = trim($_POST['code'] ?? '');

        if (verify_totp($user['totp_secret'], $code)) {
            finish_login((int) $user['id']);
        }

        flash('Kodi TOTP është i pasaktë.', 'error');
        redirect_to('verify_totp');
    }

    render_header('Verifiko TOTP');
    ?>
    <form method="post">
        <label for="code">Kodi TOTP</label>
        <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required>
        <button type="submit">Verifiko</button>
    </form>
    <?php
    render_footer();
}

function verify_sms_page(): void
{
    $user = pending_user();
    if (!$user) {
        redirect_to('login');
    }

    if (is_post()) {
        $code = trim($_POST['code'] ?? '');

        if ($code !== '' && hash_equals((string) ($_SESSION['sms_otp'] ?? ''), $code)) {
            finish_login((int) $user['id']);
        }

        flash('Kodi SMS është i pasaktë.', 'error');
        redirect_to('verify_sms');
    }

    render_header('Verifiko SMS');
    ?>
    <section class="qr-panel token-panel">
        <h3>SMS Demo</h3>
        <p>Në versionin demo, kodi SMS shfaqet këtu në vend që të dërgohet me shërbim të jashtëm.</p>
        <p class="token-code"><?= h($_SESSION['sms_otp'] ?? '') ?></p>
    </section>

    <form method="post">
        <label for="code">Kodi SMS</label>
        <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required>
        <button type="submit">Verifiko</button>
    </form>
    <?php
    render_footer();
}

function verify_hardware_page(): void
{
    $user = pending_user();
    if (!$user || empty($user['hardware_token_hash'])) {
        redirect_to('login');
    }

    if (is_post()) {
        $token = strtoupper(trim($_POST['token'] ?? ''));

        if ($token !== '' && password_verify($token, $user['hardware_token_hash'])) {
            finish_login((int) $user['id']);
        }

        flash('Hardware token është i pasaktë.', 'error');
        redirect_to('verify_hardware');
    }

    render_header('Verifiko Hardware Token');
    ?>
    <form method="post">
        <label for="token">Kodi i hardware token</label>
        <input id="token" name="token" placeholder="HW-XXXX-XXXX-XXXX" autocomplete="one-time-code" required>
        <button type="submit">Verifiko</button>
    </form>
    <?php
    render_footer();
}

function render_qr_panel(string $username, string $secret): void
{
    $uri = 'otpauth://totp/' . rawurlencode(ISSUER_NAME . ':' . $username)
        . '?secret=' . rawurlencode($secret)
        . '&issuer=' . rawurlencode(ISSUER_NAME);
    $qr = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($uri);
    ?>
    <section class="qr-panel">
        <h3>Skano QR për TOTP</h3>
        <img src="<?= h($qr) ?>" alt="QR Code">
        <p>Ose përdor këtë secret: <strong><?= h($secret) ?></strong></p>
    </section>
    <?php
}

function render_hardware_token_panel(?string $token): void
{
    ?>
    <section class="qr-panel token-panel">
        <h3>Hardware Token Demo</h3>
        <p>Ky kod përfaqëson pajisjen fizike të përdoruesit. Ruaje, sepse nuk shfaqet më.</p>
        <p class="token-code"><?= h($token) ?></p>
    </section>
    <?php
}

function generate_hardware_token(): string
{
    $parts = [];
    for ($i = 0; $i < 3; $i++) {
        $parts[] = strtoupper(bin2hex(random_bytes(2)));
    }

    return 'HW-' . implode('-', $parts);
}

function generate_base32_secret(int $length = 32): string
{
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= BASE32_ALPHABET[random_int(0, 31)];
    }
    return $secret;
}

function base32_decode_secret(string $secret): string
{
    $secret = strtoupper($secret);
    $bits = '';
    $binary = '';

    foreach (str_split($secret) as $char) {
        $value = strpos(BASE32_ALPHABET, $char);
        if ($value === false) {
            continue;
        }
        $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
    }

    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) === 8) {
            $binary .= chr(bindec($byte));
        }
    }

    return $binary;
}

function hotp(string $secret, int $counter): string
{
    $key = base32_decode_secret($secret);
    $counterBytes = pack('N2', intdiv($counter, 0x100000000), $counter & 0xffffffff);
    $hash = hash_hmac('sha1', $counterBytes, $key, true);
    $offset = ord($hash[19]) & 0x0f;
    $code = (
        ((ord($hash[$offset]) & 0x7f) << 24)
        | ((ord($hash[$offset + 1]) & 0xff) << 16)
        | ((ord($hash[$offset + 2]) & 0xff) << 8)
        | (ord($hash[$offset + 3]) & 0xff)
    ) % 1000000;

    return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
}

function verify_totp(string $secret, string $code): bool
{
    if (!preg_match('/^\d{6}$/', $code)) {
        return false;
    }

    $timeStep = intdiv(time(), 30);
    for ($window = -1; $window <= 1; $window++) {
        if (hash_equals(hotp($secret, $timeStep + $window), $code)) {
            return true;
        }
    }

    return false;
}