<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ №{{ $order->id }}</title>
    <link rel="stylesheet" href="{{ asset('css/documents.css') }}">
</head>

<body>
    <button class="print-button" onclick="window.print()">Печать</button>

    <div class="document">
        <div class="header">
            <h1>ЗАКАЗ НА ПРОИЗВОДСТВО</h1>
            <div class="subtitle">№ {{ $order->id }} от {{ $order->created_at->format('d.m.Y') }}</div>
        </div>

        <div class="order-info">
            <div class="info-section">
                <h3>Информация о заказе</h3>
                <div class="info-row">
                    <span class="info-label">Номер заказа:</span>
                    <span class="info-value">#{{ $order->id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Дата создания:</span>
                    <span class="info-value">{{ $order->created_at->format('d.m.Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Срок выполнения:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($order->deadline)->format('d.m.Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Статус:</span>
                    <span class="info-value">
                        <span class="status-badge status-completed">{{ $order->status }}</span>
                    </span>
                </div>
            </div>

            <div class="info-section">
                <h3>Информация о контрагенте</h3>
                <div class="info-row">
                    <span class="info-label">Название:</span>
                    <span class="info-value">{{ $company->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Описание:</span>
                    <span class="info-value">{{ $company->description ?? 'Не указано' }}</span>
                </div>
            </div>
        </div>

        <div class="product-details">
            <h3>Детали продукции</h3>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Наименование</th>
                        <th>Тип</th>
                        <th>Единица измерения</th>
                        <th>Количество</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->type === 'product' ? 'Продукт' : 'Материал' }}</td>
                        <td>{{ $product->unit }}</td>
                        <td><strong>{{ $order->quantity }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p>Документ сгенерирован: {{ $generated_at->format('d.m.Y H:i:s') }}</p>
            <p>Система управления производством</p>
        </div>
    </div>

</body>

</html>