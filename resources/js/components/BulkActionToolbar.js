/**
 * Bulk Action Toolbar Component
 * Displays bulk action controls when records are selected
 * Handles bulk delete, status update, and category update operations
 */

class BulkActionToolbar {
    constructor(options = {}) {
        this.toolbarId = options.toolbarId || 'bulkActionToolbar'
        this.selectedCount = 0
        this.isVisible = false
        this.isProcessing = false
        this.categories = options.categories || []
        this.statuses = options.statuses || ['active', 'inactive', 'archived']
        this.apiBaseUrl = options.apiBaseUrl || '/api/main-system'

        this.onDeleteSelected = options.onDeleteSelected || (() => { })
        this.onUpdateStatus = options.onUpdateStatus || (() => { })
        this.onUpdateCategory = options.onUpdateCategory || (() => { })
        this.onClearSelection = options.onClearSelection || (() => { })

        this.initializeToolbar()
    }

    /**
     * Initialize toolbar DOM structure
     */
    initializeToolbar() {
        let toolbar = document.getElementById(this.toolbarId)
        if (!toolbar) {
            toolbar = document.createElement('div')
            toolbar.id = this.toolbarId
            toolbar.className = 'bulk-action-toolbar'
            toolbar.style.display = 'none'
            document.body.insertBefore(toolbar, document.body.firstChild)
        }

        toolbar.innerHTML = `
            <div class="bulk-action-container">
                <div class="bulk-action-info">
                    <span class="selection-count">
                        <strong id="selectedCountDisplay">0</strong> records selected
                    </span>
                </div>
                <div class="bulk-action-controls">
                    <button id="deleteSelectedBtn" class="btn btn-danger btn-sm" title="Delete selected records">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <div class="btn-group btn-sm" role="group">
                        <button id="updateStatusBtn" class="btn btn-primary btn-sm dropdown-toggle" 
                                data-bs-toggle="dropdown" title="Update status for selected records">
                            <i class="fas fa-edit"></i> Update Status
                        </button>
                        <ul id="statusDropdown" class="dropdown-menu">
                            ${this.statuses.map((status) => `
                                <li><a class="dropdown-item status-option" data-status="${status}">${status}</a></li>
                            `).join('')}
                        </ul>
                    </div>
                    <div class="btn-group btn-sm" role="group">
                        <button id="updateCategoryBtn" class="btn btn-primary btn-sm dropdown-toggle" 
                                data-bs-toggle="dropdown" title="Update category for selected records">
                            <i class="fas fa-folder"></i> Update Category
                        </button>
                        <ul id="categoryDropdown" class="dropdown-menu">
                            ${this.categories.map((category) => `
                                <li><a class="dropdown-item category-option" data-category="${category}">${category}</a></li>
                            `).join('')}
                        </ul>
                    </div>
                    <button id="clearSelectionBtn" class="btn btn-secondary btn-sm" title="Clear selection">
                        <i class="fas fa-times"></i> Clear Selection
                    </button>
                </div>
                <div id="progressIndicator" class="progress-indicator" style="display: none;">
                    <div class="progress">
                        <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                    <span id="progressText" class="progress-text">0 / 0</span>
                </div>
            </div>
        `
    }

