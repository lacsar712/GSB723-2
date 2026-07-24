<template>
  <div class="video-list">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>影片管理</h3>
          <el-button type="primary" @click="goToAdd">
            <el-icon><Plus /></el-icon>
            新增影片
          </el-button>
        </div>
      </template>

      <div class="filter-bar">
        <el-form :inline="true" :model="queryForm">
          <el-form-item label="关键词">
            <el-input
              v-model="queryForm.keyword"
              placeholder="请输入影片标题"
              clearable
              style="width: 200px"
              @clear="handleQuery"
              @keyup.enter="handleQuery"
            />
          </el-form-item>
          <el-form-item label="状态">
            <el-select
              v-model="queryForm.status"
              placeholder="请选择状态"
              clearable
              style="width: 160px"
              @clear="handleQuery"
            >
              <el-option label="上架" value="1" />
              <el-option label="下架" value="0" />
            </el-select>
          </el-form-item>
          <el-form-item label="播放源">
            <el-select
              v-model="queryForm.has_source"
              placeholder="全部"
              clearable
              style="width: 140px"
              @clear="handleQuery"
            >
              <el-option label="有播放源" value="1" />
              <el-option label="无播放源" value="0" />
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="handleQuery">查询</el-button>
            <el-button @click="handleReset">重置</el-button>
          </el-form-item>
        </el-form>
      </div>

      <div class="bulk-bar" v-if="selectedRows.length > 0">
        <span class="bulk-info">已选择 <strong>{{ selectedRows.length }}</strong> 部影片</span>
        <el-button type="success" size="small" @click="bulkPublish">
          <el-icon><Top /></el-icon>批量上架
        </el-button>
        <el-button type="warning" size="small" @click="bulkUnpublish">
          <el-icon><Bottom /></el-icon>批量下架
        </el-button>
        <el-button type="danger" size="small" @click="bulkDelete">
          <el-icon><Delete /></el-icon>批量删除
        </el-button>
      </div>

      <el-table
        :data="tableData"
        border
        stripe
        v-loading="loading"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="50" />
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="title" label="影片标题" min-width="180" show-overflow-tooltip />
        <el-table-column prop="cover_url" label="封面" width="100">
          <template #default="{ row }">
            <div v-if="row.cover_url" class="cover-wrapper" @click="handlePreview(getCoverUrl(row.cover_url))">
              <img
                :src="getCoverUrl(row.cover_url)"
                :alt="row.title"
                class="cover-image"
                loading="lazy"
                @error="handleImageError"
              />
            </div>
            <span v-else class="cover-empty">暂无</span>
          </template>
        </el-table-column>
        <el-table-column prop="source_count" label="播放源数量" width="110" align="center">
          <template #default="{ row }">
            <el-button
              v-if="row.source_count > 0"
              link
              type="primary"
              @click="goToSources(row)"
            >
              {{ row.source_count }} 个
            </el-button>
            <span v-else class="source-zero">0</span>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="描述" min-width="180" show-overflow-tooltip />
        <el-table-column label="推荐" width="80" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.is_recommended === 1" type="warning" size="small">推荐</el-tag>
            <span v-else class="text-muted">—</span>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '上架' : '下架' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="updated_at" label="更新时间" width="170" />
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="goToEdit(row)">编辑</el-button>
            <el-button size="small" @click="goToSources(row)">播放源</el-button>
            <el-button
              size="small"
              :type="row.status === 1 ? 'warning' : 'success'"
              @click="toggleSingleStatus(row)"
            >
              {{ row.status === 1 ? '下架' : '上架' }}
            </el-button>
            <el-button size="small" type="danger" @click="deleteSingle(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.page_size"
          :page-sizes="[10, 20, 50, 100]"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>

    <el-dialog v-model="showViewer" width="800px" :show-close="true">
      <img :src="previewUrl" style="width: 100%; display: block;" />
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Plus, Top, Bottom, Delete } from '@element-plus/icons-vue'
import { useVideoList } from '../composables/useVideoList'

const {
  loading,
  tableData,
  total,
  selectedRows,
  queryForm,
  fetchList,
  handleQuery,
  handleReset,
  handlePageChange,
  handleSizeChange,
  handleSelectionChange,
  goToEdit,
  goToSources,
  goToAdd,
  toggleSingleStatus,
  deleteSingle,
  bulkPublish,
  bulkUnpublish,
  bulkDelete
} = useVideoList()

const previewUrl = ref('')
const showViewer = ref(false)

const getCoverUrl = (url) => {
  if (!url) return ''
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url
  }
  const baseURL = import.meta.env.VITE_API_BASE_URL || ''
  return baseURL ? `${baseURL}${url}` : url
}

const handlePreview = (url) => {
  previewUrl.value = url
  showViewer.value = true
}

const handleImageError = (e) => {
  e.target.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="100" height="100"%3E%3Crect fill="%23f5f5f5" width="100" height="100"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999"%3E加载失败%3C/text%3E%3C/svg%3E'
}

onMounted(() => {
  fetchList()
})
</script>

<style scoped>
.video-list :deep(.el-card) {
  border-radius: 12px;
  border: 1px solid #f0f0f0;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
}

.filter-bar {
  margin-bottom: 16px;
  padding: 16px 20px;
  background: #f8fafc;
  border-radius: 8px;
}

.filter-bar :deep(.el-form-item) {
  margin-bottom: 0;
}

.bulk-bar {
  margin-bottom: 16px;
  padding: 10px 16px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.bulk-info {
  font-size: 14px;
  color: #1e40af;
  margin-right: 8px;
}

.bulk-info strong {
  color: #2563eb;
  font-size: 16px;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.cover-wrapper {
  cursor: pointer;
  transition: transform 0.2s;
}

.cover-wrapper:hover {
  transform: scale(1.05);
}

.cover-image {
  width: 70px;
  height: 42px;
  border-radius: 6px;
  object-fit: cover;
  display: block;
}

.cover-empty {
  display: inline-flex;
  width: 70px;
  height: 42px;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  background: #f0f0ff;
  color: #94a3b8;
  font-size: 12px;
}

.source-zero {
  color: #cbd5e1;
  font-size: 13px;
}

.text-muted {
  color: #cbd5e1;
}

.video-list :deep(.el-table) {
  border-radius: 8px;
}

.video-list :deep(.el-table th.el-table__cell) {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 13px;
}

.video-list :deep(.el-button--primary) {
  background: #6366f1;
  border-color: #6366f1;
}

.video-list :deep(.el-button--primary:hover) {
  background: #4f46e5;
  border-color: #4f46e5;
}
</style>
