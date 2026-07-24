<?php
define('MAX_RECOMMEND_COUNT', 8);

function countRecommendPublished($db, $excludeId = null) {
    if ($excludeId) {
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM video WHERE is_recommend = 1 AND status = 1 AND id != ?");
        $stmt->execute([$excludeId]);
    } else {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM video WHERE is_recommend = 1 AND status = 1");
    }
    return intval($stmt->fetch()['cnt']);
}

function checkRecommendLimit($db, $isRecommend, $status, $excludeId = null) {
    if (intval($isRecommend) === 1 && intval($status) === 1) {
        $current = countRecommendPublished($db, $excludeId);
        if ($current >= MAX_RECOMMEND_COUNT) {
            error('推荐且上架的影片总数不得超过 ' . MAX_RECOMMEND_COUNT . ' 部，当前已有 ' . $current . ' 部');
        }
    }
}

// 获取影片列表（管理后台）
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
            $where[] = "sc.source_count > 0";
        } elseif ($hasSource === '0') {
            $where[] = "sc.source_count = 0";
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM video v LEFT JOIN (SELECT video_id, COUNT(*) as source_count FROM video_source GROUP BY video_id) sc ON v.id = sc.video_id {$whereClause}");
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $stmt = $db->prepare("
            SELECT v.id, v.title, v.cover_url, v.description, v.status, v.is_recommend,
                   v.created_at, v.updated_at,
                   IFNULL(sc.source_count, 0) as source_count
            FROM video v
            LEFT JOIN (SELECT video_id, COUNT(*) as source_count FROM video_source GROUP BY video_id) sc ON v.id = sc.video_id
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
            $item['is_recommend'] = intval($item['is_recommend']);
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

// 获取影片详情
function getVideoDetail($id) {
    validateInt($id, '影片ID');

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM video WHERE id = ?");
        $stmt->execute([$id]);
        $video = $stmt->fetch();

        if (!$video) {
            error('影片不存在', 404);
        }

        $video['created_at'] = formatDateTime($video['created_at']);
        $video['updated_at'] = formatDateTime($video['updated_at']);
        $video['is_recommend'] = intval($video['is_recommend']);
        $video['status'] = intval($video['status']);

        success($video);

    } catch (Exception $e) {
        error('查询失败：' . $e->getMessage());
    }
}

// 新增影片
function createVideo() {
    $title = $_POST['title'] ?? '';
    $coverUrl = $_POST['cover_url'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 1;
    $isRecommend = $_POST['is_recommend'] ?? 0;

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

    if (!in_array($isRecommend, [0, 1, '0', '1'])) {
        error('推荐值必须为 0 或 1');
    }
    $isRecommend = intval($isRecommend);

    try {
        $db = getDB();

        checkRecommendLimit($db, $isRecommend, $status);

        $stmt = $db->prepare("
            INSERT INTO video (title, cover_url, description, status, is_recommend, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$title, $coverUrl, $description, $status, $isRecommend]);

        $videoId = $db->lastInsertId();

        success(['id' => $videoId], '添加成功');

    } catch (Exception $e) {
        error('添加失败：' . $e->getMessage());
    }
}

// 更新影片
function updateVideo($id) {
    validateInt($id, '影片ID');

    $title = $_POST['title'] ?? '';
    $coverUrl = $_POST['cover_url'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? '';
    $isRecommend = $_POST['is_recommend'] ?? '';

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

    if ($isRecommend !== '' && !in_array($isRecommend, [0, 1, '0', '1'])) {
        error('推荐值必须为 0 或 1');
    }
    $isRecommend = $isRecommend !== '' ? intval($isRecommend) : null;

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id, is_recommend, status FROM video WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            error('影片不存在', 404);
        }

        $finalIsRecommend = $isRecommend !== null ? $isRecommend : intval($existing['is_recommend']);

        if ($finalIsRecommend === 1 && $status === 1) {
            $current = countRecommendPublished($db, $id);
            if ($current >= MAX_RECOMMEND_COUNT) {
                error('推荐且上架的影片总数不得超过 ' . MAX_RECOMMEND_COUNT . ' 部，当前已有 ' . $current . ' 部');
            }
        }

        $stmt = $db->prepare("
            UPDATE video
            SET title = ?, cover_url = ?, description = ?, status = ?, is_recommend = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $coverUrl, $description, $status, $finalIsRecommend, $id]);

        success(null, '更新成功');

    } catch (Exception $e) {
        error('更新失败：' . $e->getMessage());
    }
}

