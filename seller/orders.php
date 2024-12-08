<?php
require_once '../config/database.php';
require_once '../utils/auth.php';
requireSeller();

$db = Database::getInstance()->getConnection();
$sellerId = getCurrentUserId();

// 处理发货请求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $response = ['status' => 'error', 'message' => '操作失败'];
    
    if ($_POST['action'] == 'ship') {
        $orderId = (int)$_POST['order_id'];
        try {
            $stmt = $db->prepare("UPDATE orders SET status = '已发货' WHERE order_id = ? AND seller_id = ?");
            if ($stmt->execute([$orderId, $sellerId])) {
                $response = ['status' => 'success', 'message' => '发货成功'];
            }
        } catch (PDOException $e) {
            $response['message'] = '发货失败：' . $e->getMessage();
        }
    }
    echo json_encode($response);
    exit;
}

// 获取订单列表
function getOrders($sellerId) {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT o.*, u.username as buyer_name,
            CASE 
                WHEN o.item_type = 'car' THEN c.car_name
                WHEN o.item_type = 'product' THEN p.product_name
            END as item_name
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
            LEFT JOIN cars c ON o.item_type = 'car' AND o.item_id = c.car_id
            LEFT JOIN products p ON o.item_type = 'product' AND o.item_id = p.product_id
            WHERE o.seller_id = ?
            ORDER BY o.created_at DESC";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$sellerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$orders = getOrders($sellerId);
?>

<!DOCTYPE html>
<html>
<head>
    <title>订单管理</title>
    <meta charset="utf-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .orders-table th, .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .orders-table th {
            background: #f5f5f5;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-primary {
            background: #2196F3;
            color: white;
        }
        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .status-未发货 {
            color: #f44336;
        }
        .status-已发货 {
            color: #4CAF50;
        }
        .filter-section {
            margin-bottom: 20px;
        }
        .filter-section select {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>订单管理</h2>
        
        <div class="filter-section">
            <select id="statusFilter">
                <option value="">全部状态</option>
                <option value="未发货">未发货</option>
                <option value="已发货">已发货</option>
            </select>
            <select id="typeFilter">
                <option value="">全部类型</option>
                <option value="car">车辆</option>
                <option value="product">用品</option>
            </select>
        </div>
        
        <table class="orders-table">
            <thead>
                <tr>
                    <th>订单号</th>
                    <th>买家</th>
                    <th>商品类型</th>
                    <th>商品名称</th>
                    <th>数量</th>
                    <th>总价</th>
                    <th>状态</th>
                    <th>下单时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr class="order-row" 
                    data-status="<?php echo $order['status']; ?>"
                    data-type="<?php echo $order['item_type']; ?>">
                    <td><?php echo $order['order_id']; ?></td>
                    <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                    <td><?php echo $order['item_type'] == 'car' ? '车辆' : '用品'; ?></td>
                    <td><?php echo htmlspecialchars($order['item_name']); ?></td>
                    <td><?php echo $order['quantity']; ?></td>
                    <td>￥<?php echo number_format($order['total_price'], 2); ?></td>
                    <td class="status-<?php echo $order['status']; ?>">
                        <?php echo $order['status']; ?>
                    </td>
                    <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                    <td>
                        <button class="btn btn-primary ship-btn" 
                                data-id="<?php echo $order['order_id']; ?>"
                                <?php echo $order['status'] == '已发货' ? 'disabled' : ''; ?>>
                            发货
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    $(document).ready(function() {
        // 发货操作
        $('.ship-btn').click(function() {
            if (!confirm('确定要发货吗？')) return;
            var btn = $(this);
            var orderId = btn.data('id');
            $.post('orders.php', {
                action: 'ship',
                order_id: orderId
            }, function(response) {
                alert(response.message);
                if (response.status === 'success') {
                    location.reload();
                }
            }, 'json');
        });

        // 订单筛选
        function filterOrders() {
            var statusFilter = $('#statusFilter').val();
            var typeFilter = $('#typeFilter').val();
            
            $('.order-row').each(function() {
                var row = $(this);
                var status = row.data('status');
                var type = row.data('type');
                var showStatus = !statusFilter || status === statusFilter;
                var showType = !typeFilter || type === typeFilter;
                
                row.toggle(showStatus && showType);
            });
        }

        $('#statusFilter, #typeFilter').change(filterOrders);
    });
    </script>
</body>
</html> 