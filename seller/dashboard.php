<?php
require_once '../config/database.php';
require_once '../utils/auth.php';
requireSeller(); // 确保只有商家可以访问

// 获取商家基本信息
function getSellerStats($sellerId) {
    $db = Database::getInstance()->getConnection();
    
    // 获取在售车辆数量
    $stmt = $db->prepare("SELECT COUNT(*) FROM cars WHERE seller_id = ? AND status = '在售'");
    $stmt->execute([$sellerId]);
    $carCount = $stmt->fetchColumn();
    
    // 获取在售商品数量
    $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
    $stmt->execute([$sellerId]);
    $productCount = $stmt->fetchColumn();
    
    // 获取待发货订单数量
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE seller_id = ? AND status = '未发货'");
    $stmt->execute([$sellerId]);
    $pendingOrders = $stmt->fetchColumn();
    
    // 获取总收入
    $stmt = $db->prepare("SELECT SUM(total_price) FROM orders WHERE seller_id = ?");
    $stmt->execute([$sellerId]);
    $totalIncome = $stmt->fetchColumn() ?: 0;
    
    return [
        'cars' => $carCount,
        'products' => $productCount,
        'pending_orders' => $pendingOrders,
        'total_income' => $totalIncome
    ];
}

$stats = getSellerStats(getCurrentUserId());
?>

<!DOCTYPE html>
<html>
<head>
    <title>商家管理中心</title>
    <meta charset="utf-8">
    <style>
        .dashboard {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2196F3;
            margin: 10px 0;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);  /* 改为3列 */
            gap: 20px;
        }
        .menu-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
            text-decoration: none;
            color: inherit;
        }
        .menu-item:hover {
            transform: translateY(-5px);
        }
        .menu-item h3 {
            margin: 0;
            color: #333;
        }
        .menu-item p {
            color: #666;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h2>欢迎回来，<?php echo htmlspecialchars(getCurrentUsername()); ?></h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>在售车辆</h3>
                <div class="stat-number"><?php echo $stats['cars']; ?></div>
            </div>
            <div class="stat-card">
                <h3>在售商品</h3>
                <div class="stat-number"><?php echo $stats['products']; ?></div>
            </div>
            <div class="stat-card">
                <h3>待发货订单</h3>
                <div class="stat-number"><?php echo $stats['pending_orders']; ?></div>
            </div>
            <div class="stat-card">
                <h3>总收入</h3>
                <div class="stat-number">￥<?php echo number_format($stats['total_income'], 2); ?></div>
            </div>
        </div>
        
        <div class="menu-grid">
            <a href="cars.php" class="menu-item">
                <h3>车辆管理</h3>
                <p>管理您的车辆信息，包括添加、修改和下架</p>
            </a>
            <a href="products.php" class="menu-item">
                <h3>商品管理</h3>
                <p>管理汽车用品，包括库存和价格调整</p>
            </a>
            <a href="orders.php" class="menu-item">
                <h3>订单管理</h3>
                <p>查看和处理客户订单，发货管理</p>
            </a>
        </div>
    </div>
</body>
</html>