<?php
define('MAX_RECOMMENDED_PUBLISHED', 8);

function countRecommendedPublished($db, $excludeId = null) {
    if ($excludeId !== null) {
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM video WHERE is_recommended = 1 AND status = 1 AND id != ?");
        $stmt->execute([$excludeId]);
    } else {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM video WHERE is_recommended = 1 AND status = 1");
    }
    return intval($stmt->fetch()['cnt']);
}

function ensureRecommendedColumn($db) {
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'video' AND COLUMN_NAME = 'is_recommended'");
    $stmt->execute([DB_NAME]);
    $exists = intval($stmt->fetch()['cnt']) > 0;
    if (!$exists) {
        $db->exec("ALTER TABLE video ADD COLUMN is_recommended TINYINT NOT NULL DEFAULT 0 COMMENT '是否推荐 1是 0否' AFTER status");
    }
}

function getVideoList() {
    $page = intval($_GET['page'] ?? 1);
    $pageSize = intval($_GET['page_size'] ?? 10);
    $status = $_GET['status'] ?? '';
    $keyword = $_GET['keyword'] ?? '';
    $hasSource = $_GET['has_source'] ?? '';

    $page = max(1, $page);
    $pageSize = min(100, max(1, $pageSize));
    $offset = ($page - 1) * $pageSize;

    try {
        $db = getDB();
        ensureRecommendedColumn($db);

        $where = [];
        $params = [];

        if ($status !== '') {
            $where[] = "v.status = ?";
            $params[] = $status;
        }

        if ($keyword !== '') {
            $where[] = "v.title LIKE ?";
            $params[] = "%{$keyword}%";
        }

        if ($hasSource === '1') {
            $where[] = "EXISTS (SELECT 1 FROM video_source vs WHERE vs.video_id = v.id)";
        } elseif ($hasSource === '0') {
            $where[] = "NOT EXISTS (SELECT 1 FROM video_source vs WHERE vs.video_id = v.id)";
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM video v {$whereClause}");
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $stmt = $db->prepare("
            SELECT v.id, v.title, v.cover_url, v.description, v.status, v.is_recommended,
                   v.created_at, v.updated_at,
                   (SELECT COUNT(*) FROM video_source vs WHERE vs.video_id = v.id) AS source_count
            FROM video v
            {$whereClause}
            ORDER BY v.id DESC
            LIMIT {$offset}, {$pageSize}
        ");
        $stmt->execute($params);
        $list = $stmt->fetchAll();

        foreach ($list as &$item) {
            $item['created_at'] = formatDateTime($item['created_at']);
            $item['updated_at'] = formatDateTime($item['updated_at']);
            $item['source_count'] = intval($item['source_count']);
            $item['is_recommended'] = intval($item['is_recommended']);
            $item['status'] = intval($item['status']);
        }

        success([
            'list' => $list,
            'total' => intval($total),
            'page' => $page,
            'page_size' => $pageSize
        ]);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

function getVideoDetail($id) {
    validateInt($id, '影片ID');

    try {
        $db = getDB();
        ensureRecommendedColumn($db);

        $stmt = $db->prepare("SELECT * FROM video WHERE id = ?");
        $stmt->execute([$id]);
        $video = $stmt->fetch();

        if (!$video) {
            error('影片不存在', 404);
        }

        $video['created_at'] = formatDateTime($video['created_at']);
        $video['updated_at'] = formatDateTime($video['updated_at']);
        $video['is_recommended'] = intval($video['is_recommended']);
        $video['status'] = intval($video['status']);

        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM video_source WHERE video_id = ?");
        $stmt->execute([$id]);
        $video['source_count'] = intval($stmt->fetch()['cnt']);

        success($video);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

function createVideo() {
    $title = $_POST['title'] ?? '';
    $coverUrl = $_POST['cover_url'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 1;
    $isRecommended = intval($_POST['is_recommended'] ?? 0);

    validateRequired([
        'title' => '影片标题'
    ], ['title' => $title]);

    validateLength($title, 1, 200, '影片标题');

    if (!empty($description)) {
        validateLength($description, 0, 1000, '影片描述');
    }

    if (!in_array($status, [0, 1, '0', '1'])) {
        error('状态值必须为 0 或 1');
    }
    $status = intval($status);

    if (!in_array($isRecommended, [0, 1])) {
        $isRecommended = 0;
    }

    try {
        $db = getDB();
        ensureRecommendedColumn($db);

        if ($status === 1 && $isRecommended === 1) {
            $current = countRecommendedPublished($db);
            if ($current >= MAX_RECOMMENDED_PUBLISHED) {
                error('推荐且上架的影片最多为' . MAX_RECOMMENDED_PUBLISHED . '部，当前已满');
            }
        }

        $stmt = $db->prepare("
            INSERT INTO video (title, cover_url, description, status, is_recommended, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$title, $coverUrl, $description, $status, $isRecommended]);

        $videoId = $db->lastInsertId();

        success(['id' => $videoId], '添加成功');

    } catch (Exception $e) {
        error('添加失败：' . $e->getMessage());
    }
}

function updateVideo($id) {
    validateInt($id, '影片ID');

    $title = $_POST['title'] ?? '';
    $coverUrl = $_POST['cover_url'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? '';
    $isRecommended = isset($_POST['is_recommended']) ? intval($_POST['is_recommended']) : null;

    validateRequired([
        'title' => '影片标题',
        'status' => '状态'
    ], ['title' => $title, 'status' => $status]);

    validateLength($title, 1, 200, '影片标题');

    if (!empty($description)) {
        validateLength($description, 0, 1000, '影片描述');
    }

    if (!in_array($status, [0, 1, '0', '1'])) {
        error('状态值必须为 0 或 1');
    }
    $status = intval($status);

    try {
        $db = getDB();
        ensureRecommendedColumn($db);

        $stmt = $db->prepare("SELECT id, is_recommended, status FROM video WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            error('影片不存在', 404);
        }

        if ($isRecommended === null) {
            $isRecommended = intval($existing['is_recommended']);
        }
        if (!in_array($isRecommended, [0, 1])) {
            $isRecommended = 0;
        }

        if ($status === 1 && $isRecommended === 1) {
            $current = countRecommendedPublished($db, $id);
            if ($current >= MAX_RECOMMENDED_PUBLISHED) {
                error('推荐且上架的影片最多为' . MAX_RECOMMENDED_PUBLISHED . '部，当前已满');
            }
        }

        $stmt = $db->prepare("
            UPDATE video
            SET title = ?, cover_url = ?, description = ?, status = ?, is_recommended = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $coverUrl, $description, $status, $isRecommended, $id]);

        success(null, '更新成功');

    } catch (Exception $e) {
        error('更新失败：' . $e->getMessage());
    }
}

function deleteVideo($id) {
    validateInt($id, '影片ID');

    try {
        $db = getDB();

        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT id FROM video WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                error('影片不存在', 404);
            }

            $stmt = $db->prepare("DELETE FROM video_source WHERE video_id = ?");
            $stmt->execute([$id]);

            $stmt = $db->prepare("DELETE FROM video WHERE id = ?");
            $stmt->execute([$id]);

            $db->commit();

            success(null, '删除成功');
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        error('删除失败：' . $e->getMessage());
    }
}

function updateVideoStatus($id) {
    validateInt($id, '影片ID');

    $status = $_POST['status'] ?? '';

    if ($status === '') {
        error('状态不能为空');
    }

    if (!in_array($status, ['0', '1'])) {
        error('状态值不正确');
    }
    $status = intval($status);

    try {
        $db = getDB();
        ensureRecommendedColumn($db);

        $stmt = $db->prepare("SELECT id, is_recommended FROM video WHERE id = ?");
        $stmt->execute([$id]);
        $video = $stmt->fetch();
        if (!$video) {
            error('影片不存在', 404);
        }

        if ($status === 1 && intval($video['is_recommended']) === 1) {
            $current = countRecommendedPublished($db, $id);
            if ($current >= MAX_RECOMMENDED_PUBLISHED) {
                error('推荐且上架的影片最多为' . MAX_RECOMMENDED_PUBLISHED . '部，当前已满');
            }
        }

        $stmt = $db->prepare("UPDATE video SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);

        success(null, $status == 1 ? '上架成功' : '下架成功');

    } catch (Exception $e) {
        error('操作失败：' . $e->getMessage());
    }
}

function parseIdList($input) {
    if (is_string($input)) {
        $input = json_decode($input, true);
        if (!is_array($input)) {
            $input = explode(',', $input);
        }
    }
    if (!is_array($input)) {
        return [];
    }
    $ids = [];
    foreach ($input as $v) {
        if (is_numeric($v) && intval($v) > 0) {
            $ids[] = intval($v);
        }
    }
    return array_values(array_unique($ids));
}

function batchUpdateStatus() {
    $ids = parseIdList($_POST['ids'] ?? []);
    $status = $_POST['status'] ?? '';

    if (empty($ids)) {
        error('请选择要操作的影片');
    }
    if (!in_array($status, ['0', '1'])) {
        error('状态值不正确');
    }
    $status = intval($status);

    try {
        $db = getDB();
        ensureRecommendedColumn($db);

        if ($status === 1) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("SELECT id, is_recommended FROM video WHERE id IN ({$placeholders})");
            $stmt->execute($ids);
            $selected = $stmt->fetchAll();

            $selectedRecommended = 0;
            foreach ($selected as $v) {
                if (intval($v['is_recommended']) === 1) {
                    $selectedRecommended++;
                }
            }

            $currentRecommended = countRecommendedPublished($db);
            $placeholders2 = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM video WHERE id IN ({$placeholders2}) AND is_recommended = 1 AND status = 1");
            $stmt->execute($ids);
            $alreadyPublishedRecommended = intval($stmt->fetch()['cnt']);

            $newRecommendedPublished = $selectedRecommended - $alreadyPublishedRecommended;
            if ($currentRecommended + $newRecommendedPublished > MAX_RECOMMENDED_PUBLISHED) {
                error('批量上架后推荐且上架的影片将超过' . MAX_RECOMMENDED_PUBLISHED . '部上限，请先取消部分推荐或下架部分推荐影片');
            }
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$status], $ids);
        $stmt = $db->prepare("UPDATE video SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})");
        $stmt->execute($params);

        $affected = $stmt->rowCount();
        success(['affected' => $affected], $status === 1 ? '批量上架成功' : '批量下架成功');

    } catch (Exception $e) {
        error('批量操作失败：' . $e->getMessage());
    }
}

function batchDeleteVideos() {
    $ids = parseIdList($_POST['ids'] ?? $_GET['ids'] ?? '[]');

    if (empty($ids)) {
        error('请选择要删除的影片');
    }

    try {
        $db = getDB();

        $db->beginTransaction();

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $stmt = $db->prepare("DELETE FROM video_source WHERE video_id IN ({$placeholders})");
            $stmt->execute($ids);

            $stmt = $db->prepare("DELETE FROM video WHERE id IN ({$placeholders})");
            $stmt->execute($ids);

            $affected = $stmt->rowCount();

            $db->commit();

            success(['affected' => $affected], '批量删除成功');
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        error('批量删除失败：' . $e->getMessage());
    }
}

function getDashboardStats() {
    try {
        $db = getDB();
        ensureRecommendedColumn($db);

        $stmt = $db->query("SELECT COUNT(*) as total FROM video");
        $totalVideos = intval($stmt->fetch()['total']);

        $stmt = $db->query("SELECT COUNT(*) as cnt FROM video WHERE status = 1");
        $publishedCount = intval($stmt->fetch()['cnt']);

        $stmt = $db->query("SELECT COUNT(*) as cnt FROM video WHERE status = 0");
        $unpublishedCount = intval($stmt->fetch()['cnt']);

        $stmt = $db->query("SELECT COUNT(*) as cnt FROM video_source");
        $totalSources = intval($stmt->fetch()['cnt']);

        $recommendedCount = countRecommendedPublished($db);

        $stmt = $db->query("
            SELECT id, title, cover_url, status, is_recommended, updated_at
            FROM video
            ORDER BY updated_at DESC
            LIMIT 8
        ");
        $recentVideos = $stmt->fetchAll();
        foreach ($recentVideos as &$item) {
            $item['updated_at'] = formatDateTime($item['updated_at']);
            $item['status'] = intval($item['status']);
            $item['is_recommended'] = intval($item['is_recommended']);
        }

        $stmt = $db->query("
            SELECT id, title, cover_url, updated_at
            FROM video
            WHERE is_recommended = 1 AND status = 1
            ORDER BY updated_at DESC
            LIMIT 8
        ");
        $recommendedVideos = $stmt->fetchAll();
        foreach ($recommendedVideos as &$item) {
            $item['updated_at'] = formatDateTime($item['updated_at']);
        }

        success([
            'total_videos' => $totalVideos,
            'published_count' => $publishedCount,
            'unpublished_count' => $unpublishedCount,
            'total_sources' => $totalSources,
            'recommended_count' => $recommendedCount,
            'max_recommended' => MAX_RECOMMENDED_PUBLISHED,
            'recent_videos' => $recentVideos,
            'recommended_videos' => $recommendedVideos
        ]);

    } catch (Exception $e) {
        error('获取看板数据失败：' . $e->getMessage());
    }
}

function handleVideoRequest($path, $method) {
    $parts = explode('/', $path);

    if ($method === 'GET' && $path === 'videos') {
        getVideoList();
    } elseif ($method === 'GET' && $path === 'videos/dashboard') {
        getDashboardStats();
    } elseif ($method === 'GET' && count($parts) === 2) {
        getVideoDetail($parts[1]);
    } elseif ($method === 'POST' && $path === 'videos') {
        createVideo();
    } elseif ($method === 'POST' && $path === 'videos/batch-status') {
        batchUpdateStatus();
    } elseif ($method === 'POST' && $path === 'videos/batch-delete') {
        batchDeleteVideos();
    } elseif ($method === 'POST' && count($parts) === 2) {
        updateVideo($parts[1]);
    } elseif ($method === 'DELETE' && $path === 'videos/batch-delete') {
        batchDeleteVideos();
    } elseif ($method === 'DELETE' && count($parts) === 2) {
        deleteVideo($parts[1]);
    } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'status') {
        updateVideoStatus($parts[1]);
    } else {
        error('接口不存在', 404);
    }
}
