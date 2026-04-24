<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-6 sm:p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Регистрация</h2>

        @if ($errors->any())
            <div class="bg-red-50 text-red-600 p-2 rounded mb-4 text-sm">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form action="/register" method="POST" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Имя</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>

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

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Подтвердите пароль</label>
                <input type="password" name="password_confirmation" required
                    class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>

            <button type="submit" 
                class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-lg transition-colors text-sm">
                Зарегистрироваться
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="/login" class="text-blue-500 hover:text-blue-700 text-sm">
                Уже есть аккаунт? Войдите
            </a>
        </div>
    </div>
</body>
</html>