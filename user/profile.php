<?php
 // 检查是否是AJAX请求
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    define('IS_AJAX', true);
}

require_once '../config/database.php';
require_once '../utils/auth.php';
require_once '../includes/nav.php';

requireLogin();

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();

// 处理个人信息更新
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $response = ['status' => 'error', 'message' => '更新失败'];
    
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $age = (int)$_POST['age'];
    $signature = trim($_POST['signature']);
    $address = trim($_POST['address']);
    
    try {
        $stmt = $db->prepare("UPDATE users SET phone = ?, email = ?, age = ?, signature = ?, address = ? WHERE user_id = ?");
        if ($stmt->execute([$phone, $email, $age, $signature, $address, $userId])) {
            $response = ['status' => 'success', 'message' => '更新成功'];
        }
    } catch (PDOException $e) {
        $response['message'] = '更新失败：' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// 获取用户信息
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>个人信息</title>
    <meta charset="utf-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .profile-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .btn {
            background: #2196F3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background: #1976D2;
        }
        .username-display {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        .role-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            color: white;
            background: #4CAF50;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-card">
            <div class="username-display">
                <?php echo htmlspecialchars($user['username']); ?>
                <span class="role-badge"><?php echo $user['role'] == 'user' ? '普通用户' : '商家'; ?></span>
            </div>
            
            <form id="profileForm">
                <div class="form-group">
                    <label>手机号码：</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label>邮箱：</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label>年龄：</label>
                    <input type="number" name="age" value="<?php echo $user['age']; ?>" min="1" max="150">
                </div>
                
                <div class="form-group">
                    <label>个性签名：</label>
                    <textarea name="signature"><?php echo htmlspecialchars($user['signature']); ?></textarea>
                </div>
                
                <?php if ($user['role'] == 'user'): ?>
                <div class="form-group">
                    <label>收货地址：</label>
                    <textarea name="address"><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn">保存修改</button>
            </form>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#profileForm').submit(function(e) {
            e.preventDefault();
            $.post('profile.php', $(this).serialize(), function(response) {
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