    /**
     * Attach event listeners to toolbar buttons
     */
    attachEventListeners() {
        const toolbar = document.getElementById(this.toolbarId)
        if (!toolbar) return

        // Delete button
        const deleteBtn = toolbar.querySelector('#deleteSelectedBtn')
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => this.handleDeleteSelected())
        }

        // Status options
        const statusOptions = toolbar.querySelectorAll('.status-option')
        statusOptions.forEach((option) => {
            option.addEventListener('click', (e) => {
                e.preventDefault()
                const status = option.dataset.status
                this.handleUpdateStatus(status)
            })
        })

        // Category options
        const categoryOptions = toolbar.querySelectorAll('.category-option')
        categoryOptions.forEach((option) => {
            option.addEventListener('click', (e) => {
                e.preventDefault()
                const category = option.dataset.category
                this.handleUpdateCategory(category)
            })
        })

        // Clear selection button
        const clearBtn = toolbar.querySelector('#clearSelectionBtn')
        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.handleClearSelection())
        }
    }

    /**
     * Show toolbar with selection count
     */
    show(selectedCount) {
        this.selectedCount = selectedCount
        this.isVisible = true

        const toolbar = document.getElementById(this.toolbarId)
        if (toolbar) {
            toolbar.style.display = 'block'
            const countDisplay = toolbar.querySelector('#selectedCountDisplay')
            if (countDisplay) {
                countDisplay.textContent = selectedCount
            }
        }
    }

    /**
     * Hide toolbar
     */
    hide() {
        this.isVisible = false
        const toolbar = document.getElementById(this.toolbarId)
        if (toolbar) {
            toolbar.style.display = 'none'
        }
    }

    /**
     * Handle delete selected records
     */
    handleDeleteSelected() {
        this.onDeleteSelected(this.selectedCount)
    }

    /**
     * Handle update status
     */
    handleUpdateStatus(status) {
        this.onUpdateStatus(status, this.selectedCount)
    }

    /**
     * Handle update category
     */
    handleUpdateCategory(category) {
        this.onUpdateCategory(category, this.selectedCount)
    }

    /**
     * Handle clear selection
     */
    handleClearSelection() {
        this.onClearSelection()
        this.hide()
    }

    /**
     * Show progress indicator
     */
    showProgress(total) {
        const toolbar = document.getElementById(this.toolbarId)
        if (toolbar) {
            const progressIndicator = toolbar.querySelector('#progressIndicator')
            if (progressIndicator) {
                progressIndicator.style.display = 'block'
            }
        }
        this.updateProgress(0, total)
    }

    /**
     * Update progress indicator
     */
    updateProgress(processed, total) {
        const toolbar = document.getElementById(this.toolbarId)
        if (toolbar) {
            const progressBar = toolbar.querySelector('#progressBar')
            const progressText = toolbar.querySelector('#progressText')

            if (progressBar) {
                const percentage = total > 0 ? (processed / total) * 100 : 0
                progressBar.style.width = percentage + '%'
            }

            if (progressText) {
                progressText.textContent = `${processed} / ${total}`
            }
        }
    }

    /**
     * Hide progress indicator
     */
    hideProgress() {
        const toolbar = document.getElementById(this.toolbarId)
        if (toolbar) {
            const progressIndicator = toolbar.querySelector('#progressIndicator')
            if (progressIndicator) {
                progressIndicator.style.display = 'none'
            }
        }
    }

    /**
     * Set processing state
     */
    setProcessing(isProcessing) {
        this.isProcessing = isProcessing
        const toolbar = document.getElementById(this.toolbarId)
        if (toolbar) {
            const buttons = toolbar.querySelectorAll('button')
            buttons.forEach((btn) => {
                btn.disabled = isProcessing
            })
        }
    }

    /**
     * Update categories list
     */
    updateCategories(categories) {
        this.categories = categories
        const toolbar = document.getElementById(this.toolbarId)
        if (toolbar) {
            const categoryDropdown = toolbar.querySelector('#categoryDropdown')
            if (categoryDropdown) {
                categoryDropdown.innerHTML = categories
                    .map((category) => `
                        <li><a class="dropdown-item category-option" data-category="${category}">${category}</a></li>
                    `)
                    .join('')

                // Re-attach event listeners
                const categoryOptions = categoryDropdown.querySelectorAll('.category-option')
                categoryOptions.forEach((option) => {
                    option.addEventListener('click', (e) => {
                        e.preventDefault()
                        const category = option.dataset.category
                        this.handleUpdateCategory(category)
                    })
                })
            }
        }
    }

    /**
     * Update statuses list
     */
    updateStatuses(statuses) {
        this.statuses = statuses
        const toolbar = document.getElementById(this.toolbarId)
        if (toolbar) {
            const statusDropdown = toolbar.querySelector('#statusDropdown')
            if (statusDropdown) {
                statusDropdown.innerHTML = statuses
                    .map((status) => `
                        <li><a class="dropdown-item status-option" data-status="${status}">${status}</a></li>
                    `)
                    .join('')

                // Re-attach event listeners
                const statusOptions = statusDropdown.querySelectorAll('.status-option')
                statusOptions.forEach((option) => {
                    option.addEventListener('click', (e) => {
                        e.preventDefault()
                        const status = option.dataset.status
                        this.handleUpdateStatus(status)
                    })
                })
            }
        }
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BulkActionToolbar
}
