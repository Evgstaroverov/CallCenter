<h1>Информация о Telegram боте</h1>

@if($botData['ok'])
    <ul>
        <li><b>ID:</b> {{ $botData['result']['id'] }}</li>
        <li><b>Имя:</b> {{ $botData['result']['first_name'] }}</li>
        <li><b>Username:</b> @ {{ $botData['result']['username'] }}</li>
    </ul>
@else
    <p style="color: red;">Ошибка: {{ $botData['description'] ?? 'Неизвестная ошибка' }}</p>
@endif

<hr>
<h3>Сырые данные (JSON):</h3>
<pre>{{ print_r($botData, true) }}</pre>