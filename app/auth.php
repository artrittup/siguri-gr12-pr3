<?php

declare(strict_types=1);

function register_page(): void
{
    if (is_post()) {
        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $method = $_POST['method'] ?? 'totp';

        if ($username === '' || $password === '') {
            flash('Përdoruesi dhe fjalëkalimi janë të detyrueshëm.', 'error');
            redirect_to('register');
        }

        if (!in_array($method, ['totp', 'sms', 'hardware'], true)) {
            flash('Mënyra e 2FA nuk është valide.', 'error');
            redirect_to('register');
        }

        if ($method === 'sms' && $phone === '') {
            flash('Telefoni është i detyrueshëm për verifikim me SMS.', 'error');
            redirect_to('register');
        }

        if (find_user_by_username($username)) {
            flash('Ky username ekziston.', 'error');
            redirect_to('register');
        }

        $secret = $method === 'totp' ? generate_base32_secret() : null;
        $hardwareToken = $method === 'hardware' ? generate_hardware_token() : null;
        $users = load_users();
        $users[] = [
            'id' => next_user_id($users),
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'phone' => $phone !== '' ? $phone : null,
            'twofa_method' => $method,
            'totp_secret' => $secret,
            'hardware_token_hash' => $hardwareToken ? password_hash($hardwareToken, PASSWORD_DEFAULT) : null,
        ];
        save_users($users);

        if ($method === 'totp') {
            flash('Përdoruesi u regjistrua. Skano QR kodin para se të kyçesh.', 'success');
            render_header('Regjistrimi');
            render_qr_panel($username, $secret);
            echo '<a href="?page=login">Kyçu</a>';
            render_footer();
            return;
        }

        if ($method === 'hardware') {
            flash('Përdoruesi u regjistrua. Ruaje token-in fizik, sepse shfaqet vetëm një herë.', 'success');
            render_header('Regjistrimi');
            render_hardware_token_panel($hardwareToken);
            echo '<a href="?page=login">Kyçu</a>';
            render_footer();
            return;
        }

        flash('Përdoruesi u regjistrua me sukses.', 'success');
        redirect_to('login');
    }

    render_header('Regjistrimi');
?>
    <form method="post">
        <label for="username">Përdoruesi</label>
        <input id="username" name="username" autocomplete="username" required>

        <label for="password">Fjalëkalimi</label>
        <input id="password" type="password" name="password" autocomplete="new-password" required>

        <label for="phone">Telefoni</label>
        <input id="phone" type="tel" name="phone" placeholder="+38344123456">

        <label for="method">Mënyra e 2FA</label>
        <select id="method" name="method">
            <option value="totp">TOTP (Google Authenticator)</option>
            <option value="sms">SMS (demo)</option>
            <option value="hardware">Hardware Token (demo)</option>
        </select>

        <button type="submit">Regjistrohu</button>
    </form>
    <a href="?page=login">Kyçu</a>
<?php
    render_footer();
}

function login_page(): void
{
    if (is_post()) {
        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $user = find_user_by_username($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            flash('Kredenciale të pasakta.', 'error');
            redirect_to('login');
        }

        $_SESSION['pending_user_id'] = (int) $user['id'];

        if ($user['twofa_method'] === 'sms') {
            if (empty($user['phone'])) {
                flash('Ky përdorues nuk ka numër telefoni për SMS.', 'error');
                redirect_to('login');
            }

            $_SESSION['sms_otp'] = (string) random_int(100000, 999999);
            redirect_to('verify_sms');
        }

        if ($user['twofa_method'] === 'hardware') {
            redirect_to('verify_hardware');
        }

        redirect_to('verify_totp');
    }

    render_header('Kyçu');
?>
    <form method="post">
        <label for="username">Përdoruesi</label>
        <input id="username" name="username" autocomplete="username" required>

        <label for="password">Fjalëkalimi</label>
        <input id="password" type="password" name="password" autocomplete="current-password" required>

        <button type="submit">Kyçu</button>
    </form>
    <a href="?page=register">Regjistrohu</a>
<?php
    render_footer();
}

function dashboard_page(): void
{
    $user = require_login();
    render_header('Dashboard');
    echo '<p>Mirësevini, <strong>' . h($user['username']) . '</strong>!</p>';
    echo '<p>Ky është dashboard-i juaj.</p>';
    echo '<a href="?page=logout">Çkyçu</a>';
    render_footer();
}

function logout_user(): never
{
    session_destroy();
    session_start();
    flash('U çkyçët me sukses.', 'success');
    redirect_to('login');
}

function pending_user(): ?array
{
    if (empty($_SESSION['pending_user_id'])) {
        return null;
    }

    return find_user_by_id((int) $_SESSION['pending_user_id']);
}

function finish_login(int $userId): never
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    unset($_SESSION['pending_user_id'], $_SESSION['sms_otp']);
    flash('Hyrja u realizua me sukses.', 'success');
    redirect_to('dashboard');
}
