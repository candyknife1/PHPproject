<?php
require_once 'config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $captcha = trim($_POST['captcha']);
    $role = $_POST['role'];
    
    if (strtolower($captcha) != $_SESSION['captcha']) {
        echo json_encode(['status' => 'error', 'message' => '验证码错误']);
        exit;
    }
    
    // 密码验证（六位包含数字字母）
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/', $password)) {
        echo json_encode(['status' => 'error', 'message' => '密码必须包含字母和数字，且长度至少为6位']);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // 检查用户名是否已存在
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'exists' => 'true']);
        exit;
    }
    
    // 注册新用户
    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $password, $role])) {
        echo json_encode(['status' => 'success', 'message' => '注册成功']);
    } else {
        echo json_encode(['status' => 'error', 'message' => '注册失败']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>用户注册</title>
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
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .captcha-img {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>用户注册</h2>
        <form id="registerForm" method="post">
            <div class="form-group">
                <label>用户名：</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label>密码：</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>验证码：</label>
                <input type="text" name="captcha" required>
                <img src="utils/captcha.php" class="captcha-img" onclick="this.src='utils/captcha.php?'+Math.random()">
            </div>
            <div class="form-group">
                <label>注册身份：</label>
                <select name="role" required>
                    <option value="user">普通用户</option>
                    <option value="seller">商家</option>
                </select>
            </div>
            <button type="submit">注册</button>
        </form>
    </div>

    <script>
    $(document).ready(function() {
        // 用户名失焦检查
        $('#username').blur(function() {
            var username = $(this).val();
            if(username.trim() !== '') {
                $.post('check_username.php', {
                    username: username
                }, function(data) {
                    if (data.exists) {
                        alert('用户名已存在');
                    }
                }, 'json');
            }
        });

        // 表单提交
        $('#registerForm').submit(function(e) {
            e.preventDefault();
            $.post('register.php', $(this).serialize(), function(data) {
                alert(data.message);
                if (data.status == 'success') {
                    window.location.href = 'login.php';
                }
            }, 'json');
        });
    });
    </script>
</body>
</html> 