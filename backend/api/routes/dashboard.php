<?php
function getDashboardStats() {
    try {
        $db = getDB();

        $stmt = $db->query("SELECT COUNT(*) as cnt FROM video");
        $totalVideos = intval($stmt->fetch()['cnt']);

        $stmt = $db->query("SELECT COUNT(*) as cnt FROM video WHERE status = 1");
        $publishedCount = intval($stmt->fetch()['cnt']);

        $stmt = $db->query("SELECT COUNT(*) as cnt FROM video WHERE status = 0");
        $unpublishedCount = intval($stmt->fetch()['cnt']);

        $stmt = $db->query("SELECT COUNT(*) as cnt FROM video_source");
        $totalSources = intval($stmt->fetch()['cnt']);

        success([
            'total_videos' => $totalVideos,
            'published_count' => $publishedCount,
            'unpublished_count' => $unpublishedCount,
            'total_sources' => $totalSources
        ]);

    } catch (Exception $e) {
        error('获取统计数据失败：' . $e->getMessage());
    }
}

function getRecentVideos() {
    $limit = intval($_GET['limit'] ?? 8);
    $limit = min(20, max(1, $limit));

    try {
        $db = getDB();

        $stmt = $db->prepare("
            SELECT id, title, cover_url, status, is_recommend, updated_at
            FROM video
            ORDER BY updated_at DESC
            LIMIT {$limit}
        ");
        $stmt->execute();
        $list = $stmt->fetchAll();

        foreach ($list as &$item) {
            $item['updated_at'] = formatDateTime($item['updated_at']);
            $item['status'] = intval($item['status']);
            $item['is_recommend'] = intval($item['is_recommend']);
        }

        success(['list' => $list]);

    } catch (Exception $e) {
        error('获取最近更新影片失败：' . $e->getMessage());
    }
}

function getRecommendVideos() {
    $limit = intval($_GET['limit'] ?? 8);
    $limit = min(20, max(1, $limit));

    try {
        $db = getDB();

        $stmt = $db->prepare("
            SELECT id, title, cover_url, status, is_recommend, updated_at
            FROM video
            WHERE is_recommend = 1 AND status = 1
            ORDER BY updated_at DESC
            LIMIT {$limit}
        ");
        $stmt->execute();
        $list = $stmt->fetchAll();

        foreach ($list as &$item) {
            $item['updated_at'] = formatDateTime($item['updated_at']);
            $item['status'] = intval($item['status']);
            $item['is_recommend'] = intval($item['is_recommend']);
        }

        success(['list' => $list]);

    } catch (Exception $e) {
        error('获取推荐影片失败：' . $e->getMessage());
    }
}

function handleDashboardRequest($path, $method) {
    if ($method === 'GET' && $path === 'dashboard/stats') {
        getDashboardStats();
    } elseif ($method === 'GET' && $path === 'dashboard/recent') {
        getRecentVideos();
    } elseif ($method === 'GET' && $path === 'dashboard/recommend') {
        getRecommendVideos();
    } else {
        error('接口不存在', 404);
    }
}
