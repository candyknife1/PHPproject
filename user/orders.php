<?php
require_once '../config/database.php';
require_once '../utils/auth.php';
require_once '../includes/nav.php';

requireLogin();

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();

// 获取订单列表
$sql = "SELECT o.*, 
        CASE 
            WHEN o.item_type = 'car' THEN c.car_name
            WHEN o.item_type = 'product' THEN p.product_name
        END as item_name,
        u.username as seller_name
        FROM orders o
        LEFT JOIN cars c ON o.item_type = 'car' AND o.item_id = c.car_id
        LEFT JOIN products p ON o.item_type = 'product' AND o.item_id = p.product_id
        LEFT JOIN users u ON o.seller_id = u.user_id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>我的订单</title>
    <meta charset="utf-8">
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .orders-grid {
            display: grid;
            gap: 20px;
        }
        .order-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }
        .order-id {
            color: #666;
            font-size: 14px;
        }
        .order-time {
            color: #999;
            font-size: 14px;
        }
        .order-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .item-info {
            flex-grow: 1;
        }
        .item-name {
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
        }
        .item-detail {
            color: #666;
            font-size: 14px;
        }
        .order-price {
            font-size: 18px;
            color: #f44336;
            font-weight: bold;
        }
        .order-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: white;
        }
        .status-未发货 {
            background: #ff9800;
        }
        .status-已发货 {
            background: #4caf50;
        }
        .seller-info {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #eee;
        }
        .empty-orders {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #1976D2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>我的订单</h2>
        
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <h3>还没有订单</h3>
                <p>快去购物吧！</p>
                <a href="../index.php" class="btn">去购物</a>
            </div>
        <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">订单号：<?php echo $order['order_id']; ?></span>
                            <span class="order-time"><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        
                        <div class="order-content">
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($order['item_name']); ?></div>
                                <div class="item-detail">
                                    类型：<?php echo $order['item_type'] == 'car' ? '车辆' : '用品'; ?> | 
                                    数量：<?php echo $order['quantity']; ?>
                                </div>
                            </div>
                            <div class="order-price">
                                ￥<?php echo number_format($order['total_price'], 2); ?>
                            </div>
                            <div class="status-<?php echo $order['status']; ?> order-status">
                                <?php echo $order['status']; ?>
                            </div>
                        </div>
                        
                        <div class="seller-info">
                            卖家：<?php echo htmlspecialchars($order['seller_name']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 