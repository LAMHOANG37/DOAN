<?php
    session_start();
    include '../../../config.php';

    // Tổng số phòng đã đặt
    $roombooksql = "SELECT * FROM roombook";
    $roombookre = mysqli_query($conn, $roombooksql);
    $roombookrow = mysqli_num_rows($roombookre);

    // Tổng số nhân viên
    $staffsql = "SELECT * FROM staff";
    $staffre = mysqli_query($conn, $staffsql);
    $staffrow = mysqli_num_rows($staffre);

    // Tổng số phòng (sử dụng bảng rooms - bảng chính thức)
    $roomsql = "SELECT * FROM rooms";
    $roomre = mysqli_query($conn, $roomsql);
    $roomrow = mysqli_num_rows($roomre);
    
    // Nếu bảng rooms trống, thử bảng room cũ
    if ($roomrow == 0) {
        $roomsql = "SELECT * FROM room";
        $roomre = mysqli_query($conn, $roomsql);
        $roomrow = mysqli_num_rows($roomre);
    }

    // Thống kê phòng theo loại
    $chartroom1 = "SELECT * FROM roombook WHERE RoomType='Phòng Cao Cấp'";
    $chartroom1re = mysqli_query($conn, $chartroom1);
    $chartroom1row = mysqli_num_rows($chartroom1re);

    $chartroom2 = "SELECT * FROM roombook WHERE RoomType='Phòng Sang Trọng'";
    $chartroom2re = mysqli_query($conn, $chartroom2);
    $chartroom2row = mysqli_num_rows($chartroom2re);

    $chartroom3 = "SELECT * FROM roombook WHERE RoomType='Nhà Khách'";
    $chartroom3re = mysqli_query($conn, $chartroom3);
    $chartroom3row = mysqli_num_rows($chartroom3re);

    $chartroom4 = "SELECT * FROM roombook WHERE RoomType='Phòng Đơn'";
    $chartroom4re = mysqli_query($conn, $chartroom4);
    $chartroom4row = mysqli_num_rows($chartroom4re);
    
    // Tính tổng doanh thu
    $revenueQuery = "SELECT SUM(finaltotal) as total_revenue FROM payment";
    $revenueResult = mysqli_query($conn, $revenueQuery);
    $revenueRow = mysqli_fetch_assoc($revenueResult);
    $totalRevenue = floatval($revenueRow['total_revenue'] ?? 0);
?>

<?php 	
    // Tính lợi nhuận (10% của tổng doanh thu)
    $query = "SELECT * FROM payment";
    $result = mysqli_query($conn, $query);
    $chart_data = '';
    $tot = 0;
    while($row = mysqli_fetch_array($result))
    {
        // Tính profit = 10% của finaltotal
        $profit = floatval($row["finaltotal"]) * 0.1;
        $chart_data .= "{ date:'".$row["cout"]."', profit:".round($profit, 2)."}, ";
        $tot = $tot + $profit;
    }

    $chart_data = substr($chart_data, 0, -2);
    // Làm tròn tổng profit
    $tot = round($tot, 2);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Điều Khiển - BlueBird Hotel</title>
    <link rel="stylesheet" href="./dashboard.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Morris.js -->
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-chart-line"></i> Bảng Điều Khiển</h1>
            <p class="subtitle">Tổng quan hệ thống quản lý khách sạn</p>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="stats-grid">
            <div class="stat-card stat-card-rooms">
                <div class="stat-icon">
                    <i class="fas fa-bed"></i>
                </div>
                <div class="stat-content">
                    <h3>Tổng Phòng Đã Đặt</h3>
                    <div class="stat-value">
                        <span class="number"><?php echo $roombookrow ?></span>
                        <span class="divider">/</span>
                        <span class="total"><?php echo $roomrow ?></span>
                    </div>
                    <p class="stat-label">phòng</p>
                </div>
            </div>

            <div class="stat-card stat-card-staff">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3>Tổng Nhân Viên</h3>
                    <div class="stat-value">
                        <span class="number"><?php echo $staffrow ?></span>
                    </div>
                    <p class="stat-label">người</p>
                </div>
            </div>

            <div class="stat-card stat-card-revenue">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <h3>Tổng Doanh Thu</h3>
                    <div class="stat-value">
                        <span class="number"><?php echo number_format($totalRevenue, 0, ',', '.'); ?></span>
                        <span class="currency">₫</span>
                    </div>
                    <p class="stat-label">đồng</p>
                </div>
            </div>

            <div class="stat-card stat-card-profit">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3>Lợi Nhuận</h3>
                    <div class="stat-value">
                        <span class="number"><?php echo number_format($tot, 0, ',', '.'); ?></span>
                        <span class="currency">₫</span>
                    </div>
                    <p class="stat-label">(10% doanh thu)</p>
                </div>
            </div>
        </div>

        <!-- Biểu đồ -->
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-pie"></i> Thống Kê Phòng Theo Loại</h3>
                </div>
                <div class="chart-body">
                    <canvas id="bookroomchart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-bar"></i> Biểu Đồ Lợi Nhuận Theo Thời Gian</h3>
                </div>
                <div class="chart-body">
                    <div id="profitchart"></div>
                </div>
            </div>
        </div>
    </div>

<script>
    // Biểu đồ tròn - Thống kê phòng theo loại
    const labels = [
        'Phòng Cao Cấp',
        'Phòng Sang Trọng',
        'Nhà Khách',
        'Phòng Đơn'
    ];
  
    const data = {
        labels: labels,
        datasets: [{
            label: 'Số lượng đặt',
            backgroundColor: [
                'rgba(102, 126, 234, 0.8)',
                'rgba(118, 75, 162, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(153, 102, 255, 0.8)',
            ],
            borderColor: [
                'rgba(102, 126, 234, 1)',
                'rgba(118, 75, 162, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(153, 102, 255, 1)',
            ],
            borderWidth: 2,
            data: [<?php echo $chartroom1row ?>, <?php echo $chartroom2row ?>, <?php echo $chartroom3row ?>, <?php echo $chartroom4row ?>],
        }]
    };

    const doughnutchart = {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed + ' đặt phòng';
                            return label;
                        }
                    }
                }
            }
        }
    };
      
    const myChart = new Chart(
        document.getElementById('bookroomchart'),
        doughnutchart
    );
</script>

<script>
    // Biểu đồ cột - Lợi nhuận theo thời gian
    Morris.Bar({
        element: 'profitchart',
        data: [<?php echo $chart_data; ?>],
        xkey: 'date',
        ykeys: ['profit'],
        labels: ['Lợi nhuận (₫)'],
        hideHover: 'auto',
        stacked: false,
        barColors: ['rgba(102, 126, 234, 0.8)'],
        gridTextColor: '#666',
        gridLineColor: '#e0e0e0',
        xLabelAngle: 45,
        yLabelFormat: function(y) {
            return new Intl.NumberFormat('vi-VN').format(Math.round(y)) + ' ₫';
        },
        resize: true
    });
</script>

</body>
</html>
