<?php
require_once 'config/database.php';
session_start();

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']) ? (int)$_POST['remember'] : 0;
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $password == $user['password']) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];  // 保存用户角色
        
        // 处理记住密码
        if ($remember > 0) {
            $expire = time();
            switch($remember) {
                case 1: // 一小时
                    $expire += 3600;
                    break;
                case 2: // 一天
                    $expire += 86400;
                    break;
                case 3: // 一周
                    $expire += 604800;
                    break;
            }
            setcookie('remember_user', $user['username'], $expire, '/');
            setcookie('remember_token', password_hash($user['password'], PASSWORD_DEFAULT), $expire, '/');
        }
        // 根据角色重定向到不同页面
        $redirectUrl = $user['role'] == 'seller' ? 'seller/dashboard.php' : 'index.php';
        echo json_encode(['status' => 'success', 'message' => '登录成功', 'redirect' => $redirectUrl]);
    } else {
        echo json_encode(['status' => 'error', 'message' => '用户名或密码错误']);
    }
    exit;
}

// 检查是否已经记住密码
$remembered_username = isset($_COOKIE['remember_user']) ? $_COOKIE['remember_user'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>用户登录</title>
    <meta charset="utf-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .container {
            width: 400px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .remember-options {
            margin-top: 10px;
        }
        .remember-options label {
            margin-right: 10px;
        }
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .btn-submit:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div style="position: fixed; top: 10px; left: 10px; color: #666;">
        信管B221李智文
    </div>
    
    <div class="container">
        <h2>用户登录</h2>
        <form id="loginForm" method="post">
            <div class="form-group">
                <label>用户名：</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($remembered_username); ?>" required>
            </div>
            <div class="form-group">
                <label>密码：</label>
                <input type="password" name="password" required>
            </div>
            <div class="remember-options">
                <label>记住密码：</label>
                <label><input type="radio" name="remember" value="1"> 一小时</label>
                <label><input type="radio" name="remember" value="2"> 一天</label>
                <label><input type="radio" name="remember" value="3"> 一周</label>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-submit">登录</button>
                <a href="register.php" style="margin-left: 10px;">没有账号？去注册</a>
            </div>
        </form>
    </div>

    <script>
    $(document).ready(function() {
        $('#loginForm').submit(function(e) {
            e.preventDefault();
            $.post('login.php', $(this).serialize(), function(data) {
                if (data.status == 'success') {
                    alert(data.message);
                    window.location.href = data.redirect;
                } else {
                    alert(data.message);
                }
            }, 'json');
        });
    });
    </script>
</body>
</html> 