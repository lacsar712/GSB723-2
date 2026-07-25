<?php
// 推荐且上架的影片数量上限
define('RECOMMEND_ONLINE_LIMIT', 8);

// 统计「推荐且上架」的影片数量（可排除指定影片ID）
function countRecommendOnline($db, $excludeIds = []) {
    $sql = "SELECT COUNT(*) as cnt FROM video WHERE is_recommend = 1 AND status = 1";
    $params = [];
    $excludeIds = array_values(array_filter(array_map('intval', $excludeIds)));
    if (!empty($excludeIds)) {
        $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
        $sql .= " AND id NOT IN ({$placeholders})";
        $params = $excludeIds;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return intval($stmt->fetch()['cnt']);
}

// 获取影片列表（管理后台）
function getVideoList() {
    $page = intval($_GET['page'] ?? 1);
    $pageSize = intval($_GET['page_size'] ?? 10);
    $status = $_GET['status'] ?? '';
    $keyword = $_GET['keyword'] ?? '';
    $hasSource = $_GET['has_source'] ?? ''; // '1'有 '0'无 ''全部

    $page = max(1, $page);
    $pageSize = min(100, max(1, $pageSize));
    $offset = ($page - 1) * $pageSize;

    try {
        $db = getDB();

        // 构建查询条件
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

        // 是否有播放源筛选
        if ($hasSource === '1') {
            $where[] = "EXISTS (SELECT 1 FROM video_source vs WHERE vs.video_id = v.id)";
        } elseif ($hasSource === '0') {
            $where[] = "NOT EXISTS (SELECT 1 FROM video_source vs WHERE vs.video_id = v.id)";
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        // 查询总数
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM video v {$whereClause}");
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        // 查询列表（含播放源数量）
        $stmt = $db->prepare("
            SELECT v.id, v.title, v.cover_url, v.description, v.status, v.is_recommend,
                   v.created_at, v.updated_at,
                   (SELECT COUNT(*) FROM video_source vs WHERE vs.video_id = v.id) AS source_count
            FROM video v
            {$whereClause}
            ORDER BY v.id DESC
            LIMIT {$offset}, {$pageSize}
        ");
        $stmt->execute($params);
        $list = $stmt->fetchAll();

        // 格式化日期与类型
        foreach ($list as &$item) {
            $item['status'] = intval($item['status']);
            $item['is_recommend'] = intval($item['is_recommend']);
            $item['source_count'] = intval($item['source_count']);
            $item['created_at'] = formatDateTime($item['created_at']);
            $item['updated_at'] = formatDateTime($item['updated_at']);
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

        $video['status'] = intval($video['status']);
        $video['is_recommend'] = intval($video['is_recommend']);
        $video['created_at'] = formatDateTime($video['created_at']);
        $video['updated_at'] = formatDateTime($video['updated_at']);

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

    // 验证必填
    validateRequired([
        'title' => '影片标题'
    ], ['title' => $title]);

    // 验证长度
    validateLength($title, 1, 200, '影片标题');

    // 验证描述长度
    if (!empty($description)) {
        validateLength($description, 0, 1000, '影片描述');
    }

    // 验证状态值
    if (!in_array($status, [0, 1, '0', '1'])) {
        error('状态值必须为 0 或 1');
    }
    $status = intval($status); // 统一转换为整数

    // 验证推荐值
    if (!in_array($isRecommend, [0, 1, '0', '1'])) {
        error('推荐值必须为 0 或 1');
    }
    $isRecommend = intval($isRecommend);

    try {
        $db = getDB();

        // 校验「推荐且上架」数量上限
        if ($isRecommend === 1 && $status === 1) {
            if (countRecommendOnline($db) + 1 > RECOMMEND_ONLINE_LIMIT) {
                error('推荐且上架的影片不能超过 ' . RECOMMEND_ONLINE_LIMIT . ' 部，请先取消部分推荐');
            }
        }

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
    $isRecommend = $_POST['is_recommend'] ?? 0;

    // 验证必填
    validateRequired([
        'title' => '影片标题',
        'status' => '状态'
    ], ['title' => $title, 'status' => $status]);

    // 验证长度
    validateLength($title, 1, 200, '影片标题');

    // 验证描述长度
    if (!empty($description)) {
        validateLength($description, 0, 1000, '影片描述');
    }

    // 验证状态值
    if (!in_array($status, [0, 1, '0', '1'])) {
        error('状态值必须为 0 或 1');
    }
    $status = intval($status); // 统一转换为整数

    // 验证推荐值
    if (!in_array($isRecommend, [0, 1, '0', '1'])) {
        error('推荐值必须为 0 或 1');
    }
    $isRecommend = intval($isRecommend);

    try {
        $db = getDB();

        // 检查影片是否存在
        $stmt = $db->prepare("SELECT id FROM video WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('影片不存在', 404);
        }

        // 校验「推荐且上架」数量上限（排除自身）
        if ($isRecommend === 1 && $status === 1) {
            if (countRecommendOnline($db, [$id]) + 1 > RECOMMEND_ONLINE_LIMIT) {
                error('推荐且上架的影片不能超过 ' . RECOMMEND_ONLINE_LIMIT . ' 部，请先取消部分推荐');
            }
        }

        // 更新影片
        $stmt = $db->prepare("
            UPDATE video
            SET title = ?, cover_url = ?, description = ?, status = ?, is_recommend = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $coverUrl, $description, $status, $isRecommend, $id]);

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

        // 开启事务
        $db->beginTransaction();

        try {
            // 检查影片是否存在
            $stmt = $db->prepare("SELECT id FROM video WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                error('影片不存在', 404);
            }

            // 删除播放源
            $stmt = $db->prepare("DELETE FROM video_source WHERE video_id = ?");
            $stmt->execute([$id]);

            // 删除影片
            $stmt = $db->prepare("DELETE FROM video WHERE id = ?");
            $stmt->execute([$id]);

            // 提交事务
            $db->commit();

            success(null, '删除成功');
        } catch (Exception $e) {
            // 回滚事务
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

    try {
        $db = getDB();

        // 检查影片是否存在
        $stmt = $db->prepare("SELECT id, is_recommend FROM video WHERE id = ?");
        $stmt->execute([$id]);
        $video = $stmt->fetch();
        if (!$video) {
            error('影片不存在', 404);
        }

        // 上架时若为推荐影片，校验「推荐且上架」数量上限
        if ($status === '1' && intval($video['is_recommend']) === 1) {
            if (countRecommendOnline($db, [$id]) + 1 > RECOMMEND_ONLINE_LIMIT) {
                error('推荐且上架的影片不能超过 ' . RECOMMEND_ONLINE_LIMIT . ' 部，无法上架该推荐影片');
            }
        }

        // 更新状态
        $stmt = $db->prepare("UPDATE video SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);

        success(null, $status == 1 ? '上架成功' : '下架成功');

    } catch (Exception $e) {
        error('操作失败：' . $e->getMessage());
    }
}

// 解析批量操作的影片ID数组
function parseBatchIds() {
    $ids = $_POST['ids'] ?? '';
    if (is_string($ids)) {
        $ids = array_filter(array_map('trim', explode(',', $ids)), function ($v) {
            return $v !== '';
        });
    }
    if (!is_array($ids) || count($ids) === 0) {
        error('请至少选择一部影片');
    }
    $ids = array_values(array_unique(array_map('intval', $ids)));
    foreach ($ids as $id) {
        if ($id <= 0) {
            error('影片ID不正确');
        }
    }
    return $ids;
}

// 批量更新状态（批量上架 / 下架）
function batchUpdateStatus() {
    $status = $_POST['status'] ?? '';
    if (!in_array($status, ['0', '1'])) {
        error('状态值不正确');
    }
    $status = intval($status);
    $ids = parseBatchIds();

    try {
        $db = getDB();

        // 批量上架时，校验「推荐且上架」数量上限
        if ($status === 1) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM video WHERE is_recommend = 1 AND id IN ({$placeholders})");
            $stmt->execute($ids);
            $recommendInBatch = intval($stmt->fetch()['cnt']);

            // 其余「推荐且上架」的影片数量（排除本批次）
            $othersRecommendOnline = countRecommendOnline($db, $ids);

            if ($othersRecommendOnline + $recommendInBatch > RECOMMEND_ONLINE_LIMIT) {
                error('批量上架后推荐且上架的影片将超过 ' . RECOMMEND_ONLINE_LIMIT . ' 部，请调整选择或先取消部分推荐');
            }
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("UPDATE video SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})");
        $stmt->execute(array_merge([$status], $ids));

        success(['affected' => $stmt->rowCount()], $status === 1 ? '批量上架成功' : '批量下架成功');

    } catch (Exception $e) {
        error('操作失败：' . $e->getMessage());
    }
}

// 批量删除
function batchDelete() {
    $ids = parseBatchIds();

    try {
        $db = getDB();
        $db->beginTransaction();

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            // 删除播放源
            $stmt = $db->prepare("DELETE FROM video_source WHERE video_id IN ({$placeholders})");
            $stmt->execute($ids);

            // 删除影片
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

// 处理影片请求
function handleVideoRequest($path, $method) {
    // 解析路径
    $parts = explode('/', $path);

    // 批量操作接口（优先匹配）
    if ($method === 'POST' && $path === 'videos/batch/status') {
        batchUpdateStatus();
        return;
    }
    if ($method === 'POST' && $path === 'videos/batch/delete') {
        batchDelete();
        return;
    }

    if ($method === 'GET' && $path === 'videos') {
        // 获取列表
        getVideoList();
    } elseif ($method === 'GET' && count($parts) === 2) {
        // 获取详情
        getVideoDetail($parts[1]);
    } elseif ($method === 'POST' && $path === 'videos') {
        // 新增
        createVideo();
    } elseif ($method === 'POST' && count($parts) === 2) {
        // 更新
        updateVideo($parts[1]);
    } elseif ($method === 'DELETE' && count($parts) === 2) {
        // 删除
        deleteVideo($parts[1]);
    } elseif ($method === 'POST' && count($parts) === 3 && $parts[2] === 'status') {
        // 更新状态
        updateVideoStatus($parts[1]);
    } else {
        error('接口不存在', 404);
    }
}
