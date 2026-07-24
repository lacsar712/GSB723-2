import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getVideoList,
  deleteVideo,
  updateVideoStatus,
  batchUpdateVideoStatus,
  batchDeleteVideos
} from '../api'

const BULK_CONFIRM_THRESHOLD = 20

export function useVideoList() {
  const router = useRouter()
  const loading = ref(false)
  const tableData = ref([])
  const total = ref(0)
  const selectedRows = ref([])

  const queryForm = reactive({
    page: 1,
    page_size: 10,
    keyword: '',
    status: '',
    has_source: ''
  })

  const fetchList = async () => {
    loading.value = true
    try {
      const params = {
        page: queryForm.page,
        page_size: queryForm.page_size,
        keyword: queryForm.keyword || undefined,
        status: queryForm.status !== '' ? queryForm.status : undefined,
        has_source: queryForm.has_source !== '' ? queryForm.has_source : undefined
      }
      const res = await getVideoList(params)
      tableData.value = res.data.list
      total.value = res.data.total
    } catch (error) {
      console.error('获取列表失败：', error)
    } finally {
      loading.value = false
    }
  }

  const handleQuery = () => {
    queryForm.page = 1
    fetchList()
  }

  const handleReset = () => {
    queryForm.keyword = ''
    queryForm.status = ''
    queryForm.has_source = ''
    queryForm.page = 1
    fetchList()
  }

  const handlePageChange = () => {
    fetchList()
  }

  const handleSizeChange = () => {
    queryForm.page = 1
    fetchList()
  }

  const handleSelectionChange = (rows) => {
    selectedRows.value = rows
  }

  const goToEdit = (row) => {
    router.push(`/videos/${row.id}/edit`)
  }

  const goToSources = (row) => {
    router.push(`/videos/${row.id}/sources`)
  }

  const goToAdd = () => {
    router.push('/videos/new')
  }

  const toggleSingleStatus = async (row) => {
    const newStatus = row.status === 1 ? 0 : 1
    const action = newStatus === 1 ? '上架' : '下架'
    try {
      await ElMessageBox.confirm(`确定要${action}「${row.title}」吗？`, '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })
      await updateVideoStatus(row.id, newStatus)
      ElMessage.success(`${action}成功`)
      await fetchList()
    } catch (error) {
      if (error !== 'cancel') {
        ElMessage.error(`${action}失败：${error.message || '未知错误'}`)
      }
    }
  }

  const deleteSingle = async (row) => {
    try {
      await ElMessageBox.confirm(`确定要删除「${row.title}」吗？删除后将无法恢复！`, '警告', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'error'
      })
      await deleteVideo(row.id)
      ElMessage.success('删除成功')
      fetchList()
    } catch (error) {
      if (error !== 'cancel') {
        console.error('删除失败：', error)
      }
    }
  }

  const confirmBulkAction = async (message, type = 'warning') => {
    await ElMessageBox.confirm(message, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type
    })
  }

  const bulkPublish = async () => {
    const ids = selectedRows.value.map(r => r.id)
    if (ids.length === 0) {
      ElMessage.warning('请先选择影片')
      return
    }
    try {
      if (ids.length > BULK_CONFIRM_THRESHOLD) {
        await confirmBulkAction(
          `本次共选择 ${ids.length} 部影片进行批量上架，数量较多，是否继续？`,
          'warning'
        )
      }
      await batchUpdateVideoStatus(ids, 1)
      ElMessage.success('批量上架成功')
      selectedRows.value = []
      await fetchList()
    } catch (error) {
      if (error !== 'cancel') {
        ElMessage.error(`批量上架失败：${error.message || '未知错误'}`)
      }
    }
  }

  const bulkUnpublish = async () => {
    const ids = selectedRows.value.map(r => r.id)
    if (ids.length === 0) {
      ElMessage.warning('请先选择影片')
      return
    }
    try {
      if (ids.length > BULK_CONFIRM_THRESHOLD) {
        await confirmBulkAction(
          `本次共选择 ${ids.length} 部影片进行批量下架，数量较多，是否继续？`,
          'warning'
        )
      }
      await batchUpdateVideoStatus(ids, 0)
      ElMessage.success('批量下架成功')
      selectedRows.value = []
      await fetchList()
    } catch (error) {
      if (error !== 'cancel') {
        ElMessage.error(`批量下架失败：${error.message || '未知错误'}`)
      }
    }
  }

  const bulkDelete = async () => {
    const ids = selectedRows.value.map(r => r.id)
    if (ids.length === 0) {
      ElMessage.warning('请先选择影片')
      return
    }
    try {
      await confirmBulkAction(
        `确定要删除选中的 ${ids.length} 部影片吗？删除后播放源也会一并清除，且无法恢复！`,
        'error'
      )
      await batchDeleteVideos(ids)
      ElMessage.success('批量删除成功')
      selectedRows.value = []
      await fetchList()
    } catch (error) {
      if (error !== 'cancel') {
        ElMessage.error(`批量删除失败：${error.message || '未知错误'}`)
      }
    }
  }

  return {
    loading,
    tableData,
    total,
    selectedRows,
    queryForm,
    fetchList,
    handleQuery,
    handleReset,
    handlePageChange,
    handleSizeChange,
    handleSelectionChange,
    goToEdit,
    goToSources,
    goToAdd,
    toggleSingleStatus,
    deleteSingle,
    bulkPublish,
    bulkUnpublish,
    bulkDelete
  }
}
