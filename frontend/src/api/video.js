import request from '../utils/request'

export function getVideoList(params) {
  return request({
    url: '/videos',
    method: 'get',
    params
  })
}

export function getVideoDetail(id) {
  return request({
    url: `/videos/${id}`,
    method: 'get'
  })
}

export function createVideo(data) {
  const formData = new FormData()
  formData.append('title', data.title)
  formData.append('cover_url', data.cover_url)
  formData.append('description', data.description || '')
  formData.append('status', data.status)
  formData.append('is_recommended', data.is_recommended ? 1 : 0)

  return request({
    url: '/videos',
    method: 'post',
    data: formData
  })
}

export function updateVideo(id, data) {
  const formData = new FormData()
  formData.append('title', data.title)
  formData.append('cover_url', data.cover_url)
  formData.append('description', data.description || '')
  formData.append('status', data.status)
  formData.append('is_recommended', data.is_recommended ? 1 : 0)

  return request({
    url: `/videos/${id}`,
    method: 'post',
    data: formData
  })
}

export function deleteVideo(id) {
  return request({
    url: `/videos/${id}`,
    method: 'delete'
  })
}

export function updateVideoStatus(id, status) {
  const formData = new FormData()
  formData.append('status', status)

  return request({
    url: `/videos/${id}/status`,
    method: 'post',
    data: formData
  })
}

export function batchUpdateVideoStatus(ids, status) {
  const formData = new FormData()
  formData.append('ids', JSON.stringify(ids))
  formData.append('status', status)

  return request({
    url: '/videos/batch-status',
    method: 'post',
    data: formData
  })
}

export function batchDeleteVideos(ids) {
  const formData = new FormData()
  formData.append('ids', JSON.stringify(ids))

  return request({
    url: '/videos/batch-delete',
    method: 'post',
    data: formData
  })
}

export function getDashboardStats() {
  return request({
    url: '/videos/dashboard',
    method: 'get'
  })
}

export function getSourceList(videoId) {
  return request({
    url: '/sources',
    method: 'get',
    params: { video_id: videoId }
  })
}

export function createSource(data) {
  const formData = new FormData()
  formData.append('video_id', data.video_id)
  formData.append('source_name', data.source_name)
  formData.append('m3u8_url', data.m3u8_url)

  return request({
    url: '/sources',
    method: 'post',
    data: formData
  })
}

export function updateSource(id, data) {
  const formData = new FormData()
  formData.append('source_name', data.source_name)
  formData.append('m3u8_url', data.m3u8_url)

  return request({
    url: `/sources/${id}`,
    method: 'post',
    data: formData
  })
}

export function deleteSource(id) {
  return request({
    url: `/sources/${id}`,
    method: 'delete'
  })
}
