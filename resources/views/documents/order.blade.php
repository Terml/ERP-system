<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ №{{ $order->id }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>
    <div class="document">
        <div class="header">
            <div class="header-content">
                <div class="nav-controls">
                    <button class="nav-button" onclick="navigateOrder('prev')" id="prevBtn">←</button>
                    <button class="nav-button" onclick="navigateOrder('next')" id="nextBtn">→</button>
                </div>
                <div class="title-section">
                    <h1>ЗАКАЗ НА ПРОИЗВОДСТВО</h1>
                    <div class="subtitle">№ {{ $order->id }} от {{ $order->created_at->format('d.m.Y') }}</div>
                </div>
                <div class="print-control">
                    <button class="print-button" onclick="window.print()">Печать</button>
                </div>
            </div>
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
            </div>
        </div>
    </div>

    <script>
        const currentOrderId = {{ $order->id }};
        
        function navigateOrder(direction) {
            const url = new URL(window.location);
            url.searchParams.set('order_id', currentOrderId);
            url.searchParams.set('direction', direction);
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.order_id) {
                        window.location.href = `/documents/order?order_id=${data.order_id}`;
                    } else {
                        alert(direction === 'prev' ? 'Это первый заказ' : 'Это последний заказ');
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