<?php
require_once '../config/database.php';
require_once '../utils/auth.php';
requireSeller();

$db = Database::getInstance()->getConnection();
$sellerId = getCurrentUserId();

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $response = ['status' => 'error', 'message' => '操作失败'];
    
    switch ($_POST['action']) {
        case 'add_car':
            $carName = trim($_POST['car_name']);
            $carType = trim($_POST['car_type']);
            $price = floatval($_POST['price']);
            
            try {
                $stmt = $db->prepare("INSERT INTO cars (car_name, car_type, price, seller_id) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$carName, $carType, $price, $sellerId])) {
                    $response = ['status' => 'success', 'message' => '添加车辆成功'];
                }
            } catch (PDOException $e) {
                $response['message'] = '添加失败：' . $e->getMessage();
            }
            break;
            
        case 'add_product':
            $productName = trim($_POST['product_name']);
            $price = floatval($_POST['price']);
            $stock = (int)$_POST['stock'];
            
            try {
                $stmt = $db->prepare("INSERT INTO products (product_name, price, stock, seller_id) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$productName, $price, $stock, $sellerId])) {
                    $response = ['status' => 'success', 'message' => '添加商品成功'];
                }
            } catch (PDOException $e) {
                $response['message'] = '添加失败：' . $e->getMessage();
            }
            break;
            
        case 'update_car':
            $carId = (int)$_POST['car_id'];
            $carName = trim($_POST['car_name']);
            $carType = trim($_POST['car_type']);
            $price = floatval($_POST['price']);
            $status = $_POST['status'];
            
            try {
                $stmt = $db->prepare("UPDATE cars SET car_name = ?, car_type = ?, price = ?, status = ? WHERE car_id = ? AND seller_id = ?");
                if ($stmt->execute([$carName, $carType, $price, $status, $carId, $sellerId])) {
                    $response = ['status' => 'success', 'message' => '更新车辆成功'];
                }
            } catch (PDOException $e) {
                $response['message'] = '更新失败：' . $e->getMessage();
            }
            break;
            
        case 'update_product':
            $productId = (int)$_POST['product_id'];
            $productName = trim($_POST['product_name']);
            $price = floatval($_POST['price']);
            $stock = (int)$_POST['stock'];
            
            try {
                $stmt = $db->prepare("UPDATE products SET product_name = ?, price = ?, stock = ? WHERE product_id = ? AND seller_id = ?");
                if ($stmt->execute([$productName, $price, $stock, $productId, $sellerId])) {
                    $response = ['status' => 'success', 'message' => '更新商品成功'];
                }
            } catch (PDOException $e) {
                $response['message'] = '更新失败：' . $e->getMessage();
            }
            break;
            
        case 'delete_car':
            $carId = (int)$_POST['car_id'];
            try {
                $stmt = $db->prepare("DELETE FROM cars WHERE car_id = ? AND seller_id = ?");
                if ($stmt->execute([$carId, $sellerId])) {
                    $response = ['status' => 'success', 'message' => '删除车辆成功'];
                }
            } catch (PDOException $e) {
                $response['message'] = '删除失败：' . $e->getMessage();
            }
            break;
            
        case 'delete_product':
            $productId = (int)$_POST['product_id'];
            try {
                $stmt = $db->prepare("DELETE FROM products WHERE product_id = ? AND seller_id = ?");
                if ($stmt->execute([$productId, $sellerId])) {
                    $response = ['status' => 'success', 'message' => '删除商品成功'];
                }
            } catch (PDOException $e) {
                $response['message'] = '删除失败：' . $e->getMessage();
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}

// 获取车辆列表
$stmt = $db->prepare("SELECT * FROM cars WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->execute([$sellerId]);
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取商品列表
$stmt = $db->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->execute([$sellerId]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>商品管理</title>
    <meta charset="utf-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .section {
            margin-bottom: 40px;
        }
        .add-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .data-table th {
            background: #f5f5f5;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        .btn-primary { background: #2196F3; color: white; }
        .btn-danger { background: #f44336; color: white; }
        .btn-warning { background: #ff9800; color: white; }
        .status-在售 { color: #4CAF50; }
        .status-已售 { color: #f44336; }
        .tabs {
            margin-bottom: 20px;
        }
        .tab-btn {
            padding: 10px 20px;
            border: none;
            background: #f5f5f5;
            cursor: pointer;
            margin-right: 10px;
        }
        .tab-btn.active {
            background: #2196F3;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>商品管理</h2>
        
        <div class="tabs">
            <button class="tab-btn active" data-tab="cars">车辆管理</button>
            <button class="tab-btn" data-tab="products">用品管理</button>
        </div>
        
        <!-- 车辆管理部分 -->
        <div id="cars" class="tab-content section active">
            <div class="add-form">
                <h3>添加新车辆</h3>
                <form id="addCarForm">
                    <input type="hidden" name="action" value="add_car">
                    <div class="form-group">
                        <label>车辆名称：</label>
                        <input type="text" name="car_name" required>
                    </div>
                    <div class="form-group">
                        <label>车型：</label>
                        <input type="text" name="car_type" required>
                    </div>
                    <div class="form-group">
                        <label>价格：</label>
                        <input type="number" name="price" step="0.01" required>
                    </div>
                    <button type="submit" class="btn btn-primary">添加车辆</button>
                </form>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>车辆名称</th>
                        <th>车型</th>
                        <th>价格</th>
                        <th>状态</th>
                        <th>添加时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cars as $car): ?>
                    <tr data-id="<?php echo $car['car_id']; ?>">
                        <td class="car-name"><?php echo htmlspecialchars($car['car_name']); ?></td>
                        <td class="car-type"><?php echo htmlspecialchars($car['car_type']); ?></td>
                        <td class="price">￥<?php echo number_format($car['price'], 2); ?></td>
                        <td>
                            <span class="status-<?php echo $car['status']; ?>">
                                <?php echo $car['status']; ?>
                            </span>
                            <select class="status-select" style="display:none;">
                                <option value="在售" <?php echo $car['status']=='在售'?'selected':''; ?>>在售</option>
                                <option value="已售" <?php echo $car['status']=='已售'?'selected':''; ?>>已售</option>
                            </select>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($car['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-warning edit-car">编辑</button>
                            <button class="btn btn-danger delete-car">删除</button>
                            <button class="btn btn-primary save-car" style="display:none;">保存</button>
                            <button class="btn btn-warning cancel-car" style="display:none;">取消</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 用品管理部分 -->
        <div id="products" class="tab-content section">
            <div class="add-form">
                <h3>添加新商品</h3>
                <form id="addProductForm">
                    <input type="hidden" name="action" value="add_product">
                    <div class="form-group">
                        <label>商品名称：</label>
                        <input type="text" name="product_name" required>
                    </div>
                    <div class="form-group">
                        <label>价格：</label>
                        <input type="number" name="price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>库存数量：</label>
                        <input type="number" name="stock" required>
                    </div>
                    <button type="submit" class="btn btn-primary">添加商品</button>
                </form>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>商品名称</th>
                        <th>价格</th>
                        <th>库存</th>
                        <th>添加时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr data-id="<?php echo $product['product_id']; ?>">
                        <td class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td class="price">￥<?php echo number_format($product['price'], 2); ?></td>
                        <td class="stock"><?php echo $product['stock']; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($product['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-warning edit-product">编辑</button>
                            <button class="btn btn-danger delete-product">删除</button>
                            <button class="btn btn-primary save-product" style="display:none;">保存</button>
                            <button class="btn btn-warning cancel-product" style="display:none;">取消</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // 标签页切换
        $('.tab-btn').click(function() {
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.tab-content').removeClass('active');
            $('#' + $(this).data('tab')).addClass('active');
        });

        // 添加车辆
        $('#addCarForm').submit(function(e) {
            e.preventDefault();
            $.post('products_manage.php', $(this).serialize(), function(response) {
                alert(response.message);
                if (response.status === 'success') {
                    location.reload();
                }
            }, 'json');
        });

        // 添加商品
        $('#addProductForm').submit(function(e) {
            e.preventDefault();
            $.post('products_manage.php', $(this).serialize(), function(response) {
                alert(response.message);
                if (response.status === 'success') {
                    location.reload();
                }
            }, 'json');
        });

        // 编辑车辆
        $('.edit-car').click(function() {
            var row = $(this).closest('tr');
            row.find('.car-name, .car-type, .price').each(function() {
                var value = $(this).text();
                if ($(this).hasClass('price')) {
                    value = value.replace('￥', '').replace(',', '');
                }
                $(this).html('<input type="text" value="' + value + '">');
            });
            row.find('.status-select').show();
            row.find('.status-在售, .status-已售').hide();
            $(this).hide();
            row.find('.delete-car').hide();
            row.find('.save-car, .cancel-car').show();
        });

        // 编辑商品
        $('.edit-product').click(function() {
            var row = $(this).closest('tr');
            row.find('.product-name, .price, .stock').each(function() {
                var value = $(this).text();
                if ($(this).hasClass('price')) {
                    value = value.replace('￥', '').replace(',', '');
                }
                $(this).html('<input type="text" value="' + value + '">');
            });
            $(this).hide();
            row.find('.delete-product').hide();
            row.find('.save-product, .cancel-product').show();
        });

        // 保存车辆
        $('.save-car').click(function() {
            var row = $(this).closest('tr');
            var carId = row.data('id');
            var carName = row.find('.car-name input').val();
            var carType = row.find('.car-type input').val();
            var price = row.find('.price input').val();
            var status = row.find('.status-select').val();

            $.post('products_manage.php', {
                action: 'update_car',
                car_id: carId,
                car_name: carName,
                car_type: carType,
                price: price,
                status: status
            }, function(response) {
                alert(response.message);
                if (response.status === 'success') {
                    location.reload();
                }
            }, 'json');
        });

        // 保存商品
        $('.save-product').click(function() {
            var row = $(this).closest('tr');
            var productId = row.data('id');
            var productName = row.find('.product-name input').val();
            var price = row.find('.price input').val();
            var stock = row.find('.stock input').val();

            $.post('products_manage.php', {
                action: 'update_product',
                product_id: productId,
                product_name: productName,
                price: price,
                stock: stock
            }, function(response) {
                alert(response.message);
                if (response.status === 'success') {
                    location.reload();
                }
            }, 'json');
        });

        // 取消编辑
        $('.cancel-car, .cancel-product').click(function() {
            location.reload();
        });

        // 删除车辆
        $('.delete-car').click(function() {
            if (confirm('确定要删除这辆车吗？')) {
                var carId = $(this).closest('tr').data('id');
                $.post('products_manage.php', {
                    action: 'delete_car',
                    car_id: carId
                }, function(response) {
                    alert(response.message);
                    if (response.status === 'success') {
                        location.reload();
                    }
                }, 'json');
            }
        });

        // 删除商品
        $('.delete-product').click(function() {
            if (confirm('确定要删除这个商品吗？')) {
                var productId = $(this).closest('tr').data('id');
                $.post('products_manage.php', {
                    action: 'delete_product',
                    product_id: productId
                }, function(response) {
                    alert(response.message);
                    if (response.status === 'success') {
                        location.reload();
                    }
                }, 'json');
            }
        });
    });
    </script>
</body>
</html> 