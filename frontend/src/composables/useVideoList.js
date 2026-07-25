import { ref, reactive } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getVideoList,
  deleteVideo,
  updateVideoStatus,
  batchUpdateVideoStatus,
  batchDeleteVideos
} from '../api'

// 批量上下架超过该数量时需二次确认
const BATCH_CONFIRM_THRESHOLD = 20

// 影片列表业务逻辑：数据获取、筛选条件组装、单条 & 批量操作
export function useVideoList() {
  const loading = ref(false)
  const tableData = ref([])
  const total = ref(0)
  const selectedRows = ref([])

  // 筛选 / 分页条件
  const queryForm = reactive({
    page: 1,
    page_size: 10,
    keyword: '',
    status: '',
    has_source: '' // '1'有 '0'无 ''全部
  })

  // 组装请求参数（去除空值）
  const buildParams = () => {
    const params = {
      page: queryForm.page,
      page_size: queryForm.page_size
    }
    if (queryForm.keyword !== '') params.keyword = queryForm.keyword
    if (queryForm.status !== '') params.status = queryForm.status
    if (queryForm.has_source !== '') params.has_source = queryForm.has_source
    return params
  }

  const fetchData = async () => {
    loading.value = true
    try {
      const res = await getVideoList(buildParams())
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

  const selectedIds = () => selectedRows.value.map((r) => r.id)

  // 单条上下架
  const toggleStatus = async (row) => {
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
      // 用户取消无需提示；后端错误已由请求拦截器统一提示
      if (error !== 'cancel') {
        console.error(`${action}失败：`, error)
      }
    }
  }

  // 单条删除
  const removeVideo = async (row) => {
    try {
      await ElMessageBox.confirm('确定要删除该影片吗？删除后将无法恢复！', '警告', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'error'
      })
      await deleteVideo(row.id)
      ElMessage.success('删除成功')
      await fetchData()
    } catch (error) {
      if (error !== 'cancel') {
        console.error('删除失败：', error)
      }
    }
  }

  // 批量上下架
  const batchToggleStatus = async (status) => {
    const ids = selectedIds()
    if (ids.length === 0) {
      ElMessage.warning('请先选择影片')
      return
    }
    const action = status == 1 ? '上架' : '下架'

    // 一次勾选超过阈值需二次确认
    if (ids.length > BATCH_CONFIRM_THRESHOLD) {
      try {
        await ElMessageBox.confirm(
          `已选择 ${ids.length} 部影片，确定要批量${action}吗？`,
          '二次确认',
          { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' }
        )
      } catch (e) {
        return
      }
    }

    try {
      await batchUpdateVideoStatus(ids, status)
      ElMessage.success(`批量${action}成功`)
      await fetchData()
    } catch (error) {
      // 后端可能因「推荐且上架」超限而拒绝，提示已由请求拦截器统一给出
      console.error(`批量${action}失败：`, error)
    }
  }

  // 批量删除（始终二次确认）
  const batchRemove = async () => {
    const ids = selectedIds()
    if (ids.length === 0) {
      ElMessage.warning('请先选择影片')
      return
    }
    try {
      await ElMessageBox.confirm(
        `确定要删除选中的 ${ids.length} 部影片吗？删除后将无法恢复！`,
        '删除二次确认',
        { confirmButtonText: '确定删除', cancelButtonText: '取消', type: 'error' }
      )
    } catch (e) {
      return
    }
    try {
      await batchDeleteVideos(ids)
      ElMessage.success('批量删除成功')
      await fetchData()
    } catch (error) {
      console.error('批量删除失败：', error)
    }
  }

  return {
    loading,
    tableData,
    total,
    queryForm,
    selectedRows,
    fetchData,
    handleQuery,
    handleReset,
    handlePageChange,
    handleSizeChange,
    handleSelectionChange,
    toggleStatus,
    removeVideo,
    batchToggleStatus,
    batchRemove
  }
}
