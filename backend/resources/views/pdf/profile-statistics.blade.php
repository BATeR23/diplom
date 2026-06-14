<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика профиля - {{ $user->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 20px;
            background: #fff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #3b82f6;
        }
        
        .header h1 {
            font-size: 24px;
            color: #1e40af;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .user-info {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #3b82f6;
        }
        
        .user-info h2 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 15px;
        }
        
        .user-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .user-detail {
            padding: 8px 0;
        }
        
        .user-detail strong {
            color: #475569;
            display: inline-block;
            min-width: 150px;
        }
        
        .stats-section {
            margin-bottom: 25px;
        }
        
        .stats-section h3 {
            font-size: 16px;
            color: #1e40af;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
        }
        
        .stat-card h4 {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
            font-weight: normal;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #64748b;
            font-size: 11px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .stat-card {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Статистика профиля</h1>
        <p>Отчет о деятельности пользователя</p>
    </div>
    
    <div class="user-info">
        <h2>Информация о пользователе</h2>
        <div class="user-details">
            <div class="user-detail">
                <strong>Имя:</strong> {{ $user->name }}
            </div>
            <div class="user-detail">
                <strong>Email:</strong> {{ $user->email }}
            </div>
            @if($user->phone)
            <div class="user-detail">
                <strong>Телефон:</strong> {{ $user->phone }}
            </div>
            @endif
            <div class="user-detail">
                <strong>Роль:</strong> 
                @if($user->role === 'driver') Водитель
                @elseif($user->role === 'passenger') Пассажир
                @elseif($user->role === 'admin') Администратор
                @elseif($user->role === 'manager') Менеджер
                @else {{ $user->role }}
                @endif
            </div>
            <div class="user-detail">
                <strong>Рейтинг:</strong> {{ number_format($averageRating, 2) }} / 5.00
            </div>
            <div class="user-detail">
                <strong>Баланс:</strong> {{ number_format($user->balance ?? 0, 2) }} ₽
            </div>
            <div class="user-detail">
                <strong>Дата регистрации:</strong> {{ $user->created_at->format('d.m.Y') }}
            </div>
        </div>
    </div>
    
    @if($user->role === 'driver' || $totalRidesAsDriver > 0)
    <div class="stats-section">
        <h3>Статистика как водитель</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Всего поездок создано</h4>
                <div class="stat-value">{{ $totalRidesAsDriver }}</div>
            </div>
            <div class="stat-card">
                <h4>Завершено поездок</h4>
                <div class="stat-value">{{ $completedRidesAsDriver }}</div>
            </div>
            <div class="stat-card">
                <h4>Активных поездок</h4>
                <div class="stat-value">{{ $publishedRidesAsDriver }}</div>
            </div>
            <div class="stat-card">
                <h4>Общий заработок</h4>
                <div class="stat-value">{{ number_format($totalEarningsAsDriver, 2) }} ₽</div>
            </div>
        </div>
    </div>
    @endif
    
    @if($user->role === 'passenger' || $totalBookingsAsPassenger > 0)
    <div class="stats-section">
        <h3>Статистика как пассажир</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Всего бронирований</h4>
                <div class="stat-value">{{ $totalBookingsAsPassenger }}</div>
            </div>
            <div class="stat-card">
                <h4>Завершено поездок</h4>
                <div class="stat-value">{{ $completedBookingsAsPassenger }}</div>
            </div>
            <div class="stat-card">
                <h4>Общая сумма потрачено</h4>
                <div class="stat-value">{{ number_format($totalSpentAsPassenger, 2) }} ₽</div>
            </div>
        </div>
    </div>
    @endif
    
    <div class="stats-section">
        <h3>Отзывы и рейтинг</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Всего отзывов получено</h4>
                <div class="stat-value">{{ $totalReviews }}</div>
            </div>
            <div class="stat-card">
                <h4>Средний рейтинг</h4>
                <div class="stat-value">{{ number_format($averageRating, 2) }} / 5.00</div>
            </div>
        </div>
    </div>
    
    @if($vehiclesCount > 0)
    <div class="stats-section">
        <h3>Автомобили</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Всего автомобилей</h4>
                <div class="stat-value">{{ $vehiclesCount }}</div>
            </div>
            <div class="stat-card">
                <h4>Одобрено автомобилей</h4>
                <div class="stat-value">{{ $approvedVehiclesCount }}</div>
            </div>
        </div>
    </div>
    @endif
    
    <div class="footer">
        <p>Отчет сгенерирован: {{ $generatedAt }}</p>
        <p>Система управления поездками</p>
    </div>
</body>
</html>





