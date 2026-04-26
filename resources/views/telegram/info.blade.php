<h1>Последние обновления бота</h1>

@foreach($updates as $update)
    <div style="border: 1px solid #ccc; margin-bottom: 10px; padding: 10px;">
        <p><strong>От кого:</strong> {{ $update['message']['from']['first_name'] ?? 'Аноним' }}</p>
        <p><strong>Текст:</strong> {{ $update['message']['text'] ?? 'Нет текста' }}</p>
        <small>ID сообщения: {{ $update['update_id'] }}</small>
    </div>
@endforeach