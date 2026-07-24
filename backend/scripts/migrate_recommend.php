<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = getDB();

    $stmt = $db->query("SHOW COLUMNS FROM video LIKE 'is_recommend'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "is_recommend 列已存在，无需迁移。\n";
    } else {
        $db->exec("ALTER TABLE video ADD COLUMN is_recommend TINYINT NOT NULL DEFAULT 0 COMMENT '1推荐 0不推荐' AFTER status");
        $db->exec("ALTER TABLE video ADD INDEX idx_recommend (is_recommend, status)");
        echo "迁移成功：已添加 is_recommend 列和索引。\n";
    }
} catch (Exception $e) {
    echo "迁移失败：" . $e->getMessage() . "\n";
    exit(1);
}
