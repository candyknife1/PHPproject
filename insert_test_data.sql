-- 插入用户数据（包含普通用户和商家）
INSERT INTO users (username, password, phone, email, age, signature, address, role) VALUES
('user1', '111', '13800138001', 'user1@example.com', 25, '我是买家1', '北京市朝阳区', 'user'),
('user2', '222', '13800138002', 'user2@example.com', 30, '我是买家2', '上海市浦东新区', 'user'),
('seller1', '333', '13900139001', 'seller1@example.com', 35, '专业汽车经销商', null, 'seller'),
('seller2', '444', '13900139002', 'seller2@example.com', 40, '专业汽车用品销售', null, 'seller');

-- 插入车辆数据
INSERT INTO cars (car_type, car_name, price, status, seller_id) VALUES
('SUV', '本田CR-V', 220000.00, '在售', 3),
('轿车', '丰田凯美瑞', 180000.00, '在售', 3),
('SUV', '奥迪Q5', 350000.00, '在售', 4),
('轿车', '宝马3系', 300000.00, '在售', 4),
('SUV', '大众途观', 200000.00, '已售', 3),
('轿车', '奔驰C级', 320000.00, '在售', 3);

-- 插入汽车用品数据
INSERT INTO products (product_name, price, stock, seller_id) VALUES
('行车记录仪', 299.00, 100, 3),
('车载充电器', 59.00, 200, 3),
('汽车座垫', 199.00, 50, 4),
('车载香水', 39.00, 150, 4),
('车载吸尘器', 159.00, 80, 3),
('汽车脚垫', 129.00, 100, 4),
('车载蓝牙', 89.00, 120, 3),
('汽车清洁套装', 159.00, 60, 4);

-- 插入一些订单数据
INSERT INTO orders (user_id, seller_id, item_type, item_id, quantity, total_price, status) VALUES
(1, 3, 'car', 5, 1, 200000.00, '已发货'),
(1, 3, 'product', 1, 2, 598.00, '已发货'),
(2, 4, 'product', 3, 1, 199.00, '未发货'),
(2, 3, 'product', 2, 3, 177.00, '已发货'),
(1, 4, 'product', 4, 2, 78.00, '未发货');

-- 插入一些评论数据
INSERT INTO comments (user_id, item_type, item_id, content) VALUES
(1, 'car', 1, '这款车性价比很高'),
(2, 'car', 1, '外观很漂亮，动力也不错'),
(1, 'car', 3, '高端大气，就是价格有点贵'),
(2, 'product', 1, '记录仪清晰度很高'),
(1, 'product', 3, '座垫很舒服，材质不错'),
(2, 'product', 2, '充电快，很实用');

-- 插入一些购物车数据
INSERT INTO cart (user_id, item_type, item_id, quantity) VALUES
(1, 'car', 2, 1),
(1, 'product', 5, 2),
(2, 'car', 3, 1),
(2, 'product', 6, 1); 