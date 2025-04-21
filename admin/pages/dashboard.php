<div class="dashboard-cards">
    <div class="card">
        <div class="card-icon">📦</div>
        <h3 class="card-title">Products Sold</h3>
        <div class="card-value">1,283</div>
        <div class="card-stat positive">↑ 8.3% from last month</div>
    </div>
    <div class="card">
        <div class="card-icon">👥</div>
        <h3 class="card-title">New Customers</h3>
        <div class="card-value">347</div>
        <div class="card-stat positive">↑ 15.2% from last month</div>
    </div>
</div>

<div class="charts-container">
    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Sales Overview</h3>
            <div class="chart-actions">
                <button class="chart-btn active">Weekly</button>
                <button class="chart-btn">Monthly</button>
                <button class="chart-btn">Yearly</button>
            </div>
        </div>
        <div class="chart-body">
            <canvas id="salesChart" style="height: 250px; width: 100%;"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Sales Chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Sales',
                data: [1200, 1900, 1600, 2500, 2200, 3000, 3400, 2800, 3900, 4200, 5000, 4500],
                borderColor: '#FF85B3',
                backgroundColor: 'rgba(255, 133, 179, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Chart Time Range Buttons
    const chartBtns = document.querySelectorAll('.chart-btn');
    chartBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            chartBtns.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update chart data based on selected time range
            const range = this.textContent.trim();
            let data;
            
            if (range === 'Weekly') {
                data = [500, 600, 750, 800, 950, 1100, 1000];
                salesChart.data.labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            } else if (range === 'Monthly') {
                data = [1200, 1900, 1600, 2500, 2200, 3000, 3400, 2800, 3900, 4200, 5000, 4500];
                salesChart.data.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            } else if (range === 'Yearly') {
                data = [25000, 30000, 45000, 60000, 75000];
                salesChart.data.labels = ['2021', '2022', '2023', '2024', '2025'];
            }
            
            salesChart.data.datasets[0].data = data;
            salesChart.update();
        });
    });
});
</script>