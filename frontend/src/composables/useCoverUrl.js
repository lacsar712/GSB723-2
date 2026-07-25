// 封面 URL 处理：拼接 API 基础地址 & 图片加载失败占位
const PLACEHOLDER =
  'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="100" height="100"%3E%3Crect fill="%23f5f5f5" width="100" height="100"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999"%3E加载失败%3C/text%3E%3C/svg%3E'

// 获取封面完整 URL
export function useCoverUrl() {
  const getCoverUrl = (url) => {
    if (!url) return ''
    // 完整 URL 直接返回
    if (url.startsWith('http://') || url.startsWith('https://')) {
      return url
    }
    // 相对路径拼接 API 基础地址
    const baseURL = import.meta.env.VITE_API_BASE_URL || ''
    return baseURL ? `${baseURL}${url}` : url
  }

  // 图片加载失败占位
  const handleImageError = (e) => {
    e.target.src = PLACEHOLDER
  }

  return { getCoverUrl, handleImageError }
}
