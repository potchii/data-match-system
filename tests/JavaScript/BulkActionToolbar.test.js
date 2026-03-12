/**
 * Unit tests for BulkActionToolbar component
 * Tests toolbar visibility, bulk action controls, and progress tracking
 */

const BulkActionToolbar = require('../../resources/js/components/BulkActionToolbar')

describe('BulkActionToolbar Component', () => {
    let toolbar

    beforeEach(() => {
        document.body.innerHTML = ''

        toolbar = new BulkActionToolbar({
            toolbarId: 'testBulkActionToolbar',
            categories: ['Category A', 'Category B', 'Category C'],
            statuses: ['active', 'inactive', 'archived'],
        })
    })

    afterEach(() => {
        document.body.innerHTML = ''
    })

    describe('Initialization', () => {
        it('should create toolbar DOM structure', () => {
            const toolbarElement = document.getElementById('testBulkActionToolbar')
            expect(toolbarElement).toBeTruthy()
            expect(toolbarElement.classList.contains('bulk-action-toolbar')).toBe(true)
        })

        it('should have all required buttons', () => {
            const toolbarElement = document.getElementById('testBulkActionToolbar')
            const deleteBtn = toolbarElement.querySelector('#deleteSelectedBtn')
            const updateStatusBtn = toolbarElement.querySelector('#updateStatusBtn')
            const updateCategoryBtn = toolbarElement.querySelector('#updateCategoryBtn')
            const clearBtn = toolbarElement.querySelector('#clearSelectionBtn')

            expect(deleteBtn).toBeTruthy()
            expect(updateStatusBtn).toBeTruthy()
            expect(updateCategoryBtn).toBeTruthy()
            expect(clearBtn).toBeTruthy()
        })

        it('should have status dropdown options', () => {
            const toolbarElement = document.getElementById('testBulkActionToolbar')
            const statusOptions = toolbarElement.querySelectorAll('.status-option')

            expect(statusOptions.length).toBe(3)
            expect(statusOptions[0].dataset.status).toBe('active')
            expect(statusOptions[1].dataset.status).toBe('inactive')
            expect(statusOptions[2].dataset.status).toBe('archived')
        })

        it('should have category dropdown options', () => {
            const toolbarElement = document.getElementById('testBulkActionToolbar')
            const categoryOptions = toolbarElement.querySelectorAll('.category-option')

            expect(categoryOptions.length).toBe(3)
            expect(categoryOptions[0].dataset.category).toBe('Category A')
            expect(categoryOptions[1].dataset.category).toBe('Category B')
            expect(categoryOptions[2].dataset.category).toBe('Category C')
        })

        it('should be hidden by default', () => {
            const toolbarElement = document.getElementById('testBulkActionToolbar')
            expect(toolbarElement.style.display).toBe('none')
        })
    })

    describe('Toolbar Visibility', () => {
        it('should show toolbar with selection count', () => {
            toolbar.show(5)

            const toolbarElement = document.getElementById('testBulkActionToolbar')
            expect(toolbarElement.style.display).toBe('block')

            const countDisplay = toolbarElement.querySelector('#selectedCountDisplay')
            expect(countDisplay.textContent).toBe('5')
        })

        it('should hide toolbar', () => {
            toolbar.show(5)
            toolbar.hide()

            const toolbarElement = document.getElementById('testBulkActionToolbar')
            expect(toolbarElement.style.display).toBe('none')
        })

        it('should update selection count when shown', () => {
            toolbar.show(3)
            let countDisplay = document.querySelector('#selectedCountDisplay')
            expect(countDisplay.textContent).toBe('3')

            toolbar.show(10)
            countDisplay = document.querySelector('#selectedCountDisplay')
            expect(countDisplay.textContent).toBe('10')
        })
    })

    describe('Button Actions', () => {
        it('should call onDeleteSelected when delete button is clicked', () => {
            const onDeleteSelected = jest.fn()
            toolbar.onDeleteSelected = onDeleteSelected

            toolbar.show(5)
            const deleteBtn = document.querySelector('#deleteSelectedBtn')
            deleteBtn.click()

            expect(onDeleteSelected).toHaveBeenCalledWith(5)
        })

        it('should call onUpdateStatus when status option is clicked', () => {
            const onUpdateStatus = jest.fn()
            toolbar.onUpdateStatus = onUpdateStatus

            toolbar.show(5)
            const statusOption = document.querySelector('[data-status="active"]')
            statusOption.click()

            expect(onUpdateStatus).toHaveBeenCalledWith('active', 5)
        })

        it('should call onUpdateCategory when category option is clicked', () => {
            const onUpdateCategory = jest.fn()
            toolbar.onUpdateCategory = onUpdateCategory

            toolbar.show(5)
            const categoryOption = document.querySelector('[data-category="Category A"]')
            categoryOption.click()

            expect(onUpdateCategory).toHaveBeenCalledWith('Category A', 5)
        })

        it('should call onClearSelection when clear button is clicked', () => {
            const onClearSelection = jest.fn()
            toolbar.onClearSelection = onClearSelection

            toolbar.show(5)
            const clearBtn = document.querySelector('#clearSelectionBtn')
            clearBtn.click()

            expect(onClearSelection).toHaveBeenCalled()
        })

        it('should hide toolbar when clear selection is clicked', () => {
            toolbar.show(5)
            const clearBtn = document.querySelector('#clearSelectionBtn')
            clearBtn.click()

            const toolbarElement = document.getElementById('testBulkActionToolbar')
            expect(toolbarElement.style.display).toBe('none')
        })
    })

    describe('Processing State', () => {
        it('should disable buttons when processing', () => {
            toolbar.show(5)
            toolbar.setProcessing(true)

            const buttons = document.querySelectorAll('#testBulkActionToolbar button')
            buttons.forEach((btn) => {
                expect(btn.disabled).toBe(true)
            })
        })

        it('should enable buttons when processing completes', () => {
            toolbar.show(5)
            toolbar.setProcessing(true)
            toolbar.setProcessing(false)

            const buttons = document.querySelectorAll('#testBulkActionToolbar button')
            buttons.forEach((btn) => {
                expect(btn.disabled).toBe(false)
            })
        })
    })

    describe('Progress Tracking', () => {
        it('should show progress indicator', () => {
            toolbar.show(5)
            toolbar.showProgress(10)

            const progressIndicator = document.querySelector('#testBulkActionToolbar #progressIndicator')
            expect(progressIndicator.style.display).toBe('block')
        })

        it('should update progress bar', () => {
            toolbar.show(5)
            toolbar.showProgress(10)
            toolbar.updateProgress(5, 10)

            const progressBar = document.querySelector('#testBulkActionToolbar #progressBar')
            expect(progressBar.style.width).toBe('50%')
        })

        it('should update progress text', () => {
            toolbar.show(5)
            toolbar.showProgress(10)
            toolbar.updateProgress(3, 10)

            const progressText = document.querySelector('#testBulkActionToolbar #progressText')
            expect(progressText.textContent).toBe('3 / 10')
        })

        it('should hide progress indicator', () => {
            toolbar.show(5)
            toolbar.showProgress(10)
            toolbar.hideProgress()

            const progressIndicator = document.querySelector('#testBulkActionToolbar #progressIndicator')
            expect(progressIndicator.style.display).toBe('none')
        })

        it('should handle 0% progress', () => {
            toolbar.show(5)
            toolbar.showProgress(10)
            toolbar.updateProgress(0, 10)

            const progressBar = document.querySelector('#testBulkActionToolbar #progressBar')
            expect(progressBar.style.width).toBe('0%')
        })

        it('should handle 100% progress', () => {
            toolbar.show(5)
            toolbar.showProgress(10)
            toolbar.updateProgress(10, 10)

            const progressBar = document.querySelector('#testBulkActionToolbar #progressBar')
            expect(progressBar.style.width).toBe('100%')
        })
    })

    describe('Dynamic Updates', () => {
        it('should update categories list', () => {
            const newCategories = ['New Category 1', 'New Category 2']
            toolbar.updateCategories(newCategories)

            const categoryOptions = document.querySelectorAll('.category-option')
            expect(categoryOptions.length).toBe(2)
            expect(categoryOptions[0].dataset.category).toBe('New Category 1')
            expect(categoryOptions[1].dataset.category).toBe('New Category 2')
        })

        it('should update statuses list', () => {
            const newStatuses = ['pending', 'completed']
            toolbar.updateStatuses(newStatuses)

            const statusOptions = document.querySelectorAll('.status-option')
            expect(statusOptions.length).toBe(2)
            expect(statusOptions[0].dataset.status).toBe('pending')
            expect(statusOptions[1].dataset.status).toBe('completed')
        })

        it('should re-attach event listeners after updating categories', () => {
            const onUpdateCategory = jest.fn()
            toolbar.onUpdateCategory = onUpdateCategory

            const newCategories = ['New Category 1', 'New Category 2']
            toolbar.updateCategories(newCategories)

            toolbar.show(5)
            const categoryOption = document.querySelector('[data-category="New Category 1"]')
            categoryOption.click()

            expect(onUpdateCategory).toHaveBeenCalledWith('New Category 1', 5)
        })

        it('should re-attach event listeners after updating statuses', () => {
            const onUpdateStatus = jest.fn()
            toolbar.onUpdateStatus = onUpdateStatus

            const newStatuses = ['pending', 'completed']
            toolbar.updateStatuses(newStatuses)

            toolbar.show(5)
            const statusOption = document.querySelector('[data-status="pending"]')
            statusOption.click()

            expect(onUpdateStatus).toHaveBeenCalledWith('pending', 5)
        })
    })

    describe('Edge Cases', () => {
        it('should handle empty categories', () => {
            const emptyToolbar = new BulkActionToolbar({
                toolbarId: 'emptyToolbar',
                categories: [],
                statuses: ['active', 'inactive'],
            })

            const categoryOptions = document.querySelectorAll('#emptyToolbar .category-option')
            expect(categoryOptions.length).toBe(0)
        })

        it('should handle empty statuses', () => {
            const emptyToolbar = new BulkActionToolbar({
                toolbarId: 'emptyToolbar2',
                categories: ['Category A'],
                statuses: [],
            })

            const statusOptions = document.querySelectorAll('#emptyToolbar2 .status-option')
            expect(statusOptions.length).toBe(0)
        })

        it('should handle large selection counts', () => {
            toolbar.show(999999)

            const countDisplay = document.querySelector('#selectedCountDisplay')
            expect(countDisplay.textContent).toBe('999999')
        })
    })
})
