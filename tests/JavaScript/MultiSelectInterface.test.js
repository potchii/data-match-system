/**
 * Unit tests for MultiSelectInterface component
 * Tests checkbox selection, Select All functionality, and pagination persistence
 */

const MultiSelectInterface = require('../../resources/js/components/MultiSelectInterface')

describe('MultiSelectInterface Component', () => {
    let multiSelect
    let tableHTML

    beforeEach(() => {
        tableHTML = `
      <table id="testTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr data-record-id="1">
            <td>1</td>
            <td>John Doe</td>
            <td>Active</td>
          </tr>
          <tr data-record-id="2">
            <td>2</td>
            <td>Jane Smith</td>
            <td>Active</td>
          </tr>
          <tr data-record-id="3">
            <td>3</td>
            <td>Bob Johnson</td>
            <td>Inactive</td>
          </tr>
        </tbody>
      </table>
    `

        document.body.innerHTML = tableHTML

        multiSelect = new MultiSelectInterface({
            tableId: 'testTable',
        })
    })

    afterEach(() => {
        document.body.innerHTML = ''
    })

    describe('Initialization', () => {
        it('should create checkbox column in table header', () => {
            const headerCheckbox = document.getElementById('selectAllCheckbox')
            expect(headerCheckbox).toBeTruthy()
            expect(headerCheckbox.type).toBe('checkbox')
        })

        it('should add checkboxes to all table rows', () => {
            const recordCheckboxes = document.querySelectorAll('.record-checkbox')
            expect(recordCheckboxes.length).toBe(3)
        })

        it('should set correct data-record-id on checkboxes', () => {
            const checkbox1 = document.querySelector('[data-record-id="1"]')
            const checkbox2 = document.querySelector('[data-record-id="2"]')
            const checkbox3 = document.querySelector('[data-record-id="3"]')

            expect(checkbox1).toBeTruthy()
            expect(checkbox2).toBeTruthy()
            expect(checkbox3).toBeTruthy()
        })

        it('should initialize with empty selection', () => {
            expect(multiSelect.getSelectionCount()).toBe(0)
            expect(multiSelect.getSelectedRecords()).toEqual([])
        })
    })

    describe('Individual Record Selection', () => {
        it('should select record when checkbox is checked', () => {
            multiSelect.selectRecords([1])

            expect(multiSelect.isRecordSelected(1)).toBe(true)
            expect(multiSelect.getSelectionCount()).toBe(1)
        })

        it('should deselect record when checkbox is unchecked', () => {
            multiSelect.selectRecords([1])
            multiSelect.deselectRecords([1])

            expect(multiSelect.isRecordSelected(1)).toBe(false)
            expect(multiSelect.getSelectionCount()).toBe(0)
        })

        it('should add table-active class to selected row', () => {
            multiSelect.selectRecords([1])

            const checkbox = document.querySelector('[data-record-id="1"]')
            const row = checkbox.closest('tr')

            expect(row.classList.contains('table-active')).toBe(true)
        })

        it('should remove table-active class from deselected row', () => {
            multiSelect.selectRecords([1])
            multiSelect.deselectRecords([1])

            const checkbox = document.querySelector('[data-record-id="1"]')
            const row = checkbox.closest('tr')

            expect(row.classList.contains('table-active')).toBe(false)
        })

        it('should toggle record selection', () => {
            multiSelect.toggleRecord(1)
            expect(multiSelect.isRecordSelected(1)).toBe(true)

            multiSelect.toggleRecord(1)
            expect(multiSelect.isRecordSelected(1)).toBe(false)
        })
    })

    describe('Select All Functionality', () => {
        it('should select all records when Select All is checked', () => {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox')
            selectAllCheckbox.checked = true
            selectAllCheckbox.dispatchEvent(new Event('change', { bubbles: true }))

            expect(multiSelect.getSelectionCount()).toBe(3)
            expect(multiSelect.isRecordSelected(1)).toBe(true)
            expect(multiSelect.isRecordSelected(2)).toBe(true)
            expect(multiSelect.isRecordSelected(3)).toBe(true)
        })

        it('should deselect all records when Select All is unchecked', () => {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox')

            selectAllCheckbox.checked = true
            selectAllCheckbox.dispatchEvent(new Event('change', { bubbles: true }))

            selectAllCheckbox.checked = false
            selectAllCheckbox.dispatchEvent(new Event('change', { bubbles: true }))

            expect(multiSelect.getSelectionCount()).toBe(0)
        })

        it('should set indeterminate state when some records are selected', () => {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox')
            multiSelect.selectRecords([1])

            expect(selectAllCheckbox.indeterminate).toBe(true)
        })

        it('should toggle Select All', () => {
            multiSelect.toggleSelectAll()
            expect(multiSelect.getSelectionCount()).toBe(3)

            multiSelect.toggleSelectAll()
            expect(multiSelect.getSelectionCount()).toBe(0)
        })
    })

    describe('Selection State Management', () => {
        it('should return array of selected record IDs', () => {
            multiSelect.selectRecords([1, 2])

            const selected = multiSelect.getSelectedRecords()
            expect(selected).toContain(1)
            expect(selected).toContain(2)
            expect(selected.length).toBe(2)
        })

        it('should clear all selections', () => {
            multiSelect.selectRecords([1, 2, 3])
            multiSelect.clearSelection()

            expect(multiSelect.getSelectionCount()).toBe(0)
            const selectAllCheckbox = document.getElementById('selectAllCheckbox')
            expect(selectAllCheckbox.checked).toBe(false)
            expect(selectAllCheckbox.indeterminate).toBe(false)
        })

        it('should select specific records', () => {
            multiSelect.selectRecords([1, 3])

            expect(multiSelect.isRecordSelected(1)).toBe(true)
            expect(multiSelect.isRecordSelected(2)).toBe(false)
            expect(multiSelect.isRecordSelected(3)).toBe(true)
            expect(multiSelect.getSelectionCount()).toBe(2)
        })

        it('should deselect specific records', () => {
            multiSelect.selectRecords([1, 2, 3])
            multiSelect.deselectRecords([2])

            expect(multiSelect.isRecordSelected(1)).toBe(true)
            expect(multiSelect.isRecordSelected(2)).toBe(false)
            expect(multiSelect.isRecordSelected(3)).toBe(true)
            expect(multiSelect.getSelectionCount()).toBe(2)
        })
    })

    describe('Selection Persistence', () => {
        it('should restore selection state after refresh', () => {
            multiSelect.selectRecords([1, 2])
            multiSelect.refreshCheckboxes()

            expect(multiSelect.isRecordSelected(1)).toBe(true)
            expect(multiSelect.isRecordSelected(2)).toBe(true)
            expect(multiSelect.isRecordSelected(3)).toBe(false)
        })

        it('should maintain selection across multiple refreshes', () => {
            multiSelect.selectRecords([1, 3])

            multiSelect.refreshCheckboxes()
            expect(multiSelect.getSelectionCount()).toBe(2)

            multiSelect.refreshCheckboxes()
            expect(multiSelect.getSelectionCount()).toBe(2)
            expect(multiSelect.isRecordSelected(1)).toBe(true)
            expect(multiSelect.isRecordSelected(3)).toBe(true)
        })
    })

    describe('Page Selection', () => {
        it('should track page selection separately', () => {
            multiSelect.selectRecords([1, 2])

            const pageSelection = multiSelect.getPageSelection()
            expect(pageSelection).toContain(1)
            expect(pageSelection).toContain(2)
            expect(pageSelection.length).toBe(2)
        })

        it('should clear page selection without affecting global selection', () => {
            multiSelect.selectRecords([1, 2, 3])
            multiSelect.clearPageSelection()

            expect(multiSelect.getSelectionCount()).toBe(0)
            expect(multiSelect.getPageSelection().length).toBe(0)
        })
    })

    describe('Callbacks', () => {
        it('should call onSelectionChanged callback when selection changes', () => {
            const onSelectionChanged = jest.fn()
            multiSelect.onSelectionChanged = onSelectionChanged

            multiSelect.selectRecords([1])

            expect(onSelectionChanged).toHaveBeenCalled()
            expect(onSelectionChanged).toHaveBeenCalledWith([1])
        })

        it('should call onSelectAllChanged callback when Select All changes', () => {
            const onSelectAllChanged = jest.fn()
            multiSelect.onSelectAllChanged = onSelectAllChanged

            const selectAllCheckbox = document.getElementById('selectAllCheckbox')
            selectAllCheckbox.checked = true
            selectAllCheckbox.dispatchEvent(new Event('change', { bubbles: true }))

            expect(onSelectAllChanged).toHaveBeenCalled()
            expect(onSelectAllChanged).toHaveBeenCalledWith(true)
        })
    })

    describe('Edge Cases', () => {
        it('should handle empty table', () => {
            document.body.innerHTML = `
        <table id="emptyTable">
          <thead><tr><th>ID</th></tr></thead>
          <tbody></tbody>
        </table>
      `

            const emptySelect = new MultiSelectInterface({
                tableId: 'emptyTable',
            })

            expect(emptySelect.getSelectionCount()).toBe(0)
            expect(emptySelect.getSelectedRecords()).toEqual([])
        })

        it('should handle selecting non-existent record', () => {
            multiSelect.selectRecords([999])

            expect(multiSelect.isRecordSelected(999)).toBe(true)
            expect(multiSelect.getSelectionCount()).toBe(1)
        })

        it('should handle deselecting non-existent record', () => {
            multiSelect.deselectRecords([999])

            expect(multiSelect.isRecordSelected(999)).toBe(false)
            expect(multiSelect.getSelectionCount()).toBe(0)
        })
    })
})