// 删除影片
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

// 更新影片状态（上下架）
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

        $stmt = $db->prepare("SELECT id, is_recommend FROM video WHERE id = ?");
        $stmt->execute([$id]);
        $video = $stmt->fetch();
        if (!$video) {
            error('影片不存在', 404);
        }

        if ($status === 1 && intval($video['is_recommend']) === 1) {
            $current = countRecommendPublished($db, $id);
            if ($current >= MAX_RECOMMEND_COUNT) {
                error('推荐且上架的影片总数不得超过 ' . MAX_RECOMMEND_COUNT . ' 部，当前已有 ' . $current . ' 部');
            }
        }

        $stmt = $db->prepare("UPDATE video SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);

        success(null, $status == 1 ? '上架成功' : '下架成功');

    } catch (Exception $e) {
        error('操作失败：' . $e->getMessage());
    }
}

// 批量更新影片状态
function batchUpdateStatus() {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $ids = $input['ids'] ?? [];
    $status = $input['status'] ?? '';

    if (empty($ids) || !is_array($ids)) {
        error('请选择要操作的影片');
    }

    if (!in_array($status, ['0', '1'])) {
        error('状态值不正确');
    }
    $status = intval($status);

    foreach ($ids as $id) {
        if (!is_numeric($id)) {
            error('影片ID格式不正确');
        }
    }

    try {
        $db = getDB();

        if ($status === 1) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM video WHERE is_recommend = 1 AND id IN ({$placeholders})");
            $stmt->execute($ids);
            $toPublishRecommended = intval($stmt->fetch()['cnt']);

            $existingRecommended = countRecommendPublished($db);

            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM video WHERE is_recommend = 1 AND status = 1 AND id IN ({$placeholders})");
            $stmt->execute($ids);
            $alreadyPublishedRecommended = intval($stmt->fetch()['cnt']);

            $newRecommended = $toPublishRecommended - $alreadyPublishedRecommended;
            if ($existingRecommended + $newRecommended > MAX_RECOMMEND_COUNT) {
                error('推荐且上架的影片总数不得超过 ' . MAX_RECOMMEND_COUNT . ' 部，本次操作将导致总数超限');
            }
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $db->prepare("UPDATE video SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})");
        $stmt->execute(array_merge([$status], $ids));

        $action = $status === 1 ? '上架' : '下架';
        success(['count' => count($ids)], "批量{$action}成功");

    } catch (Exception $e) {
        error('批量操作失败：' . $e->getMessage());
    }
}

// 批量删除影片
function batchDelete() {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $ids = $input['ids'] ?? [];

    if (empty($ids) || !is_array($ids)) {
        error('请选择要删除的影片');
    }

    foreach ($ids as $id) {
        if (!is_numeric($id)) {
            error('影片ID格式不正确');
        }
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

            $db->commit();

            success(['count' => count($ids)], '批量删除成功');
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        error('批量删除失败：' . $e->getMessage());
    }
}

// 处理影片请求
function handleVideoRequest($path, $method) {
    $parts = explode('/', $path);

    if ($method === 'GET' && $path === 'videos') {
        getVideoList();
    } elseif ($method === 'POST' && $path === 'videos') {
        createVideo();
    } elseif ($method === 'POST' && $path === 'videos/batch-status') {
        batchUpdateStatus();
    } elseif ($method === 'POST' && $path === 'videos/batch-delete') {
        batchDelete();
    } elseif ($method === 'GET' && count($parts) === 2) {
        getVideoDetail($parts[1]);
    } elseif ($method === 'POST' && count($parts) === 2) {
        updateVideo($parts[1]);
    } elseif ($method === 'DELETE' && count($parts) === 2) {
        deleteVideo($parts[1]);
    } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'status') {
        updateVideoStatus($parts[1]);
    } else {
        error('接口不存在', 404);
    }
}
