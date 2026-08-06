import { reportsApi } from './api'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

export async function exportReport(type: string, format: 'csv' | 'excel' | 'pdf') {
  const res = await reportsApi.generate(type)
  const data = res.data.data as Record<string, unknown>[]
  if (!data.length) return

  const filename = `${type}-report-${new Date().toISOString().slice(0, 10)}`

  if (format === 'csv') {
    const headers = Object.keys(data[0])
    const csv = [headers.join(','), ...data.map(row => headers.map(h => JSON.stringify(row[h] ?? '')).join(','))].join('\n')
    downloadBlob(new Blob([csv], { type: 'text/csv' }), `${filename}.csv`)
  } else if (format === 'excel') {
    const ws = XLSX.utils.json_to_sheet(data)
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, type)
    XLSX.writeFile(wb, `${filename}.xlsx`)
  } else if (format === 'pdf') {
    const doc = new jsPDF()
    doc.text(`${type.charAt(0).toUpperCase() + type.slice(1)} Report`, 14, 20)
    const headers = Object.keys(data[0])
    autoTable(doc, {
      head: [headers],
      body: data.map(row => headers.map(h => String(row[h] ?? ''))),
      startY: 30,
    })
    doc.save(`${filename}.pdf`)
  }
}

function downloadBlob(blob: Blob, filename: string) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}
