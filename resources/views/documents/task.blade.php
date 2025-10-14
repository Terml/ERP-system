<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Производственное задание номер {{ $task->id }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>
    <div class="document">
        <div class="header">
            <div class="header-content">
                <div class="nav-controls">
                    <button class="nav-button" onclick="navigateTask('prev')" id="prevBtn">←</button>
                    <button class="nav-button" onclick="navigateTask('next')" id="nextBtn">→</button>
                </div>
                <div class="title-section">
                    <h1>ПРОИЗВОДСТВЕННОЕ ЗАДАНИЕ</h1>
                    <div class="subtitle">№ {{ $task->id }} от {{ $task->created_at->format('d.m.Y') }}</div>
                </div>
                <div class="print-control">
                    <button class="print-button" onclick="window.print()">Печать</button>
                </div>
            </div>
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
                        <th>Планируемое</th>
                        <th>Использовано</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($components as $index => $component)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $component->product->name ?? 'Не указано' }}</td>
                        <td>{{ $component->product->type === 'product' ? 'Продукт' : 'Материал' }}</td>
                        <td>{{ $component->product->unit ?? 'Не указано' }}</td>
                        <td><strong>{{ $component->quantity ?? 0 }}</strong></td>
                        <td><strong>{{ $component->used_quantity ?? 0 }}</strong></td>
                        <td>
                            @php
                                $status = 'wait';
                                $statusClass = 'status-wait';
                                if ($component->used_quantity > 0) {
                                    if ($component->used_quantity >= $component->quantity) {
                                        $status = 'completed';
                                        $statusClass = 'status-completed';
                                    } else {
                                        $status = 'in_process';
                                        $statusClass = 'status-in-process';
                                    }
                                }
                            @endphp
                            <span class="status-badge {{ $statusClass }}">{{ $status }}</span>
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

    </div>

    <script>
        const currentTaskId = {{ $task->id }};
        
        function navigateTask(direction) {
            const url = new URL(window.location);
            url.searchParams.set('task_id', currentTaskId);
            url.searchParams.set('direction', direction);
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.task_id) {
                        window.location.href = `/documents/task?task_id=${data.task_id}`;
                    } else {
                        alert(direction === 'prev' ? 'Это первое задание' : 'Это последнее задание');
                    }
                })
                .catch(error => {
                    console.error('Ошибка навигации:', error);
                });
        }
        
        window.addEventListener('beforeprint', function() {
            document.querySelector('.nav-controls').style.display = 'none';
            document.querySelector('.print-control').style.display = 'none';
        });
        
        window.addEventListener('afterprint', function() {
            document.querySelector('.nav-controls').style.display = 'flex';
            document.querySelector('.print-control').style.display = 'block';
        });
    </script>

</body>

</html>