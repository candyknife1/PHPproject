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
        case 'add':
            $productName = trim($_POST['product_name']);
            $price = floatval($_POST['price']);
            $stock = (int)$_POST['stock'];
            
            try {
                $stmt = $db->prepare("INSERT INTO products (product_name, price, stock, seller_id) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$productName, $price, $stock, $sellerId])) {
                    $response = ['status' => 'success', 'message' => '添加成功'];
                }
            } catch (PDOException $e) {
                $response['message'] = '添加失败：' . $e->getMessage();
            }
            break;
            
        case 'update':
            $productId = (int)$_POST['product_id'];
            $productName = trim($_POST['product_name']);
            $price = floatval($_POST['price']);
            $stock = (int)$_POST['stock'];
            
            try {
                $stmt = $db->prepare("UPDATE products SET product_name = ?, price = ?, stock = ? WHERE product_id = ? AND seller_id = ?");
                if ($stmt->execute([$productName, $price, $stock, $productId, $sellerId])) {
                    $response = ['status' => 'success', 'message' => '更新成功'];
                }
            } catch (PDOException $e) {
                $response['message'] = '更新失败：' . $e->getMessage();
            }
            break;
            
        case 'delete':
            $productId = (int)$_POST['product_id'];
            try {
                $stmt = $db->prepare("DELETE FROM products WHERE product_id = ? AND seller_id = ?");
                if ($stmt->execute([$productId, $sellerId])) {
                    $response = ['status' => 'success', 'message' => '删除成功'];
                }
            } catch (PDOException $e) {
                $response['message'] = '删除失败：' . $e->getMessage();
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}

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
        .products-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .products-table th, .products-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .products-table th {
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
    </style>
</head>
<body>
    <div class="container">
        <h2>商品管理</h2>
        
        <div class="add-form">
            <h3>添加新商品</h3>
            <form id="addProductForm">
                <input type="hidden" name="action" value="add">
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
        
        <table class="products-table">
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
                        <button class="btn btn-warning edit-btn">编辑</button>
                        <button class="btn btn-danger delete-btn">删除</button>
                        <button class="btn btn-primary save-btn" style="display:none;">保存</button>
                        <button class="btn btn-warning cancel-btn" style="display:none;">取消</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    $(document).ready(function() {
        // 添加商品
        $('#addProductForm').submit(function(e) {
            e.preventDefault();
            $.post('products.php', $(this).serialize(), function(response) {
                alert(response.message);
                if (response.status === 'success') {
                    location.reload();
                }
            }, 'json');
        });

        // 编辑商品
        $('.edit-btn').click(function() {
            var row = $(this).closest('tr');
            row.find('.product-name, .price, .stock').each(function() {
                var value = $(this).text();
                if ($(this).hasClass('price')) {
                    value = value.replace('￥', '').replace(',', '');
                }
                $(this).html('<input type="text" value="' + value + '">');
            });
            $(this).hide();
            row.find('.delete-btn').hide();
            row.find('.save-btn, .cancel-btn').show();
        });

        // 保存编辑
        $('.save-btn').click(function() {
            var row = $(this).closest('tr');
            var productId = row.data('id');
            var productName = row.find('.product-name input').val();
            var price = row.find('.price input').val();
            var stock = row.find('.stock input').val();

            $.post('products.php', {
                action: 'update',
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
        $('.cancel-btn').click(function() {
            location.reload();
        });

        // 删除商品
        $('.delete-btn').click(function() {
            if (!confirm('确定要删除这个商品吗？')) return;
            
            var productId = $(this).closest('tr').data('id');
            $.post('products.php', {
                action: 'delete',
                product_id: productId
            }, function(response) {
                alert(response.message);
                if (response.status === 'success') {
                    location.reload();
                }
            }, 'json');
        });
    });
    </script>
</body>
</html> 