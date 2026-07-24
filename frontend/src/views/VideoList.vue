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
              @keyup.enter="handleQuery"
            />
          </el-form-item>
          <el-form-item label="状态">
            <el-select
              v-model="queryForm.status"
              placeholder="请选择状态"
              clearable
              style="width: 150px"
              @change="handleQuery"
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
              style="width: 150px"
              @change="handleQuery"
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

      <div class="batch-bar" v-if="selectedRows.length > 0">
        <span class="batch-info">已选择 <strong>{{ selectedRows.length }}</strong> 项</span>
        <el-button type="success" size="default" @click="handleBatchPublish">
          <el-icon><Top /></el-icon>
          批量上架
        </el-button>
        <el-button type="warning" size="default" @click="handleBatchUnpublish">
          <el-icon><Bottom /></el-icon>
          批量下架
        </el-button>
        <el-button type="danger" size="default" @click="handleBatchDelete">
          <el-icon><Delete /></el-icon>
          批量删除
        </el-button>
      </div>

      <el-table
        :data="tableData"
        border
        stripe
        v-loading="loading"
        @selection-change="handleSelectionChange"
        row-key="id"
      >
        <el-table-column type="selection" width="50" />
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="title" label="影片标题" min-width="180" />
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
              type="primary"
              link
              :class="{ 'has-sources': row.source_count > 0 }"
              @click="handleSources(row)"
            >
              {{ row.source_count }}
            </el-button>
          </template>
        </el-table-column>
        <el-table-column prop="is_recommend" label="推荐" width="80" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.is_recommend == 1" type="warning" size="small">推荐</el-tag>
            <span v-else style="color: #c0c4cc">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="描述" min-width="180" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="row.status == 1 ? 'success' : 'info'">
              {{ row.status == 1 ? '上架' : '下架' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="updated_at" label="更新时间" width="170" />
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button size="small" @click="handleSources(row)">播放源</el-button>
            <el-button
              size="small"
              :type="row.status == 1 ? 'warning' : 'success'"
              @click="handleToggleStatus(row)"
            >
              {{ row.status == 1 ? '下架' : '上架' }}
            </el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
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
import { useRouter } from 'vue-router'
import { Plus, Top, Bottom, Delete } from '@element-plus/icons-vue'
import { useVideos } from '../composables/useVideos'
import { useCoverUrl } from '../composables/useCoverUrl'

const router = useRouter()
const showViewer = ref(false)
const previewUrl = ref('')

const { getCoverUrl, handleImageError } = useCoverUrl()

const {
  loading,
  tableData,
  total,
  selectedRows,
  queryForm,
  fetchData,
  handleQuery,
  handleReset,
  handlePageChange,
  handleSizeChange,
  handleSelectionChange,
  handleToggleStatus,
  handleDelete,
  handleBatchPublish,
  handleBatchUnpublish,
  handleBatchDelete
} = useVideos()

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
  margin-bottom: 16px;
  padding: 12px 20px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.batch-info {
  font-size: 14px;
  color: #1e40af;
  margin-right: 8px;
}

.batch-info strong {
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
  height: 40px;
  border-radius: 6px;
  object-fit: cover;
  display: block;
}

.cover-empty {
  display: inline-flex;
  width: 70px;
  height: 40px;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  background: #f0f0ff;
  color: #94a3b8;
  font-size: 12px;
}

.has-sources {
  font-weight: 600;
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
