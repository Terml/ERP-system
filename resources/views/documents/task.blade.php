<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Производственное задание номер {{ $task->id }}</title>
    <link rel="stylesheet" href="{{ asset('css/documents.css') }}">
</head>

<body>
    <button class="print-button task-print" onclick="window.print()">🖨️ Печать</button>

    <div class="document">
        <div class="header">
            <h1>ПРОИЗВОДСТВЕННОЕ ЗАДАНИЕ</h1>
            <div class="subtitle">Номер {{ $task->id }} от {{ $task->created_at->format('d.m.Y') }}</div>
        </div>

        <div class="task-info">
            <div class="info-section">
                <h3>Информация о задании</h3>
                <div class="info-row">
                    <span class="info-label">Номер задания:</span>
                    <span class="info-value">#{{ $task->id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Дата создания:</span>
                    <span class="info-value">{{ $task->created_at->format('d.m.Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Количество:</span>
                    <span class="info-value"><strong>{{ $task->quantity }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Статус:</span>
                    <span class="info-value">
                        <span class="status-badge status-completed">{{ $task->status }}</span>
                    </span>
                </div>
            </div>

            <div class="info-section">
                <h3>Исполнитель</h3>
                <div class="info-row">
                    <span class="info-label">Мастер:</span>
                    <span class="info-value">{{ $master->login ?? 'Не назначен' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $master->email ?? 'Не указан' }}</span>
                </div>
            </div>
        </div>

        <div class="order-details">
            <h3>Связанный заказ</h3>
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Номер заказа</th>
                        <th>Контрагент</th>
                        <th>Продукция</th>
                        <th>Количество</th>
                        <th>Срок</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#{{ $order->id }}</td>
                        <td>{{ $company->name }}</td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $order->quantity }}</td>
                        <td>{{ \Carbon\Carbon::parse($order->deadline)->format('d.m.Y') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="components-section">
            <h3>Компоненты задания</h3>
            @if($components && $components->count() > 0)
            <table class="components-table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Наименование</th>
                        <th>Тип</th>
                        <th>Единица</th>
                        <th>Количество</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($components as $index => $component)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $component->name }}</td>
                        <td>{{ $component->type === 'product' ? 'Продукт' : 'Материал' }}</td>
                        <td>{{ $component->unit }}</td>
                        <td><strong>{{ $component->quantity }}</strong></td>
                        <td>
                            <span class="status-badge status-completed">{{ $component->status }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="no-components">
                Компоненты не назначены
            </div>
            @endif
        </div>

        <div class="footer">
            <p>Документ сгенерирован: {{ $generated_at->format('d.m.Y H:i:s') }}</p>
            <p>Система управления производством</p>
        </div>
    </div>
</body>

</html>