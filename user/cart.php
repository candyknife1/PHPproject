<?php
 // 检查是否是AJAX请求
 if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
     define('IS_AJAX', true);
 }

require_once '../config/database.php';
require_once '../utils/auth.php';
require_once '../includes/nav.php';

requireLogin();

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $response = ['status' => 'error', 'message' => '操作失败'];
    
    switch ($_POST['action']) {
        // 删除购物车商品
        case 'remove':
            $cartId = (int)$_POST['cart_id'];
            try {
                $stmt = $db->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
                if ($stmt->execute([$cartId, $userId])) {
                    $response = ['status' => 'success', 'message' => '商品已移除'];
                }
            } catch (PDOException $e) {
                $response['message'] = '删除失败：' . $e->getMessage();
            }
            break;
            
        // 结算购物车
        case 'checkout':
            try {
                $db->beginTransaction();
                // 获取购物车商品
                $stmt = $db->prepare("SELECT c.*, 
                    CASE 
                        WHEN c.item_type = 'car' THEN car.price
                        WHEN c.item_type = 'product' THEN p.price
                    END as price,
                    CASE 
                        WHEN c.item_type = 'car' THEN car.seller_id
                        WHEN c.item_type = 'product' THEN p.seller_id
                    END as seller_id
                    FROM cart c
                    LEFT JOIN cars car ON c.item_type = 'car' AND c.item_id = car.car_id
                    LEFT JOIN products p ON c.item_type = 'product' AND c.item_id = p.product_id
                    WHERE c.user_id = ?");
                $stmt->execute([$userId]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($items as $item) {
                    // 创建订单
                    $totalPrice = $item['price'] * $item['quantity'];
                    $stmt = $db->prepare("INSERT INTO orders (user_id, seller_id, item_type, item_id, quantity, total_price, status) 
                                        VALUES (?, ?, ?, ?, ?, ?, '未发货')");
                    $stmt->execute([
                        $userId,
                        $item['seller_id'],
                        $item['item_type'],
                        $item['item_id'],
                        $item['quantity'],
                        $totalPrice
                    ]);
                    // 更新商品状态/库存
                    if ($item['item_type'] == 'product') {
                        $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
                        $stmt->execute([$item['quantity'], $item['item_id']]);
                    } else {
                        $stmt = $db->prepare("UPDATE cars SET status = '已售' WHERE car_id = ?");
                        $stmt->execute([$item['item_id']]);
                    }
                }
                
                // 清空购物车
                $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$userId]);
                
                $db->commit();
                $response = ['status' => 'success', 'message' => '下单成功！'];
                
            } catch (Exception $e) {
                $db->rollBack();
                $response['message'] = '下单失败：' . $e->getMessage();
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}

// 获取购物车商品
$stmt = $db->prepare("SELECT c.*, 
    CASE 
        WHEN c.item_type = 'car' THEN car.car_name
        WHEN c.item_type = 'product' THEN p.product_name
    END as item_name,
    CASE 
        WHEN c.item_type = 'car' THEN car.price
        WHEN c.item_type = 'product' THEN p.price
    END as price
    FROM cart c
    LEFT JOIN cars car ON c.item_type = 'car' AND c.item_id = car.car_id
    LEFT JOIN products p ON c.item_type = 'product' AND c.item_id = p.product_id
    WHERE c.user_id = ?");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 计算总价
$totalPrice = 0;
foreach ($cartItems as $item) {
    $totalPrice += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>购物车</title>
    <meta charset="utf-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .cart-table th, .cart-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .cart-table th {
            background: #f5f5f5;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
        }
        .btn-primary { background: #2196F3; }
        .btn-danger { background: #f44336; }
        .total-section {
            text-align: right;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .total-price {
            color: #f44336;
            font-weight: bold;
            font-size: 24px;
        }
        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>购物车</h2>
        
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <h3>购物车是空的</h3>
                <p>快去添加商品吧！</p>
                <a href="../index.php" class="btn btn-primary">去购物</a>
            </div>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>商品名称</th>
                        <th>类型</th>
                        <th>单价</th>
                        <th>数量</th>
                        <th>小计</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                    <tr data-id="<?php echo $item['cart_id']; ?>">
                        <td><?php echo $item['item_name']; ?></td>
                        <td><?php echo $item['item_type'] == 'car' ? '车辆' : '用品'; ?></td>
                        <td>￥<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td class="subtotal">￥<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        <td>
                            <button class="btn btn-danger remove-item">删除</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="total-section">
                总计：<span class="total-price">￥<?php echo number_format($totalPrice, 2); ?></span>
                <button id="checkoutBtn" class="btn btn-primary" style="margin-left: 20px;">结算</button>
            </div>
        <?php endif; ?>
    </div>

    <script>
    $(document).ready(function() {
        // 删除商品
        $('.remove-item').click(function() {
            if (!confirm('确定要删除这个商品吗？')) return;
            
            var row = $(this).closest('tr');
            var cartId = row.data('id');
            
            $.post('cart.php', {
                action: 'remove',
                cart_id: cartId
            }, function(response) {
                if (response.status === 'success') {
                    location.reload();
                }
            }, 'json');
        });

        // 结算
        $('#checkoutBtn').click(function() {
            if (!confirm('确定要结算购物车吗？')) return;
            $.post('cart.php', {
                action: 'checkout'
            }, function(response) {
                alert(response.message);
                if (response.status === 'success') {
                    window.location.href = 'orders.php';
                }
            }, 'json');
        });
    });
    </script>
</body>
</html>