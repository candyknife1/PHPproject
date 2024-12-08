<?php
require_once '../config/database.php';
require_once '../utils/auth.php';
requireSeller();

$db = Database::getInstance()->getConnection();
$sellerId = getCurrentUserId();

// 处理添加车辆请求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $response = ['status' => 'error', 'message' => '操作失败'];
    
    switch ($_POST['action']) {
        case 'add':
            $carName = trim($_POST['car_name']);
            $carType = trim($_POST['car_type']);
            $price = floatval($_POST['price']);
            
            try {
                $stmt = $db->prepare("INSERT INTO cars (car_name, car_type, price, seller_id) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$carName, $carType, $price, $sellerId])) {
                    $response = ['status' => 'success', 'message' => '添加成功'];
                }
            } catch (PDOException $e) {
                $response['message'] = '添加失败：' . $e->getMessage();
            }
            break;
            
        case 'update':
            $carId = (int)$_POST['car_id'];
            $carName = trim($_POST['car_name']);
            $carType = trim($_POST['car_type']);
            $price = floatval($_POST['price']);
            
            try {
                $stmt = $db->prepare("UPDATE cars SET car_name = ?, car_type = ?, price = ? WHERE car_id = ? AND seller_id = ?");
                if ($stmt->execute([$carName, $carType, $price, $carId, $sellerId])) {
                    $response = ['status' => 'success', 'message' => '更新成功'];
                }
            } catch (PDOException $e) {
                $response['message'] = '更新失败：' . $e->getMessage();
            }
            break;
            
        case 'delete':
            $carId = (int)$_POST['car_id'];
            try {
                $stmt = $db->prepare("DELETE FROM cars WHERE car_id = ? AND seller_id = ?");
                if ($stmt->execute([$carId, $sellerId])) {
                    $response = ['status' => 'success', 'message' => '删除成功'];
                }
            } catch (PDOException $e) {
                $response['message'] = '删除失败：' . $e->getMessage();
            }
            break;
            
        case 'change_status':
            $carId = (int)$_POST['car_id'];
            $status = $_POST['status'];
            try {
                $stmt = $db->prepare("UPDATE cars SET status = ? WHERE car_id = ? AND seller_id = ?");
                if ($stmt->execute([$status, $carId, $sellerId])) {
                    $response = ['status' => 'success', 'message' => '状态更新成功'];
                }
            } catch (PDOException $e) {
                $response['message'] = '状态更新失败：' . $e->getMessage();
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>车辆管理</title>
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
        .cars-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cars-table th, .cars-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .cars-table th {
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
        .btn-danger {
            background: #f44336;
            color: white;
        }
        .btn-warning {
            background: #ff9800;
            color: white;
        }
        .status-在售 {
            color: #4CAF50;
        }
        .status-已售 {
            color: #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>车辆管理</h2>
        
        <div class="add-form">
            <h3>添加新车辆</h3>
            <form id="addCarForm">
                <input type="hidden" name="action" value="add">
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
        
        <table class="cars-table">
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
        // 添加车辆
        $('#addCarForm').submit(function(e) {
            e.preventDefault();
            $.post('cars.php', $(this).serialize(), function(response) {
                alert(response.message);
                if (response.status === 'success') {
                    location.reload();
                }
            }, 'json');
        });

        // 编辑车辆
        $('.edit-btn').click(function() {
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
            row.find('.delete-btn').hide();
            row.find('.save-btn, .cancel-btn').show();
        });

        // 保存编辑
        $('.save-btn').click(function() {
            var row = $(this).closest('tr');
            var carId = row.data('id');
            var carName = row.find('.car-name input').val();
            var carType = row.find('.car-type input').val();
            var price = row.find('.price input').val();
            var status = row.find('.status-select').val();

            $.post('cars.php', {
                action: 'update',
                car_id: carId,
                car_name: carName,
                car_type: carType,
                price: price
            }, function(response) {
                if (response.status === 'success') {
                    // 更新状态
                    $.post('cars.php', {
                        action: 'change_status',
                        car_id: carId,
                        status: status
                    }, function(response) {
                        if (response.status === 'success') {
                            location.reload();
                        }
                    }, 'json');
                }
                alert(response.message);
            }, 'json');
        });

        // 取消编辑
        $('.cancel-btn').click(function() {
            location.reload();
        });

        // 删除车辆
        $('.delete-btn').click(function() {
            if (confirm('确定要删除这辆车吗？')) {
                var carId = $(this).closest('tr').data('id');
                $.post('cars.php', {
                    action: 'delete',
                    car_id: carId
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