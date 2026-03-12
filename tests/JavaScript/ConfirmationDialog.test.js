/**
 * Unit tests for ConfirmationDialog component
 * Tests confirmation dialogs for delete and bulk operations
 */

const ConfirmationDialog = require('../../resources/js/components/ConfirmationDialog')

describe('ConfirmationDialog Component', () => {
    let dialog

    beforeEach(() => {
        document.body.innerHTML = ''

        // Mock Bootstrap Modal
        window.bootstrap = {
            Modal: jest.fn(function (element) {
                this.show = jest.fn()
                this.hide = jest.fn()
            }),
        }
        window.bootstrap.Modal.getInstance = jest.fn(() => ({
            show: jest.fn(),
            hide: jest.fn(),
        }))

        dialog = new ConfirmationDialog({
            dialogId: 'testConfirmationDialog',
        })
    })

    afterEach(() => {
        jest.clearAllMocks()
        document.body.innerHTML = ''
    })

    describe('Initialization', () => {
        it('should create dialog DOM structure', () => {
            const dialogElement = document.getElementById('testConfirmationDialog')
            expect(dialogElement).toBeTruthy()
            expect(dialogElement.classList.contains('modal')).toBe(true)
        })

        it('should have modal header with title', () => {
            const dialogElement = document.getElementById('testConfirmationDialog')
            const title = dialogElement.querySelector('.modal-title')
            expect(title).toBeTruthy()
        })

        it('should have modal body with message area', () => {
            const dialogElement = document.getElementById('testConfirmationDialog')
            const messageArea = dialogElement.querySelector('#testConfirmationDialog-message')
            expect(messageArea).toBeTruthy()
        })

        it('should have confirm and cancel buttons', () => {
            const dialogElement = document.getElementById('testConfirmationDialog')
            const confirmBtn = dialogElement.querySelector('#testConfirmationDialog-confirm-btn')
            const cancelBtn = dialogElement.querySelector('[data-bs-dismiss="modal"]')

            expect(confirmBtn).toBeTruthy()
            expect(cancelBtn).toBeTruthy()
        })

        it('should have record preview section', () => {
            const dialogElement = document.getElementById('testConfirmationDialog')
            const previewSection = dialogElement.querySelector('#testConfirmationDialog-preview')
            expect(previewSection).toBeTruthy()
        })
    })

    describe('Dialog Opening', () => {
        it('should open dialog with title and message', () => {
            dialog.open({
                title: 'Delete Record',
                message: 'Are you sure you want to delete this record?',
                actionType: 'delete',
            })

            const dialogElement = document.getElementById('testConfirmationDialog')
            const title = dialogElement.querySelector('.modal-title')
            const message = dialogElement.querySelector('#testConfirmationDialog-message')

            expect(title.textContent).toBe('Delete Record')
            expect(message.textContent).toContain('Are you sure you want to delete this record?')
        })

        it('should set confirm button text based on action type', () => {
            dialog.open({
                title: 'Delete',
                message: 'Delete this record?',
                actionType: 'delete',
            })

            const confirmBtn = document.querySelector('#testConfirmationDialog-confirm-btn')
            expect(confirmBtn.textContent).toBe('Delete')
            expect(confirmBtn.className).toContain('btn-danger')
        })

        it('should set confirm button text for status update', () => {
            dialog.open({
                title: 'Update Status',
                message: 'Update status?',
                actionType: 'status-update',
            })

            const confirmBtn = document.querySelector('#testConfirmationDialog-confirm-btn')
            expect(confirmBtn.textContent).toBe('Update Status')
            expect(confirmBtn.className).toContain('btn-primary')
        })

        it('should set confirm button text for category update', () => {
            dialog.open({
                title: 'Update Category',
                message: 'Update category?',
                actionType: 'category-update',
            })

            const confirmBtn = document.querySelector('#testConfirmationDialog-confirm-btn')
            expect(confirmBtn.textContent).toBe('Update Category')
            expect(confirmBtn.className).toContain('btn-primary')
        })
    })

    describe('Record Preview', () => {
        it('should display record preview when provided', () => {
            const records = [
                { first_name: 'John', last_name: 'Doe', regs_no: 'REG001' },
                { first_name: 'Jane', last_name: 'Smith', regs_no: 'REG002' },
            ]

            dialog.open({
                title: 'Delete Records',
                message: 'Delete these records?',
                recordPreview: records,
                totalCount: 2,
                actionType: 'delete',
            })

            const previewSection = document.querySelector('#testConfirmationDialog-preview')
            expect(previewSection.style.display).toBe('block')

            const previewItems = document.querySelectorAll('#testConfirmationDialog-preview-list li')
            expect(previewItems.length).toBe(2)
            expect(previewItems[0].textContent).toContain('John Doe')
            expect(previewItems[1].textContent).toContain('Jane Smith')
        })

        it('should hide preview when no records provided', () => {
            dialog.open({
                title: 'Delete',
                message: 'Delete?',
                recordPreview: [],
                actionType: 'delete',
            })

            const previewSection = document.querySelector('#testConfirmationDialog-preview')
            expect(previewSection.style.display).toBe('none')
        })

        it('should show "and X more" when more than 10 records', () => {
            const records = Array.from({ length: 15 }, (_, i) => ({
                first_name: `User${i}`,
                last_name: 'Test',
                regs_no: `REG${i}`,
            }))

            dialog.open({
                title: 'Delete Records',
                message: 'Delete these records?',
                recordPreview: records,
                totalCount: 15,
                actionType: 'delete',
            })

            const previewItems = document.querySelectorAll('#testConfirmationDialog-preview-list li')
            expect(previewItems.length).toBe(10)

            const moreText = document.querySelector('#testConfirmationDialog-preview-more')
            expect(moreText.style.display).toBe('block')
            expect(moreText.textContent).toContain('and 5 more records')
        })

        it('should display registration number in preview', () => {
            const records = [{ first_name: 'John', last_name: 'Doe', regs_no: 'REG001' }]

            dialog.open({
                title: 'Delete',
                message: 'Delete?',
                recordPreview: records,
                actionType: 'delete',
            })

            const previewItem = document.querySelector('#testConfirmationDialog-preview-list li')
            expect(previewItem.textContent).toContain('REG001')
        })

        it('should handle records without registration number', () => {
            const records = [{ first_name: 'John', last_name: 'Doe' }]

            dialog.open({
                title: 'Delete',
                message: 'Delete?',
                recordPreview: records,
                actionType: 'delete',
            })

            const previewItem = document.querySelector('#testConfirmationDialog-preview-list li')
            expect(previewItem.textContent).toContain('John Doe')
        })
    })

    describe('Confirmation and Cancellation', () => {
        it('should call onConfirmed when confirm button is clicked', () => {
            const onConfirmed = jest.fn()
            dialog.onConfirmed = onConfirmed

            dialog.open({
                title: 'Delete',
                message: 'Delete?',
                actionType: 'delete',
            })

            const confirmBtn = document.querySelector('#testConfirmationDialog-confirm-btn')
            confirmBtn.click()

            expect(onConfirmed).toHaveBeenCalled()
        })

        it('should call onCancelled when cancel button is clicked', () => {
            const onCancelled = jest.fn()
            dialog.onCancelled = onCancelled

            dialog.open({
                title: 'Delete',
                message: 'Delete?',
                actionType: 'delete',
            })

            const cancelBtn = document.querySelector('[data-bs-dismiss="modal"]')
            cancelBtn.click()

            expect(onCancelled).toHaveBeenCalled()
        })

        it('should call onCancelled when close button is clicked', () => {
            const onCancelled = jest.fn()
            dialog.onCancelled = onCancelled

            dialog.open({
                title: 'Delete',
                message: 'Delete?',
                actionType: 'delete',
            })

            const closeBtn = document.querySelector('.btn-close')
            closeBtn.click()

            expect(onCancelled).toHaveBeenCalled()
        })

        it('should close dialog after confirmation', () => {
            dialog.open({
                title: 'Delete',
                message: 'Delete?',
                actionType: 'delete',
            })

            const confirmBtn = document.querySelector('#testConfirmationDialog-confirm-btn')
            confirmBtn.click()

            // Dialog should be hidden (Bootstrap Modal.hide() called)
            expect(window.bootstrap.Modal).toHaveBeenCalled()
        })
    })

    describe('Callback Management', () => {
        it('should set onConfirmed callback', () => {
            const callback = jest.fn()
            dialog.setOnConfirmed(callback)

            dialog.open({
                title: 'Delete',
                message: 'Delete?',
                actionType: 'delete',
            })

            const confirmBtn = document.querySelector('#testConfirmationDialog-confirm-btn')
            confirmBtn.click()

            expect(callback).toHaveBeenCalled()
        })

        it('should set onCancelled callback', () => {
            const callback = jest.fn()
            dialog.setOnCancelled(callback)

            dialog.open({
                title: 'Delete',
                message: 'Delete?',
                actionType: 'delete',
            })

            const cancelBtn = document.querySelector('[data-bs-dismiss="modal"]')
            cancelBtn.click()

            expect(callback).toHaveBeenCalled()
        })
    })

    describe('Dialog Closing', () => {
        it('should close dialog', () => {
            dialog.open({
                title: 'Delete',
                message: 'Delete?',
                actionType: 'delete',
            })

            dialog.close()

            // Bootstrap Modal.hide() should be called
            expect(window.bootstrap.Modal).toHaveBeenCalled()
        })
    })

    describe('Edge Cases', () => {
        it('should handle very long record names', () => {
            const longName = 'A'.repeat(100)
            const records = [{ first_name: longName, last_name: 'Test' }]

            dialog.open({
                title: 'Delete',
                message: 'Delete?',
                recordPreview: records,
                actionType: 'delete',
            })

            const previewItem = document.querySelector('#testConfirmationDialog-preview-list li')
            expect(previewItem.textContent).toContain(longName)
        })

        it('should handle exactly 10 records without "and more"', () => {
            const records = Array.from({ length: 10 }, (_, i) => ({
                first_name: `User${i}`,
                last_name: 'Test',
            }))

            dialog.open({
                title: 'Delete',
                message: 'Delete?',
                recordPreview: records,
                actionType: 'delete',
            })

            const previewItems = document.querySelectorAll('#testConfirmationDialog-preview-list li')
            expect(previewItems.length).toBe(10)

            const moreText = document.querySelector('#testConfirmationDialog-preview-more')
            expect(moreText.style.display).toBe('none')
        })
    })
})
