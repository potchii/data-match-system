/**
 * Confirmation Dialog Component
 * Displays confirmation for destructive operations with record preview
 */

class ConfirmationDialog {
    constructor(options = {}) {
        this.dialogId = options.dialogId || 'confirmationDialog'
        this.title = ''
        this.message = ''
        this.recordPreview = []
        this.totalCount = 0
        this.actionType = 'delete'

        this.onConfirmed = options.onConfirmed || (() => { })
        this.onCancelled = options.onCancelled || (() => { })

        this.initializeDialog()
    }

    /**
     * Initialize dialog DOM structure
     */
    initializeDialog() {
        let dialog = document.getElementById(this.dialogId)
        if (!dialog) {
            dialog = document.createElement('div')
            dialog.id = this.dialogId
            dialog.className = 'modal fade'
            dialog.setAttribute('tabindex', '-1')
            dialog.setAttribute('role', 'dialog')
            dialog.setAttribute('aria-labelledby', `${this.dialogId}-label`)
            dialog.setAttribute('aria-hidden', 'true')
            document.body.appendChild(dialog)
        }

        dialog.innerHTML = `
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="${this.dialogId}-label">Confirm Action</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="${this.dialogId}-message" class="confirmation-message"></div>
                        <div id="${this.dialogId}-preview" class="record-preview" style="display: none;">
                            <h6>Affected Records:</h6>
                            <ul id="${this.dialogId}-preview-list" class="list-group"></ul>
                            <div id="${this.dialogId}-preview-more" class="text-muted small mt-2" style="display: none;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="${this.dialogId}-confirm-btn" class="btn btn-danger">Confirm</button>
                    </div>
                </div>
            </div>
        `
    }

    /**
     * Attach event listeners to dialog buttons
     */
    attachEventListeners() {
        const dialog = document.getElementById(this.dialogId)
        if (!dialog) return

        const confirmBtn = dialog.querySelector(`#${this.dialogId}-confirm-btn`)
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => this.confirm())
        }

        // Handle cancel via close button or Cancel button
        const closeBtn = dialog.querySelector('.btn-close')
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.cancel())
        }

        const cancelBtn = dialog.querySelector('[data-bs-dismiss="modal"]')
        if (cancelBtn && cancelBtn !== closeBtn) {
            cancelBtn.addEventListener('click', () => this.cancel())
        }
    }

    /**
     * Open confirmation dialog
     */
    open(options = {}) {
        this.title = options.title || 'Confirm Action'
        this.message = options.message || 'Are you sure?'
        this.recordPreview = options.recordPreview || []
        this.totalCount = options.totalCount || 0
        this.actionType = options.actionType || 'delete'

        const dialog = document.getElementById(this.dialogId)
        if (!dialog) return

        // Update title
        const titleElement = dialog.querySelector(`#${this.dialogId}-label`)
        if (titleElement) {
            titleElement.textContent = this.title
        }

        // Update message
        const messageElement = dialog.querySelector(`#${this.dialogId}-message`)
        if (messageElement) {
            messageElement.innerHTML = `<p>${this.message}</p>`
        }

        // Update record preview
        this.renderRecordPreview()

        // Update confirm button text
        const confirmBtn = dialog.querySelector(`#${this.dialogId}-confirm-btn`)
        if (confirmBtn) {
            if (this.actionType === 'delete') {
                confirmBtn.textContent = 'Delete'
                confirmBtn.className = 'btn btn-danger'
            } else if (this.actionType === 'status-update') {
                confirmBtn.textContent = 'Update Status'
                confirmBtn.className = 'btn btn-primary'
            } else if (this.actionType === 'category-update') {
                confirmBtn.textContent = 'Update Category'
                confirmBtn.className = 'btn btn-primary'
            }
        }

        // Show dialog using Bootstrap Modal
        if (window.bootstrap && window.bootstrap.Modal) {
            const bsDialog = new window.bootstrap.Modal(dialog)
            bsDialog.show()
        } else {
            dialog.style.display = 'block'
        }
    }

    /**
     * Render record preview
     */
    renderRecordPreview() {
        const dialog = document.getElementById(this.dialogId)
        if (!dialog) return

        const previewContainer = dialog.querySelector(`#${this.dialogId}-preview`)
        const previewList = dialog.querySelector(`#${this.dialogId}-preview-list`)
        const previewMore = dialog.querySelector(`#${this.dialogId}-preview-more`)

        if (!previewContainer || !previewList) return

        if (this.recordPreview.length === 0) {
            previewContainer.style.display = 'none'
            return
        }

        previewContainer.style.display = 'block'
        previewList.innerHTML = ''

        // Show up to 10 records
        const displayRecords = this.recordPreview.slice(0, 10)
        displayRecords.forEach((record) => {
            const li = document.createElement('li')
            li.className = 'list-group-item'
            li.innerHTML = `
                <strong>${record.first_name} ${record.last_name}</strong>
                ${record.regs_no ? `<br><small class="text-muted">Reg: ${record.regs_no}</small>` : ''}
            `
            previewList.appendChild(li)
        })

        // Show "and X more" if there are more records
        if (this.recordPreview.length > 10) {
            const remaining = this.recordPreview.length - 10
            if (previewMore) {
                previewMore.style.display = 'block'
                previewMore.textContent = `and ${remaining} more record${remaining > 1 ? 's' : ''}`
            }
        } else if (previewMore) {
            previewMore.style.display = 'none'
        }
    }

    /**
     * Confirm the operation
     */
    confirm() {
        this.close()
        this.onConfirmed()
    }

    /**
     * Cancel the operation
     */
    cancel() {
        this.close()
        this.onCancelled()
    }

    /**
     * Close dialog
     */
    close() {
        const dialog = document.getElementById(this.dialogId)
        if (!dialog) return

        if (window.bootstrap && window.bootstrap.Modal) {
            try {
                const bsDialog = window.bootstrap.Modal.getInstance(dialog)
                if (bsDialog) {
                    bsDialog.hide()
                }
            } catch (e) {
                // Fallback if getInstance is not available
                dialog.style.display = 'none'
            }
        } else {
            dialog.style.display = 'none'
        }
    }

    /**
     * Set callback for confirmation
     */
    setOnConfirmed(callback) {
        this.onConfirmed = callback
    }

    /**
     * Set callback for cancellation
     */
    setOnCancelled(callback) {
        this.onCancelled = callback
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ConfirmationDialog
}
