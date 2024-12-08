<?php
// 检查是否是AJAX请求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    define('IS_AJAX', true);
}

require_once 'config/database.php';
require_once 'utils/auth.php';
require_once 'includes/nav.php';

$db = Database::getInstance()->getConnection();

// 获取商品评论
function getComments($itemId, $itemType) {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT c.*, u.username 
            FROM comments c 
            LEFT JOIN users u ON c.user_id = u.user_id 
            WHERE c.item_type = ? AND c.item_id = ? 
            ORDER BY c.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$itemType, $itemId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 处理添加到购物车和评论的请求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    if (!isLoggedIn()) {
        echo json_encode(['status' => 'error', 'message' => '请先登录']);
        exit;
    }

    $response = ['status' => 'error', 'message' => '操作失败'];
    $userId = getCurrentUserId();

    if ($_POST['action'] == 'add_to_cart') {
        $itemType = $_POST['item_type'];
        $itemId = (int)$_POST['item_id'];
        $quantity = (int)$_POST['quantity'];

        try {
            // 检查购物车是否已有该商品
            $stmt = $db->prepare("SELECT cart_id FROM cart WHERE user_id = ? AND item_type = ? AND item_id = ?");
            $stmt->execute([$userId, $itemType, $itemId]);

            $existingItem = $stmt->fetch();
            if (!$existingItem) {
                $stmt = $db->prepare("INSERT INTO cart (user_id, item_type, item_id, quantity) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $itemType, $itemId, $quantity]);
                $response = ['status' => 'success', 'message' => '已添加到购物车'];
            } else {
                $response = ['status' => 'success', 'message' => '该商品已在购物车中'];
            }
        } catch (PDOException $e) {
            $response['message'] = '添加失败：' . $e->getMessage();
        }
    } elseif ($_POST['action'] == 'add_comment') {
        $itemType = $_POST['item_type'];
        $itemId = (int)$_POST['item_id'];
        $content = trim($_POST['content']);
        try {
            $stmt = $db->prepare("INSERT INTO comments (user_id, item_type, item_id, content) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$userId, $itemType, $itemId, $content])) {
                $response = ['status' => 'success', 'message' => '评论成功'];
            }
        } catch (PDOException $e) {
            $response['message'] = '评论失败：' . $e->getMessage();
        }
    }

    echo json_encode($response);
    exit;
}

// 获取热门车辆
function getHotCars() {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT c.*, u.username as seller_name, COUNT(o.order_id) as sales_count
            FROM cars c 
            LEFT JOIN users u ON c.seller_id = u.user_id
            LEFT JOIN orders o ON c.car_id = o.item_id AND o.item_type = 'car'
            WHERE c.status = '在售'
            GROUP BY c.car_id
            ORDER BY sales_count DESC, c.created_at DESC
            LIMIT 4";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// 获取热门商品
function getHotProducts() {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT p.*, u.username as seller_name, COUNT(o.order_id) as sales_count
            FROM products p
            LEFT JOIN users u ON p.seller_id = u.user_id
            LEFT JOIN orders o ON p.product_id = o.item_id AND o.item_type = 'product'
            WHERE p.stock > 0
            GROUP BY p.product_id
            ORDER BY sales_count DESC, p.created_at DESC
            LIMIT 4";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$hotCars = getHotCars();
$hotProducts = getHotProducts();
?>

<!DOCTYPE html>
<html>
<head>
    <title>汽车用品销售系统</title>
    <meta charset="utf-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .section {
            margin-bottom: 40px;
        }
        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2196F3;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .card p {
            margin: 5px 0;
            color: #666;
        }
        .price {
            color: #f44336;
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
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
        .seller {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
        .banner {
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-bottom: 40px;
            border-radius: 8px;
        }
        .banner h1 {
            margin: 0;
            font-size: 32px;
        }
        .banner p {
            margin: 10px 0 0 0;
            opacity: 0.8;
        }
        /* 添加模态框样式 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            position: relative;
            background: white;
            width: 90%;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .close-modal {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .comments-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .comment {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .comment-user {
            font-weight: bold;
            color: #333;
        }
        .comment-time {
            font-size: 12px;
            color: #999;
        }
        .comment-content {
            margin-top: 5px;
            color: #666;
        }
        .quantity-input {
            width: 60px;
            padding: 5px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div style="position: fixed; top: 10px; left: 10px; color: #666;">
        信管B221李智文
    </div>

    <div class="container">
        <div class="banner">
            <h1>欢迎来到汽车用品销售系统</h1>
            <p>为您提供优质的汽车和汽车用品</p>
        </div>

        <div class="section">
            <h2 class="section-title">热门车辆</h2>
            <div class="grid">
                <?php foreach ($hotCars as $car): ?>
                <div class="card">
                    <h3><?php echo $car['car_name']; ?></h3>
                    <p>车型：<?php echo $car['car_type']; ?></p>
                    <div class="price">￥<?php echo number_format($car['price'], 2); ?></div>
                    <div class="seller">卖家：<?php echo $car['seller_name']; ?></div>
                    <button class="btn view-detail"
                            data-type="car"
                            data-id="<?php echo $car['car_id']; ?>"
                            data-name="<?php echo $car['car_name']; ?>"
                            data-cartype="<?php echo $car['car_type']; ?>"
                            data-price="<?php echo $car['price']; ?>"
                            data-seller="<?php echo $car['seller_name']; ?>">
                        查看详情
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">热门用品</h2>
            <div class="grid">
                <?php foreach ($hotProducts as $product): ?>
                <div class="card">
                    <h3><?php echo $product['product_name']; ?></h3>
                    <div class="price">￥<?php echo number_format($product['price'], 2); ?></div>
                    <p>库存：<?php echo $product['stock']; ?></p>
                    <div class="seller">卖家：<?php echo $product['seller_name']; ?></div>
                    <button class="btn view-detail"
                            data-type="product"
                            data-id="<?php echo $product['product_id']; ?>"
                            data-name="<?php echo $product['product_name']; ?>"
                            data-price="<?php echo $product['price']; ?>"
                            data-stock="<?php echo $product['stock']; ?>"
                            data-seller="<?php echo $product['seller_name']; ?>">
                        查看详情
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 添加模态框 -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="modalContent"></div>
            <div class="comments-section">
                <h3>评论</h3>
                <div id="commentsList"></div>
                <?php if (isLoggedIn()): ?>
                <div class="comment-form" style="margin-top: 20px;">
                    <textarea id="commentInput" placeholder="写下您的评论..." style="width: 100%; height: 80px;"></textarea>
                    <button id="submitComment" class="btn" style="margin-top: 10px;">提交评论</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // 查看详情
        $('.view-detail').click(function() {
            var type = $(this).data('type');
            var id = $(this).data('id');
            var content = '';

            if (type === 'car') {
                content = `
                    <h2>${$(this).data('name')}</h2>
                    <p>车型：${$(this).data('cartype')}</p>
                    <p>价格：￥${$(this).data('price')}</p>
                    <p>卖家：${$(this).data('seller')}</p>
                    <div style="margin-top: 20px;">
                        <input type="number" class="quantity-input" value="1" min="1" max="1">
                        <button class="btn add-to-cart" data-type="car" data-id="${id}">加入购物车</button>
                    </div>
                `;
            } else {
                content = `
                    <h2>${$(this).data('name')}</h2>
                    <p>价格：￥${$(this).data('price')}</p>
                    <p>库存：${$(this).data('stock')}</p>
                    <p>卖家：${$(this).data('seller')}</p>
                    <div style="margin-top: 20px;">
                        <input type="number" class="quantity-input" value="1" min="1" max="${$(this).data('stock')}">
                        <button class="btn add-to-cart" data-type="product" data-id="${id}">加入购物车</button>
                    </div>
                `;
            }

            $('#modalContent').html(content);
            loadComments(type, id);
            $('#detailModal').show();
        });

        // 关闭模态框
        $('.close-modal').click(function() {
            $('#detailModal').hide();
        });

        // 加入购物车
        $(document).on('click', '.add-to-cart', function() {
            // 如果未登录，提示用户登录
            <?php if (!isLoggedIn()): ?>
                alert('请先登录后再添加商品到购物车');
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            var type = $(this).data('type');
            var id = $(this).data('id');
            var quantity = $(this).siblings('.quantity-input').val();

            $.post('index.php', {
                action: 'add_to_cart',
                item_type: type,
                item_id: id,
                quantity: quantity
            }, function(response) {
                if (response.status === 'success') {
                    if (confirm('添加成功！是否立即查看购物车？')) {
                        window.location.href = 'user/cart.php';
                    } else {
                        $('#detailModal').hide();
                    }
                } else {
                    alert(response.message);
                }
            }, 'json');
        });

        // 提交评论
        $('#submitComment').click(function() {
            var content = $('#commentInput').val();
            var type = $('.add-to-cart').data('type');
            var id = $('.add-to-cart').data('id');
            $.post('index.php', {
                action: 'add_comment',
                item_type: type,
                item_id: id,
                content: content
            }, function(response) {
                if (response.status === 'success') {
                    $('#commentInput').val('');
                    loadComments(type, id);
                    alert('评论成功！');
                } else {
                    alert(response.message);
                }
            }, 'json');
        });

        // 加载评论
        function loadComments(type, id) {
            $.get('get_comments.php', {
                item_type: type,
                item_id: id
            }, function(comments) {
                var html = '';
                comments.forEach(function(comment) {
                    html += `
                        <div class="comment">
                            <div class="comment-user">${comment.username}</div>
                            <div class="comment-time">${comment.created_at}</div>
                            <div class="comment-content">${comment.content}</div>
                        </div>
                    `;
                });
                $('#commentsList').html(html);
            }, 'json');
        }
    });
    </script>
</body>
</html>