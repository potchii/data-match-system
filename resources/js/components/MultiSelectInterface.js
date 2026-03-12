/**
 * Multi-Select Interface Component
 * Handles checkbox-based record selection with "Select All" functionality
 * Persists selection state across pagination
 */

class MultiSelectInterface {
    constructor(options = {}) {
        this.tableId = options.tableId || 'recordsTable'
        this.selectedRecords = new Set()
        this.pageSelection = new Set()
        this.isSelectAllIndeterminate = false
        this.onSelectionChanged = options.onSelectionChanged || (() => { })
        this.onSelectAllChanged = options.onSelectAllChanged || (() => { })
        this.eventListenerAttached = false

        this.initializeCheckboxes()
    }

    /**
     * Initialize checkbox column in table
     */
    initializeCheckboxes() {
        const table = document.getElementById(this.tableId)
        if (!table) return

        const thead = table.querySelector('thead')
        const tbody = table.querySelector('tbody')

        // Add checkbox header
        if (thead) {
            const headerRow = thead.querySelector('tr')
            if (headerRow) {
                const checkboxHeader = document.createElement('th')
                checkboxHeader.style.width = '40px'
                checkboxHeader.innerHTML = `
          <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="selectAllCheckbox">
            <label class="custom-control-label" for="selectAllCheckbox"></label>
          </div>
        `
                headerRow.insertBefore(checkboxHeader, headerRow.firstChild)
            }
        }

        // Add checkboxes to rows
        if (tbody) {
            const rows = tbody.querySelectorAll('tr')
            rows.forEach((row) => {
                const recordId = this.extractRecordId(row)
                if (recordId) {
                    const checkboxCell = document.createElement('td')
                    checkboxCell.style.width = '40px'
                    checkboxCell.innerHTML = `
            <div class="custom-control custom-checkbox">
              <input type="checkbox" class="custom-control-input record-checkbox" 
                     id="checkbox_${recordId}" data-record-id="${recordId}">
              <label class="custom-control-label" for="checkbox_${recordId}"></label>
            </div>
          `
                    row.insertBefore(checkboxCell, row.firstChild)

                    // Restore selection state if record was previously selected
                    if (this.selectedRecords.has(recordId)) {
                        const checkbox = checkboxCell.querySelector('input[type="checkbox"]')
                        checkbox.checked = true
                        row.classList.add('table-active')
                    }
                }
            })
        }
    }

    /**
     * Extract record ID from table row
     */
    extractRecordId(row) {
        if (row.dataset.recordId) {
            return parseInt(row.dataset.recordId)
        }

        const firstCell = row.querySelector('td:nth-child(2)')
        if (firstCell && firstCell.textContent) {
            const id = parseInt(firstCell.textContent.trim())
            if (!isNaN(id)) {
                return id
            }
        }

        return null
    }

    /**
     * Attach event listeners to checkboxes using event delegation
     */
    attachEventListeners() {
        const table = document.getElementById(this.tableId)
        if (!table || this.eventListenerAttached) return

        table.addEventListener('change', (e) => {
            if (e.target.id === 'selectAllCheckbox') {
                this.handleSelectAll(e)
            } else if (e.target.classList.contains('record-checkbox')) {
                this.handleRecordCheckbox(e)
            }
        })

        this.eventListenerAttached = true
    }

    /**
     * Handle Select All checkbox change
     */
    handleSelectAll(event) {
        const isChecked = event.target.checked
        const table = document.getElementById(this.tableId)
        const recordCheckboxes = table.querySelectorAll('.record-checkbox')

        recordCheckboxes.forEach((checkbox) => {
            checkbox.checked = isChecked
            const recordId = parseInt(checkbox.dataset.recordId)
            const row = checkbox.closest('tr')

            if (isChecked) {
                this.selectedRecords.add(recordId)
                this.pageSelection.add(recordId)
                row.classList.add('table-active')
            } else {
                this.selectedRecords.delete(recordId)
                this.pageSelection.delete(recordId)
                row.classList.remove('table-active')
            }
        })

        this.isSelectAllIndeterminate = false
        this.onSelectAllChanged(isChecked)
        this.onSelectionChanged(this.getSelectedRecords())
    }

    /**
     * Handle individual record checkbox change
     */
    handleRecordCheckbox(event) {
        const checkbox = event.target
        const recordId = parseInt(checkbox.dataset.recordId)
        const row = checkbox.closest('tr')

        if (checkbox.checked) {
            this.selectedRecords.add(recordId)
            this.pageSelection.add(recordId)
            row.classList.add('table-active')
        } else {
            this.selectedRecords.delete(recordId)
            this.pageSelection.delete(recordId)
            row.classList.remove('table-active')
        }

        this.updateSelectAllState()
        this.onSelectionChanged(this.getSelectedRecords())
    }

