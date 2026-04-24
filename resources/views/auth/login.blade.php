<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-6 sm:p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Вход</h2>

        @if ($errors->any())
            <div class="bg-red-50 text-red-600 p-2 rounded mb-4 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="/login" method="POST" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
                <input type="password" name="password" required
                    class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="remember" id="remember" class="mr-2">
                <label for="remember" class="text-sm text-gray-600">Запомнить меня</label>
            </div>

            <button type="submit" 
                class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-lg transition-colors text-sm">
                Войти
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="/register" class="text-blue-500 hover:text-blue-700 text-sm">
                Нет аккаунта? Зарегистрируйтесь
            </a>
        </div>
    </div>
</body>
</html>