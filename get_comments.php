<?php
require_once 'config/database.php';

if (isset($_GET['item_type']) && isset($_GET['item_id'])) {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT c.*, u.username 
            FROM comments c 
            LEFT JOIN users u ON c.user_id = u.user_id 
            WHERE c.item_type = ? AND c.item_id = ? 
            ORDER BY c.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_GET['item_type'], (int)$_GET['item_id']]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($comments);
}
?> 