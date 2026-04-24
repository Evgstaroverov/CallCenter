<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Telegram Messages</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <div class="bg-gray-800 text-white p-3 flex justify-between items-center">
            <div>
                <span class="text-sm">{{ Auth::user()->name }}</span>
                <span class="text-xs text-gray-400 ml-2">({{ Auth::user()->email }})</span>
            </div>
            <form action="/logout" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-sm">
                    Выйти
                </button>
            </form>
        </div>
        
        <telegram-messages></telegram-messages>
    </div>
</body>
</html>