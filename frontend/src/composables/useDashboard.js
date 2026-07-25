import { ref } from 'vue'
import {
  getDashboardStats,
  getDashboardRecent,
  getDashboardRecommend
} from '../api'

// 首页数据看板业务逻辑：统计、最近更新、推荐影片的请求与汇总
export function useDashboard() {
  const loading = ref(false)

  // 统计卡片数据
  const stats = ref({
    video_total: 0,
    online_total: 0,
    offline_total: 0,
    source_total: 0
  })

  const recentVideos = ref([]) // 最近更新影片
  const recommendVideos = ref([]) // 推荐影片（推荐且上架）

  // 并行拉取并汇总所有看板数据
  const fetchDashboard = async ({ recentLimit = 5, recommendLimit = 8 } = {}) => {
    loading.value = true
    try {
      const [statsRes, recentRes, recommendRes] = await Promise.all([
        getDashboardStats(),
        getDashboardRecent(recentLimit),
        getDashboardRecommend(recommendLimit)
      ])
      stats.value = statsRes.data
      recentVideos.value = recentRes.data.list
      recommendVideos.value = recommendRes.data.list
    } catch (error) {
      console.error('获取看板数据失败：', error)
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    stats,
    recentVideos,
    recommendVideos,
    fetchDashboard
  }
}
