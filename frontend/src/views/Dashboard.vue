<template>
  <div class="dashboard">
    <div class="stats-grid">
      <el-card class="stat-card" shadow="hover" v-loading="statsLoading">
        <div class="stat-content">
          <div class="stat-icon" style="background: rgba(99,102,241,0.1);">
            <el-icon :size="28" color="#6366f1"><Film /></el-icon>
          </div>
          <div class="stat-info">
            <p class="stat-label">影片总数</p>
            <h2 class="stat-value">{{ stats.total_videos }}</h2>
          </div>
        </div>
      </el-card>

      <el-card class="stat-card" shadow="hover" v-loading="statsLoading">
        <div class="stat-content">
          <div class="stat-icon" style="background: rgba(16,185,129,0.1);">
            <el-icon :size="28" color="#10b981"><CircleCheck /></el-icon>
          </div>
          <div class="stat-info">
            <p class="stat-label">上架影片</p>
            <h2 class="stat-value">{{ stats.published_count }}</h2>
          </div>
        </div>
      </el-card>

      <el-card class="stat-card" shadow="hover" v-loading="statsLoading">
        <div class="stat-content">
          <div class="stat-icon" style="background: rgba(245,158,11,0.1);">
            <el-icon :size="28" color="#f59e0b"><Remove /></el-icon>
          </div>
          <div class="stat-info">
            <p class="stat-label">下架影片</p>
            <h2 class="stat-value">{{ stats.unpublished_count }}</h2>
          </div>
        </div>
      </el-card>

      <el-card class="stat-card" shadow="hover" v-loading="statsLoading">
        <div class="stat-content">
          <div class="stat-icon" style="background: rgba(236,72,153,0.1);">
            <el-icon :size="28" color="#ec4899"><Link /></el-icon>
          </div>
          <div class="stat-info">
            <p class="stat-label">播放源总数</p>
            <h2 class="stat-value">{{ stats.total_sources }}</h2>
          </div>
        </div>
      </el-card>
    </div>

    <div class="content-grid">
      <el-card class="section-card" shadow="never" v-loading="recentLoading">
        <template #header>
          <div class="section-header">
            <h3 class="section-title">最近更新</h3>
            <el-button text type="primary" @click="goToVideos">查看全部</el-button>
          </div>
        </template>
        <div class="recent-list" v-if="recentVideos.length > 0">
          <div
            v-for="video in recentVideos"
            :key="video.id"
            class="recent-item"
            @click="handleEdit(video)"
          >
            <div class="recent-cover">
              <img
                v-if="video.cover_url"
                :src="getCoverUrl(video.cover_url)"
                :alt="video.title"
                @error="handleImageError"
              />
              <div v-else class="cover-placeholder">
                <el-icon :size="20" color="#94a3b8"><Film /></el-icon>
              </div>
            </div>
            <div class="recent-info">
              <h4 class="recent-title" :title="video.title">{{ video.title }}</h4>
              <div class="recent-meta">
                <el-tag :type="video.status == 1 ? 'success' : 'info'" size="small">
                  {{ video.status == 1 ? '上架' : '下架' }}
                </el-tag>
                <span class="recent-time">{{ video.updated_at }}</span>
              </div>
            </div>
            <el-icon class="recent-arrow"><ArrowRight /></el-icon>
          </div>
        </div>
        <el-empty v-else description="暂无影片数据" :image-size="60" />
      </el-card>

      <el-card class="section-card" shadow="never" v-loading="recommendLoading">
        <template #header>
          <div class="section-header">
            <h3 class="section-title">
              <el-icon color="#f59e0b"><Star /></el-icon>
              推荐影片
            </h3>
            <el-button text type="primary" @click="goToAddVideo">添加推荐</el-button>
          </div>
        </template>
        <div class="recommend-grid" v-if="recommendVideos.length > 0">
          <div
            v-for="video in recommendVideos"
            :key="video.id"
            class="recommend-card"
            @click="handleEdit(video)"
          >
            <div class="recommend-cover">
              <img
                v-if="video.cover_url"
                :src="getCoverUrl(video.cover_url)"
                :alt="video.title"
                @error="handleImageError"
              />
              <div v-else class="cover-placeholder">
                <el-icon :size="24" color="#94a3b8"><Film /></el-icon>
              </div>
              <div class="recommend-badge">
                <el-icon :size="12"><Star /></el-icon>
              </div>
            </div>
            <p class="recommend-title" :title="video.title">{{ video.title }}</p>
          </div>
        </div>
        <el-empty v-else description="暂无推荐影片" :image-size="60">
          <el-button type="primary" size="small" @click="goToAddVideo">去添加</el-button>
        </el-empty>
      </el-card>
    </div>

    <div class="quick-actions">
      <div class="action-card" @click="goToVideos">
        <div class="action-icon" style="background: rgba(99,102,241,0.1);">
          <el-icon :size="24" color="#6366f1"><Film /></el-icon>
        </div>
        <div class="action-info">
          <h4>影片管理</h4>
          <p>查看和管理所有影片</p>
        </div>
      </div>
      <div class="action-card" @click="goToAddVideo">
        <div class="action-icon" style="background: rgba(16,185,129,0.1);">
          <el-icon :size="24" color="#10b981"><Plus /></el-icon>
        </div>
        <div class="action-info">
          <h4>新增影片</h4>
          <p>添加新的影片资源</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Film, CircleCheck, Remove, Link, ArrowRight, Star, Plus } from '@element-plus/icons-vue'
