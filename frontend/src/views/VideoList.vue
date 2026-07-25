<template>
  <div class="video-list">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>影片管理</h3>
          <el-button type="primary" @click="handleAdd">
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
              placeholder="是否有播放源"
              clearable
              style="width: 160px"
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

      <!-- 批量操作栏 -->
      <div class="batch-bar">
        <span class="batch-tip">已选择 {{ selectedRows.length }} 项</span>
        <el-button
          size="small"
          type="success"
          :disabled="selectedRows.length === 0"
          @click="batchToggleStatus(1)"
        >
          批量上架
        </el-button>
        <el-button
          size="small"
          type="warning"
          :disabled="selectedRows.length === 0"
          @click="batchToggleStatus(0)"
        >
          批量下架
        </el-button>
        <el-button
          size="small"
          type="danger"
          :disabled="selectedRows.length === 0"
          @click="batchRemove"
        >
          批量删除
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
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="title" label="影片标题" min-width="180" />
        <el-table-column prop="cover_url" label="封面" width="120">
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
        <el-table-column prop="status" label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="row.status == 1 ? 'success' : 'info'">
              {{ row.status == 1 ? '上架' : '下架' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="推荐" width="90">
          <template #default="{ row }">
            <el-tag v-if="row.is_recommend == 1" type="warning">推荐</el-tag>
            <span v-else class="text-muted">—</span>
          </template>
        </el-table-column>
        <el-table-column label="播放源数量" width="110">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleSources(row)">
              {{ row.source_count }}
            </el-button>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="170" />
        <el-table-column label="操作" width="300" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button size="small" @click="handleSources(row)">播放源</el-button>
            <el-button
              size="small"
              :type="row.status == 1 ? 'warning' : 'success'"
              @click="toggleStatus(row)"
            >
              {{ row.status == 1 ? '下架' : '上架' }}
            </el-button>
            <el-button size="small" type="danger" @click="removeVideo(row)">删除</el-button>
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

    <!-- 图片预览对话框 -->
    <el-dialog v-model="showViewer" width="800px" :show-close="true">
      <img :src="previewUrl" style="width: 100%; display: block;" />
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useVideoList } from '../composables/useVideoList'
import { useCoverUrl } from '../composables/useCoverUrl'

const router = useRouter()
const { getCoverUrl, handleImageError } = useCoverUrl()

const {
  loading,
  tableData,
  total,
  queryForm,
  selectedRows,
  fetchData,
  handleQuery,
  handleReset,
  handlePageChange,
  handleSizeChange,
  handleSelectionChange,
  toggleStatus,
  removeVideo,
  batchToggleStatus,
  batchRemove
} = useVideoList()

const previewUrl = ref('')
const showViewer = ref(false)

const handleAdd = () => {
  router.push('/videos/new')
}

const handleEdit = (row) => {
  router.push(`/videos/${row.id}/edit`)
}

const handleSources = (row) => {
  router.push(`/videos/${row.id}/sources`)
}

const handlePreview = (url) => {
  previewUrl.value = url
  showViewer.value = true
}

onMounted(() => {
  fetchData()
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

.batch-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  padding: 10px 16px;
  background: #f0f0ff;
  border-radius: 8px;
}

.batch-tip {
  font-size: 13px;
  color: #6366f1;
  font-weight: 600;
}

.text-muted {
  color: #cbd5e1;
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
  width: 80px;
  height: 45px;
  border-radius: 6px;
  object-fit: cover;
  display: block;
}

.cover-empty {
  display: inline-flex;
  width: 80px;
  height: 45px;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  background: #f0f0ff;
  color: #94a3b8;
  font-size: 12px;
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
