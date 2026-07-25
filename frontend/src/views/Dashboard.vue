<template>
  <div class="dashboard" v-loading="loading">
    <div class="welcome-banner">
      <div class="welcome-text">
        <h2>数据看板</h2>
        <p>影视管理后台 — 实时掌握影片运营状况</p>
      </div>
    </div>

    <!-- 统计卡片 -->
    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background: rgba(99,102,241,0.1);">
          <el-icon :size="26" color="#6366f1"><Film /></el-icon>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.video_total }}</div>
          <div class="stat-label">影片总数</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16,185,129,0.1);">
          <el-icon :size="26" color="#10b981"><VideoPlay /></el-icon>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.online_total }}</div>
          <div class="stat-label">上架数</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background: rgba(148,163,184,0.15);">
          <el-icon :size="26" color="#94a3b8"><Remove /></el-icon>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.offline_total }}</div>
          <div class="stat-label">下架数</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245,158,11,0.12);">
          <el-icon :size="26" color="#f59e0b"><Link /></el-icon>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.source_total }}</div>
          <div class="stat-label">播放源总数</div>
        </div>
      </div>
    </div>

    <div class="content-grid">
      <!-- 最近更新影片 -->
      <el-card class="panel-card" shadow="never">
        <h3 class="section-title">最近更新</h3>
        <el-table :data="recentVideos" size="small" @row-click="goEdit">
          <el-table-column prop="title" label="标题" min-width="160" show-overflow-tooltip />
          <el-table-column label="状态" width="90">
            <template #default="{ row }">
              <el-tag size="small" :type="row.status == 1 ? 'success' : 'info'">
                {{ row.status == 1 ? '上架' : '下架' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="updated_at" label="更新时间" width="170" />
        </el-table>
        <div v-if="recentVideos.length === 0" class="empty-tip">暂无数据</div>
      </el-card>

      <!-- 推荐影片 -->
      <el-card class="panel-card" shadow="never">
        <h3 class="section-title">推荐影片</h3>
        <div v-if="recommendVideos.length" class="recommend-grid">
          <div
            v-for="item in recommendVideos"
            :key="item.id"
            class="recommend-item"
            @click="goEdit(item)"
          >
            <img
              :src="getCoverUrl(item.cover_url)"
              :alt="item.title"
              class="recommend-cover"
              loading="lazy"
              @error="handleImageError"
            />
            <div class="recommend-title">{{ item.title }}</div>
          </div>
        </div>
        <div v-else class="empty-tip">暂无推荐影片</div>
      </el-card>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Film, VideoPlay, Remove, Link } from '@element-plus/icons-vue'
import { useDashboard } from '../composables/useDashboard'
import { useCoverUrl } from '../composables/useCoverUrl'

const router = useRouter()
const { getCoverUrl, handleImageError } = useCoverUrl()
const { loading, stats, recentVideos, recommendVideos, fetchDashboard } = useDashboard()

const goEdit = (row) => {
  router.push(`/videos/${row.id}/edit`)
}

onMounted(() => {
  fetchDashboard()
})
</script>

<style scoped>
.dashboard {
  max-width: 1100px;
}

.welcome-banner {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
  border-radius: 12px;
  padding: 32px;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;
}

.welcome-banner::after {
  content: '';
  position: absolute;
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: rgba(99, 102, 241, 0.15);
  top: -60px;
  right: -20px;
}

.welcome-text h2 {
  color: #fff;
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 8px;
}

.welcome-text p {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
  margin: 0;
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  border: 1px solid #f0f0f0;
}

.stat-icon {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-value {
  font-size: 26px;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
}

.stat-label {
  font-size: 13px;
  color: #94a3b8;
  margin-top: 2px;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.panel-card {
  border-radius: 12px;
  border: 1px solid #f0f0f0;
}

.section-title {
  margin: 0 0 16px;
  font-size: 16px;
  color: #1e293b;
  font-weight: 600;
}

.panel-card :deep(.el-table) {
  cursor: pointer;
}

.recommend-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 14px;
}

.recommend-item {
  cursor: pointer;
  transition: transform 0.2s;
}

.recommend-item:hover {
  transform: translateY(-3px);
}

.recommend-cover {
  width: 100%;
  height: 90px;
  object-fit: cover;
  border-radius: 8px;
  display: block;
  background: #f0f0ff;
}

.recommend-title {
  margin-top: 6px;
  font-size: 13px;
  color: #475569;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.empty-tip {
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
  padding: 24px 0;
}

@media (max-width: 900px) {
  .stat-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .content-grid {
    grid-template-columns: 1fr;
  }
}
</style>
