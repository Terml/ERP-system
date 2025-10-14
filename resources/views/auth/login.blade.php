<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Вход в систему</h1>
                <p>Система управления производством</p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="login" class="form-label">Логин</label>
                    <input
                        type="text"
                        id="login"
                        name="login"
                        class="form-input @error('login') error @enderror"
                        value="{{ old('login') }}"
                        required
                        autofocus
                        placeholder="Введите логин">
                    @error('login')
                    <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Пароль</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input @error('password') error @enderror"
                        required
                        placeholder="Введите пароль">
                    @error('password')
                    <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    Войти
                </button>
            </form>

        </div>
    </div>

</body>

</html>