<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 */

$this->title = 'Login';
?>

<div class="login-page">
    <div class="login-card">

        <div class="login-brand">
            <div class="brand-mark">DM</div>
            <h1>Document Management</h1>
            <p>Sign in to continue</p>
        </div>

        <form id="login-form">

            <div class="form-group">
                <label for="login">Username or Email</label>
                <input
                    type="text"
                    id="login"
                    name="login"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <div id="login-error" class="login-error"></div>

            <button type="submit" id="login-button">
                Sign In
            </button>

        </form>

    </div>
</div>

<?php
$apiUrl = 'http://127.0.0.1:8081/api/v1/auth/login';

$this->registerJs(<<<JS
const form = document.getElementById('login-form');
const button = document.getElementById('login-button');
const error = document.getElementById('login-error');

form.addEventListener('submit', async function (event) {
    event.preventDefault();

    error.textContent = '';
    button.disabled = true;
    button.textContent = 'Signing in...';

    const login = document.getElementById('login').value.trim();
    const password = document.getElementById('password').value;

    try {
        const response = await fetch('$apiUrl', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                login: login,
                password: password
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || 'Invalid username/email or password.'
            );
        }

        localStorage.setItem('auth_key', data.auth_key);
        localStorage.setItem('user', JSON.stringify(data.user));

        window.location.href = '/index.php?r=dashboard/index';

    } catch (err) {
        error.textContent = err.message;
    } finally {
        button.disabled = false;
        button.textContent = 'Sign In';
    }
});
JS);
?>
