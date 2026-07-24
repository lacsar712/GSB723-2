<template>
  <div class="dashboard" v-loading="loading">
    <div class="stats-grid">
      <el-card shadow="never" class="stat-card" @click="goToVideos">
        <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;">
          <el-icon :size="28"><Film /></el-icon>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.total_videos }}</div>
          <div class="stat-label">影片总数</div>
        </div>
      </el-card>

      <el-card shadow="never" class="stat-card" @click="goToVideos">
        <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
          <el-icon :size="28"><Top /></el-icon>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.published_count }}</div>
          <div class="stat-label">上架数</div>
        </div>
      </el-card>

      <el-card shadow="never" class="stat-card" @click="goToVideos">
        <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
          <el-icon :size="28"><Bottom /></el-icon>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.unpublished_count }}</div>
          <div class="stat-label">下架数</div>
        </div>
      </el-card>

      <el-card shadow="never" class="stat-card" @click="goToVideos">
        <div class="stat-icon" style="background: rgba(236,72,153,0.1); color: #ec4899;">
          <el-icon :size="28"><Connection /></el-icon>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.total_sources }}</div>
          <div class="stat-label">播放源总数</div>
        </div>
      </el-card>
    </div>

    <el-row :gutter="20">
      <el-col :span="14">
        <el-card shadow="never" class="section-card">
          <template #header>
            <div class="section-header">
              <h3 class="section-title">最近更新</h3>
              <el-button link type="primary" @click="goToVideos">查看全部</el-button>
            </div>
          </template>
          <el-table :data="stats.recent_videos" stripe style="width: 100%" :show-header="false">
            <el-table-column prop="title" label="标题" min-width="160">
              <template #default="{ row }">
                <span class="video-title-link" @click="goToEdit(row.id)">{{ row.title }}</span>
              </template>
            </el-table-column>
            <el-table-column label="状态" width="80" align="center">
              <template #default="{ row }">
                <el-tag :type="getStatusType(row.status)" size="small">
                  {{ getStatusLabel(row.status) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="updated_at" label="更新时间" width="160" />
          </el-table>
        </el-card>
      </el-col>

      <el-col :span="10">
        <el-card shadow="never" class="section-card">
          <template #header>
            <div class="section-header">
              <h3 class="section-title">推荐影片</h3>
              <span class="recommended-hint">{{ stats.recommended_count }}/{{ stats.max_recommended }}</span>
            </div>
          </template>
          <div v-if="stats.recommended_videos.length === 0" class="empty-tip">
            暂无推荐影片
          </div>
          <div v-else class="recommended-grid">
            <div
              v-for="video in stats.recommended_videos"
              :key="video.id"
              class="recommend-card"
              @click="goToEdit(video.id)"
            >
              <div class="recommend-cover">
                <img
                  v-if="video.cover_url"
                  :src="getCoverUrl(video.cover_url)"
                  :alt="video.title"
                  loading="lazy"
                />
                <div v-else class="recommend-cover-empty">
                  <el-icon :size="24" color="#cbd5e1"><Picture /></el-icon>
                </div>
                <div class="recommend-badge">推荐</div>
              </div>
              <div class="recommend-title" :title="video.title">{{ video.title }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-card shadow="never" class="section-card quick-section">
      <h3 class="section-title">快捷操作</h3>
      <div class="quick-actions">
        <div class="action-card" @click="goToAddVideo">
          <div class="action-icon" style="background: rgba(99,102,241,0.1);">
            <el-icon :size="24" color="#6366f1"><Plus /></el-icon>
          </div>
          <div class="action-info">
            <h4>新增影片</h4>
            <p>添加新的影片资源</p>
          </div>
        </div>
        <div class="action-card" @click="goToVideos">
          <div class="action-icon" style="background: rgba(16,185,129,0.1);">
            <el-icon :size="24" color="#10b981"><List /></el-icon>
          </div>
          <div class="action-info">
            <h4>影片管理</h4>
            <p>编辑、上下架、删除影片</p>
          </div>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { Film, Plus, Top, Bottom, Connection, Picture, List } from '@element-plus/icons-vue'
import { useDashboard } from '../composables/useDashboard'

const {
  loading,
  stats,
  goToVideos,
  goToAddVideo,
  goToEdit,
  getCoverUrl,
  getStatusLabel,
  getStatusType
} = useDashboard()
</script>

<style scoped>
.dashboard {
  max-width: 1200px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.stat-card {
  border-radius: 12px;
  border: 1px solid #f0f0f0;
  cursor: pointer;
  transition: all 0.2s;
}

.stat-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.stat-card :deep(.el-card__body) {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
}

.stat-label {
  font-size: 13px;
  color: #94a3b8;
  margin-top: 4px;
}

.section-card {
  border-radius: 12px;
  border: 1px solid #f0f0f0;
  margin-bottom: 20px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-title {
  margin: 0;
  font-size: 16px;
  color: #1e293b;
  font-weight: 600;
}

.recommended-hint {
  font-size: 13px;
  color: #94a3b8;
}

.video-title-link {
  color: #1e293b;
  cursor: pointer;
  font-weight: 500;
  transition: color 0.2s;
}

.video-title-link:hover {
  color: #6366f1;
}

.empty-tip {
  text-align: center;
  padding: 32px 0;
  color: #cbd5e1;
  font-size: 14px;
}

.recommended-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.recommend-card {
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid #f0f0f0;
}

.recommend-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.recommend-cover {
  position: relative;
  width: 100%;
  padding-top: 56%;
  background: #f8fafc;
  overflow: hidden;
}

.recommend-cover img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.recommend-cover-empty {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.recommend-badge {
  position: absolute;
  top: 6px;
  left: 6px;
  background: linear-gradient(135deg, #f59e0b, #ef4444);
  color: #fff;
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 4px;
  font-weight: 600;
}

.recommend-title {
  padding: 8px 10px;
  font-size: 13px;
  color: #334155;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.quick-section {
  margin-bottom: 0;
}

.quick-actions {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.action-card {
  background: #f8fafc;
  border-radius: 10px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.action-card:hover {
  background: #fff;
  border-color: #e2e8f0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.action-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.action-info h4 {
  margin: 0 0 4px;
  font-size: 15px;
  color: #1e293b;
  font-weight: 600;
}

.action-info p {
  margin: 0;
  font-size: 12px;
  color: #94a3b8;
}
</style>
