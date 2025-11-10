<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Key Management System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3b82f6;
        }
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1>Analytics Dashboard</h1>
        
        <!-- Stats Overview -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">{{ $stats['today_checkouts'] }}</div>
                    <div class="stat-label">Today's Checkouts</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">{{ $stats['week_checkouts'] }}</div>
                    <div class="stat-label">This Week's Checkouts</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        @if($stats['avg_checkout_duration'])
                            {{ number_format($stats['avg_checkout_duration'], 1) }} min
                        @else
                            N/A
                        @endif
                    </div>
                    <div class="stat-label">Avg Checkout Duration</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        @if($stats['busiest_location'])
                            {{ $stats['busiest_location']->recent_checkouts }}
                        @else
                            0
                        @endif
                    </div>
                    <div class="stat-label">
                        @if($stats['busiest_location'])
                            Checkouts at {{ $stats['busiest_location']->name }}
                        @else
                            No Active Locations
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <!-- Hourly Activity Chart -->
            <div class="col-md-8">
                <div class="chart-container">
                    <h3>Today's Hourly Activity</h3>
                    <canvas id="hourlyChart" height="100"></canvas>
                </div>
            </div>
            
            <!-- Top Keys -->
            <div class="col-md-4">
                <div class="chart-container">
                    <h3>Top Keys This Week</h3>
                    <div class="list-group">
                        @forelse($topKeys as $key)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $key->code }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $key->label }}</small>
                                </div>
                                <span class="badge bg-primary rounded-pill">{{ $key->recent_checkouts }}</span>
                            </div>
                        @empty
                            <div class="list-group-item text-muted">No key activity this week</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Stats -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h3>Quick Actions</h3>
                    <div class="d-grid gap-2">
                        <a href="{{ route('reports.key-activity') }}" class="btn btn-outline-primary">View Key Activity</a>
                        <a href="{{ route('reports.current-holders') }}" class="btn btn-outline-secondary">Current Holders</a>
                        <a href="{{ route('reports.overdue-keys') }}" class="btn btn-outline-danger">Overdue Keys</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Hourly Activity Chart
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        const hourlyData = @json($hourlyActivity);
        
        // Prepare data for all 24 hours
        const hours = Array.from({length: 24}, (_, i) => i);
        const counts = hours.map(hour => hourlyData[hour] || 0);
        
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: hours.map(hour => `${hour}:00`),
                datasets: [{
                    label: 'Checkouts',
                    data: counts,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Checkouts'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Hour of Day'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>