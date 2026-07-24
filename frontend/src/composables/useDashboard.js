import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getDashboardStats } from '../api'

export function useDashboard() {
  const router = useRouter()
  const loading = ref(false)
  const stats = ref({
    total_videos: 0,
    published_count: 0,
    unpublished_count: 0,
    total_sources: 0,
    recommended_count: 0,
    max_recommended: 8,
    recent_videos: [],
    recommended_videos: []
  })

  const fetchStats = async () => {
    loading.value = true
    try {
      const res = await getDashboardStats()
      stats.value = res.data
    } catch (error) {
      console.error('获取看板数据失败：', error)
    } finally {
      loading.value = false
    }
  }

  const goToVideos = () => {
    router.push('/videos')
  }

  const goToAddVideo = () => {
    router.push('/videos/new')
  }

  const goToEdit = (id) => {
    router.push(`/videos/${id}/edit`)
  }

  const getCoverUrl = (url) => {
    if (!url) return ''
    if (url.startsWith('http://') || url.startsWith('https://')) {
      return url
    }
    const baseURL = import.meta.env.VITE_API_BASE_URL || ''
    return baseURL ? `${baseURL}${url}` : url
  }

  const getStatusLabel = (status) => {
    return status === 1 ? '上架' : '下架'
  }

  const getStatusType = (status) => {
    return status === 1 ? 'success' : 'info'
  }

  onMounted(() => {
    fetchStats()
  })

  return {
    loading,
    stats,
    fetchStats,
    goToVideos,
    goToAddVideo,
    goToEdit,
    getCoverUrl,
    getStatusLabel,
    getStatusType
  }
}
