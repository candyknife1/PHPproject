<?php
require_once dirname(__DIR__) . '/utils/auth.php';
if (defined('IS_AJAX')) {
    return;
}
?>
<nav style="background: #f8f9fa; padding: 10px; margin-bottom: 20px;">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <a href="<?php echo getBaseUrl(); ?>index.php" style="text-decoration: none; color: #333; margin-right: 15px;">首页</a>
        </div>
        <div>
            <?php if (isLoggedIn()): ?>
                <span style="margin-right: 15px;">欢迎, <?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                <a href="<?php echo getBaseUrl(); ?>user/cart.php" style="text-decoration: none; color: #333; margin-right: 15px;">购物车</a>
                <a href="<?php echo getBaseUrl(); ?>user/orders.php" style="text-decoration: none; color: #333; margin-right: 15px;">我的订单</a>
                <a href="<?php echo getBaseUrl(); ?>user/profile.php" style="text-decoration: none; color: #333; margin-right: 15px;">个人信息</a>
                <a href="<?php echo getBaseUrl(); ?>logout.php" style="text-decoration: none; color: #333;">退出</a>
            <?php else: ?>
                <a href="<?php echo getBaseUrl(); ?>login.php" style="text-decoration: none; color: #333; margin-right: 15px;">登录</a>
                <a href="<?php echo getBaseUrl(); ?>register.php" style="text-decoration: none; color: #333;">注册</a>
            <?php endif; ?>
        </div>
    </div>
</nav>