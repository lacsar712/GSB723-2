import { ref } from 'vue'
import { getDashboardStats, getRecentVideos, getRecommendVideos } from '../api'

export function useDashboard() {
  const statsLoading = ref(false)
  const recentLoading = ref(false)
  const recommendLoading = ref(false)

  const stats = ref({
    total_videos: 0,
    published_count: 0,
    unpublished_count: 0,
    total_sources: 0
  })

  const recentVideos = ref([])
  const recommendVideos = ref([])

  const fetchStats = async () => {
    statsLoading.value = true
    try {
      const res = await getDashboardStats()
      stats.value = res.data
    } catch (error) {
      console.error('获取统计数据失败：', error)
    } finally {
      statsLoading.value = false
    }
  }

  const fetchRecentVideos = async (limit = 8) => {
    recentLoading.value = true
    try {
      const res = await getRecentVideos(limit)
      recentVideos.value = res.data.list
    } catch (error) {
      console.error('获取最近更新影片失败：', error)
    } finally {
      recentLoading.value = false
    }
  }

  const fetchRecommendVideos = async (limit = 8) => {
    recommendLoading.value = true
    try {
      const res = await getRecommendVideos(limit)
      recommendVideos.value = res.data.list
    } catch (error) {
      console.error('获取推荐影片失败：', error)
    } finally {
      recommendLoading.value = false
    }
  }

  const fetchAll = async () => {
    await Promise.all([
      fetchStats(),
      fetchRecentVideos(),
      fetchRecommendVideos()
    ])
  }

  return {
    statsLoading,
    recentLoading,
    recommendLoading,
    stats,
    recentVideos,
    recommendVideos,
    fetchStats,
    fetchRecentVideos,
    fetchRecommendVideos,
    fetchAll
  }
}