    /**
     * Update Select All checkbox state based on page selection
     */
    updateSelectAllState() {
        const table = document.getElementById(this.tableId)
        const selectAllCheckbox = document.getElementById('selectAllCheckbox')
        const recordCheckboxes = table.querySelectorAll('.record-checkbox')

        if (recordCheckboxes.length === 0) {
            selectAllCheckbox.checked = false
            selectAllCheckbox.indeterminate = false
            return
        }

        // Count how many records on this page are selected
        const pageRecordIds = Array.from(recordCheckboxes).map((cb) => parseInt(cb.dataset.recordId))
        const selectedOnPage = pageRecordIds.filter((id) => this.selectedRecords.has(id)).length

        if (selectedOnPage === 0) {
            selectAllCheckbox.checked = false
            selectAllCheckbox.indeterminate = false
        } else if (selectedOnPage === pageRecordIds.length) {
            selectAllCheckbox.checked = true
            selectAllCheckbox.indeterminate = false
        } else {
            selectAllCheckbox.checked = false
            selectAllCheckbox.indeterminate = true
        }
    }

    /**
     * Toggle selection of individual record
     */
    toggleRecord(recordId) {
        const checkbox = document.querySelector(`[data-record-id="${recordId}"]`)
        if (checkbox) {
            checkbox.checked = !checkbox.checked
            const row = checkbox.closest('tr')

            if (checkbox.checked) {
                this.selectedRecords.add(recordId)
                this.pageSelection.add(recordId)
                row.classList.add('table-active')
            } else {
                this.selectedRecords.delete(recordId)
                this.pageSelection.delete(recordId)
                row.classList.remove('table-active')
            }

            this.updateSelectAllState()
            this.onSelectionChanged(this.getSelectedRecords())
        }
    }

    /**
     * Toggle Select All
     */
    toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAllCheckbox')
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = !selectAllCheckbox.checked
            selectAllCheckbox.dispatchEvent(new Event('change', { bubbles: true }))
        }
    }

    /**
     * Clear all selections
     */
    clearSelection() {
        const table = document.getElementById(this.tableId)
        const allCheckboxes = table.querySelectorAll('input[type="checkbox"]')

        allCheckboxes.forEach((checkbox) => {
            checkbox.checked = false
        })

        this.selectedRecords.clear()
        this.pageSelection.clear()
        this.isSelectAllIndeterminate = false

        const selectAllCheckbox = document.getElementById('selectAllCheckbox')
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false
            selectAllCheckbox.indeterminate = false
        }

        table.querySelectorAll('tr').forEach((row) => {
            row.classList.remove('table-active')
        })

        this.onSelectionChanged(this.getSelectedRecords())
    }

    /**
     * Get array of selected record IDs
     */
    getSelectedRecords() {
        return Array.from(this.selectedRecords)
    }

    /**
     * Get count of selected records
     */
    getSelectionCount() {
        return this.selectedRecords.size
    }

    /**
     * Check if record is selected
     */
    isRecordSelected(recordId) {
        return this.selectedRecords.has(recordId)
    }

    /**
     * Select specific records
     */
    selectRecords(recordIds) {
        recordIds.forEach((recordId) => {
            this.selectedRecords.add(recordId)
            this.pageSelection.add(recordId)
            const checkbox = document.querySelector(`[data-record-id="${recordId}"]`)
            if (checkbox) {
                checkbox.checked = true
                checkbox.closest('tr').classList.add('table-active')
            }
        })

        this.updateSelectAllState()
        this.onSelectionChanged(this.getSelectedRecords())
    }

    /**
     * Deselect specific records
     */
    deselectRecords(recordIds) {
        recordIds.forEach((recordId) => {
            this.selectedRecords.delete(recordId)
            this.pageSelection.delete(recordId)
            const checkbox = document.querySelector(`[data-record-id="${recordId}"]`)
            if (checkbox) {
                checkbox.checked = false
                checkbox.closest('tr').classList.remove('table-active')
            }
        })

        this.updateSelectAllState()
        this.onSelectionChanged(this.getSelectedRecords())
    }

    /**
     * Refresh checkboxes after table update (e.g., pagination)
     */
    refreshCheckboxes() {
        const table = document.getElementById(this.tableId)
        if (!table) return

        // Remove old checkboxes
        const oldCheckboxes = table.querySelectorAll('.custom-control')
        oldCheckboxes.forEach((checkbox) => {
            const cell = checkbox.closest('th, td')
            if (cell) {
                cell.remove()
            }
        })

        // Re-initialize
        this.eventListenerAttached = false
        this.initializeCheckboxes()
        this.attachEventListeners()
        this.updateSelectAllState()
    }

    /**
     * Get page selection (records selected on current page only)
     */
    getPageSelection() {
        return Array.from(this.pageSelection)
    }

    /**
     * Clear page selection (for current page only)
     */
    clearPageSelection() {
        const table = document.getElementById(this.tableId)
        const recordCheckboxes = table.querySelectorAll('.record-checkbox')

        recordCheckboxes.forEach((checkbox) => {
            const recordId = parseInt(checkbox.dataset.recordId)
            this.selectedRecords.delete(recordId)
            this.pageSelection.delete(recordId)
            checkbox.checked = false
            checkbox.closest('tr').classList.remove('table-active')
        })

        this.updateSelectAllState()
        this.onSelectionChanged(this.getSelectedRecords())
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MultiSelectInterface
}
