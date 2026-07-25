<?php
/**
 * 迁移脚本：为 video 表新增 is_recommend 字段（推荐标记）
 *
 * 适用于已存在数据的库，init.sql 已包含该字段，本脚本用于线上库增量升级。
 *
 * 使用方法：
 * php migrate_recommend.php
 */

require_once __DIR__ . '/../config/database.php';

echo "开始迁移：为 video 表新增 is_recommend 字段...\n\n";

try {
    $db = getDB();

    // 检查字段是否已存在
    $stmt = $db->query("SHOW COLUMNS FROM video LIKE 'is_recommend'");
    if ($stmt->fetch()) {
        echo "字段 is_recommend 已存在，跳过。\n";
        exit(0);
    }

    // 新增字段
    $db->exec("
        ALTER TABLE video
        ADD COLUMN is_recommend TINYINT NOT NULL DEFAULT 0 COMMENT '1推荐 0不推荐' AFTER status,
        ADD INDEX idx_recommend (is_recommend)
    ");

    echo "字段 is_recommend 新增成功。\n";

} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}
