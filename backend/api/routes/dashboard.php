<?php
// 仪表盘 - 统计数据（影片总数、上架数、下架数、播放源总数）
function getDashboardStats() {
    try {
        $db = getDB();

        $videoTotal = intval($db->query("SELECT COUNT(*) AS c FROM video")->fetch()['c']);
        $onlineTotal = intval($db->query("SELECT COUNT(*) AS c FROM video WHERE status = 1")->fetch()['c']);
        $offlineTotal = intval($db->query("SELECT COUNT(*) AS c FROM video WHERE status = 0")->fetch()['c']);
        $sourceTotal = intval($db->query("SELECT COUNT(*) AS c FROM video_source")->fetch()['c']);

        success([
            'video_total' => $videoTotal,
            'online_total' => $onlineTotal,
            'offline_total' => $offlineTotal,
            'source_total' => $sourceTotal
        ]);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

// 仪表盘 - 最近更新的影片
function getDashboardRecent() {
    // 限制条数（1-20），经校验后安全直接插值到 LIMIT
    $limit = intval($_GET['limit'] ?? 5);
    $limit = min(20, max(1, $limit));

    try {
        $db = getDB();
        $stmt = $db->query("
            SELECT id, title, status, is_recommend, updated_at
            FROM video
            ORDER BY updated_at DESC, id DESC
            LIMIT {$limit}
        ");
        $list = $stmt->fetchAll();

        foreach ($list as &$item) {
            $item['status'] = intval($item['status']);
            $item['is_recommend'] = intval($item['is_recommend']);
            $item['updated_at'] = formatDateTime($item['updated_at']);
        }

        success(['list' => $list]);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

// 仪表盘 - 推荐影片（推荐且已上架）
function getDashboardRecommend() {
    $limit = intval($_GET['limit'] ?? 8);
    $limit = min(20, max(1, $limit));

    try {
        $db = getDB();
        $stmt = $db->query("
            SELECT id, title, cover_url, updated_at
            FROM video
            WHERE is_recommend = 1 AND status = 1
            ORDER BY updated_at DESC, id DESC
            LIMIT {$limit}
        ");
        $list = $stmt->fetchAll();

        foreach ($list as &$item) {
            $item['updated_at'] = formatDateTime($item['updated_at']);
        }

        success(['list' => $list]);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

// 处理仪表盘请求
function handleDashboardRequest($path, $method) {
    if ($method === 'GET' && $path === 'dashboard/stats') {
        getDashboardStats();
    } elseif ($method === 'GET' && $path === 'dashboard/recent') {
        getDashboardRecent();
    } elseif ($method === 'GET' && $path === 'dashboard/recommend') {
        getDashboardRecommend();
    } else {
        error('接口不存在', 404);
    }
}