import { useDashboard } from '../composables/useDashboard'
import { useCoverUrl } from '../composables/useCoverUrl'

const router = useRouter()
const { getCoverUrl, handleImageError } = useCoverUrl()

const {
  statsLoading,
  recentLoading,
  recommendLoading,
  stats,
  recentVideos,
  recommendVideos,
  fetchAll
} = useDashboard()

const goToVideos = () => {
  router.push('/videos')
}

const goToAddVideo = () => {
  router.push('/videos/new')
}

const handleEdit = (video) => {
  router.push(`/videos/${video.id}/edit`)
}

onMounted(() => {
  fetchAll()
})
</script>

<style scoped>
.dashboard {
  max-width: 1100px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  border-radius: 12px;
  border: 1px solid #f0f0f0;
}

.stat-card :deep(.el-card__body) {
  padding: 20px;
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 16px;
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

.stat-label {
  margin: 0 0 4px;
  font-size: 13px;
  color: #94a3b8;
}

.stat-value {
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}

.section-card {
  border-radius: 12px;
  border: 1px solid #f0f0f0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 6px;
}

.recent-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.recent-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}

.recent-item:hover {
  background: #f8fafc;
}

.recent-cover {
  width: 60px;
  height: 36px;
  border-radius: 6px;
  overflow: hidden;
  flex-shrink: 0;
  background: #f0f0f0;
}

.recent-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.cover-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
}

.recent-info {
  flex: 1;
  min-width: 0;
}

.recent-title {
  margin: 0 0 4px;
  font-size: 14px;
  font-weight: 500;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.recent-meta {
  display: flex;
  align-items: center;
  gap: 8px;
}

.recent-time {
  font-size: 12px;
  color: #94a3b8;
}

.recent-arrow {
  color: #cbd5e1;
  font-size: 14px;
  flex-shrink: 0;
}

.recommend-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

.recommend-card {
  cursor: pointer;
  border-radius: 8px;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.recommend-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.recommend-cover {
  position: relative;
  width: 100%;
  padding-top: 56.25%;
  background: #f0f0f0;
  border-radius: 8px;
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

.recommend-cover .cover-placeholder {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border-radius: 0;
}

.recommend-badge {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 22px;
  height: 22px;
  background: linear-gradient(135deg, #f59e0b, #f97316);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.recommend-title {
  margin: 8px 0 0;
  font-size: 13px;
  font-weight: 500;
  color: #334155;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: center;
}

.quick-actions {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.action-card {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid #f0f0f0;
}

.action-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
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
  font-size: 13px;
  color: #94a3b8;
}

@media (max-width: 900px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .content-grid {
    grid-template-columns: 1fr;
  }
  .recommend-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>
