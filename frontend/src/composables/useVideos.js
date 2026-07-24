import { ref, reactive } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getVideoList,
  deleteVideo,
  updateVideoStatus,
  batchUpdateVideoStatus,
  batchDeleteVideos
} from '../api'

export function useVideos() {
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

  const fetchData = async () => {
    loading.value = true
    try {
      const params = {}
      if (queryForm.keyword) params.keyword = queryForm.keyword
      if (queryForm.status !== '') params.status = queryForm.status
      if (queryForm.has_source !== '') params.has_source = queryForm.has_source
      params.page = queryForm.page
      params.page_size = queryForm.page_size

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
    selectedRows.value = []
    fetchData()
  }

  const handleReset = () => {
    queryForm.keyword = ''
    queryForm.status = ''
    queryForm.has_source = ''
    handleQuery()
  }

  const handlePageChange = () => {
    fetchData()
  }

  const handleSizeChange = () => {
    queryForm.page = 1
    fetchData()
  }

  const handleSelectionChange = (rows) => {
    selectedRows.value = rows
  }

  const handleToggleStatus = async (row) => {
    const newStatus = row.status == 1 ? 0 : 1
    const action = newStatus == 1 ? '上架' : '下架'

    try {
      await ElMessageBox.confirm(`确定要${action}该影片吗？`, '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })

      await updateVideoStatus(row.id, newStatus)
      ElMessage.success(`${action}成功`)
      await fetchData()
    } catch (error) {
      if (error !== 'cancel') {
        console.error(`${action}失败：`, error)
      }
    }
  }

  const handleDelete = async (row) => {
    try {
      await ElMessageBox.confirm('确定要删除该影片吗？删除后将无法恢复！', '警告', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'error'
      })

      await deleteVideo(row.id)
      ElMessage.success('删除成功')
      selectedRows.value = selectedRows.value.filter(r => r.id !== row.id)
      fetchData()
    } catch (error) {
      if (error !== 'cancel') {
        console.error('删除失败：', error)
      }
    }
  }

  const confirmBatchAction = async (message, type = 'warning') => {
    try {
      await ElMessageBox.confirm(message, '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type
      })
      return true
    } catch {
      return false
    }
  }

  const handleBatchPublish = async () => {
    const ids = selectedRows.value.map(r => r.id)
    if (ids.length === 0) {
      ElMessage.warning('请先选择要操作的影片')
      return
    }

    const needConfirm = ids.length > 20
    if (needConfirm) {
      const confirmed = await confirmBatchAction(
        `您选择了 ${ids.length} 部影片进行批量上架，数据量较大，确定继续吗？`,
        'warning'
      )
      if (!confirmed) return
    }

    try {
      await batchUpdateVideoStatus(ids, 1)
      ElMessage.success(`批量上架成功，共 ${ids.length} 部`)
      selectedRows.value = []
      fetchData()
    } catch (error) {
      console.error('批量上架失败：', error)
    }
  }

  const handleBatchUnpublish = async () => {
    const ids = selectedRows.value.map(r => r.id)
    if (ids.length === 0) {
      ElMessage.warning('请先选择要操作的影片')
      return
    }

    const needConfirm = ids.length > 20
    if (needConfirm) {
      const confirmed = await confirmBatchAction(
        `您选择了 ${ids.length} 部影片进行批量下架，数据量较大，确定继续吗？`,
        'warning'
      )
      if (!confirmed) return
    }

    try {
      await batchUpdateVideoStatus(ids, 0)
      ElMessage.success(`批量下架成功，共 ${ids.length} 部`)
      selectedRows.value = []
      fetchData()
    } catch (error) {
      console.error('批量下架失败：', error)
    }
  }

  const handleBatchDelete = async () => {
    const ids = selectedRows.value.map(r => r.id)
    if (ids.length === 0) {
      ElMessage.warning('请先选择要删除的影片')
      return
    }

    const confirmed = await confirmBatchAction(
      `确定要批量删除选中的 ${ids.length} 部影片吗？删除后将无法恢复！`,
      'error'
    )
    if (!confirmed) return

    try {
      await batchDeleteVideos(ids)
      ElMessage.success(`批量删除成功，共 ${ids.length} 部`)
      selectedRows.value = []
      fetchData()
    } catch (error) {
      console.error('批量删除失败：', error)
    }
  }

  return {
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
  }
}
