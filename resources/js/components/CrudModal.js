/**
 * CRUD Modal Component
 * Handles create and edit operations for Main System records
 * Integrates with validation service and API endpoints
 */

class CrudModal {
  constructor(options = {}) {
    this.modalId = options.modalId || 'crudModal'
    this.mode = 'create' // 'create' or 'edit'
    this.recordId = null
    this.formData = this.getEmptyFormData()
    this.initialFormData = null
    this.errors = {}
    this.isLoading = false
    this.hasChanges = false
    this.templateFields = []
    this.apiBaseUrl = options.apiBaseUrl || '/api/main-system'
    this.onRecordCreated = options.onRecordCreated || (() => { })
    this.onRecordUpdated = options.onRecordUpdated || (() => { })
    this.onModalClosed = options.onModalClosed || (() => { })
    this.onError = options.onError || (() => { })

    this.initializeModal()
  }

  /**
   * Initialize modal DOM structure
   */
  initializeModal() {
    const modalHTML = `
      <div class="modal fade" id="${this.modalId}" tabindex="-1" role="dialog" aria-labelledby="${this.modalId}Label" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="${this.modalId}-label">Create Record</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div id="${this.modalId}-error-banner" class="alert alert-danger" style="display: none;">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <span id="${this.modalId}-error-message"></span>
              </div>
              <form id="${this.modalId}-form">
                <!-- Core Fields Section -->
                <div class="form-section">
                  <h6 class="mb-3"><i class="fas fa-database"></i> Core Information</h6>
                  
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="uid">UID <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="uid" name="uid" placeholder="Unique Identifier" required>
                      <small class="form-text text-danger" style="display: none;"></small>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="regs_no">Registration Number</label>
                      <input type="text" class="form-control" id="regs_no" name="regs_no" placeholder="Registration Number">
                      <small class="form-text text-danger" style="display: none;"></small>
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-4">
                      <label for="first_name">First Name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" required>
                      <small class="form-text text-danger" style="display: none;"></small>
                    </div>
                    <div class="form-group col-md-4">
                      <label for="middle_name">Middle Name</label>
                      <input type="text" class="form-control" id="middle_name" name="middle_name" placeholder="Middle Name">
                      <small class="form-text text-danger" style="display: none;"></small>
                    </div>
                    <div class="form-group col-md-4">
                      <label for="last_name">Last Name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" required>
                      <small class="form-text text-danger" style="display: none;"></small>
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="suffix">Suffix</label>
                      <input type="text" class="form-control" id="suffix" name="suffix" placeholder="Suffix (Jr., Sr., etc.)">
                      <small class="form-text text-danger" style="display: none;"></small>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="registration_date">Registration Date</label>
                      <input type="date" class="form-control" id="registration_date" name="registration_date">
                      <small class="form-text text-danger" style="display: none;"></small>
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="birthday">Birthday</label>
                      <input type="date" class="form-control" id="birthday" name="birthday">
                      <small class="form-text text-danger" style="display: none;"></small>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="gender">Gender</label>
                      <select class="form-control" id="gender" name="gender">
                        <option value="">-- Select Gender --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                      </select>
                      <small class="form-text text-danger" style="display: none;"></small>
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="civil_status">Civil Status</label>
                      <input type="text" class="form-control" id="civil_status" name="civil_status" placeholder="Civil Status">
                      <small class="form-text text-danger" style="display: none;"></small>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="barangay">Barangay</label>
                      <input type="text" class="form-control" id="barangay" name="barangay" placeholder="Barangay">
                      <small class="form-text text-danger" style="display: none;"></small>
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="address">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3" placeholder="Street Address"></textarea>
                    <small class="form-text text-danger" style="display: none;"></small>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="status">Status</label>
                      <select class="form-control" id="status" name="status">
                        <option value="">-- Select Status --</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="archived">Archived</option>
                      </select>
                      <small class="form-text text-danger" style="display: none;"></small>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="category">Category</label>
                      <input type="text" class="form-control" id="category" name="category" placeholder="Category">
                      <small class="form-text text-danger" style="display: none;"></small>
                    </div>
                  </div>
                </div>

                <!-- Template Fields Section -->
                <div id="${this.modalId}-template-fields" class="form-section" style="display: none;">
                  <hr>
                  <h6 class="mb-3"><i class="fas fa-cog"></i> Custom Fields</h6>
                  <div id="${this.modalId}-template-fields-container"></div>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-primary" id="${this.modalId}-save-btn">Save Record</button>
            </div>
          </div>
        </div>
      </div>
    `

    // Insert modal into DOM if not already present
    if (!document.getElementById(this.modalId)) {
      document.body.insertAdjacentHTML('beforeend', modalHTML)
      console.log(`CrudModal: Modal with ID "${this.modalId}" inserted into DOM`)
    } else {
      console.log(`CrudModal: Modal with ID "${this.modalId}" already exists in DOM`)
    }
  }

  /**
   * Attach event listeners to modal elements
   */
  attachEventListeners() {
    const form = document.getElementById(`${this.modalId}-form`)
    const saveBtn = document.getElementById(`${this.modalId}-save-btn`)
    const modal = document.getElementById(this.modalId)

    console.log(`CrudModal.attachEventListeners: Looking for form with ID "${this.modalId}-form"`)
    console.log(`CrudModal.attachEventListeners: Form found:`, !!form)
    console.log(`CrudModal.attachEventListeners: Save button found:`, !!saveBtn)
    console.log(`CrudModal.attachEventListeners: Modal found:`, !!modal)

    if (!form || !saveBtn || !modal) {
      console.error('CrudModal: Required elements not found')
      return
    }

    // Track form changes
    form.addEventListener('change', () => this.detectChanges())
    form.addEventListener('input', () => this.detectChanges())

    // Save button
    saveBtn.addEventListener('click', () => this.saveRecord())

    // Cancel button with unsaved changes confirmation
    modal.addEventListener('hide.bs.modal', (e) => {
      if (this.hasChanges) {
        e.preventDefault()
        this.showCancelConfirmation()
      }
    })
  }

  /**
   * Get empty form data object
   */
  getEmptyFormData() {
    return {
      uid: '',
      regs_no: '',
      registration_date: '',
      first_name: '',
      middle_name: '',
      last_name: '',
      suffix: '',
      birthday: '',
      gender: '',
      civil_status: '',
      address: '',
      barangay: '',
      status: '',
      category: '',
      templateFields: {},
    }
  }

  /**
   * Open modal in create mode
   */
  openCreate() {
    this.mode = 'create'
    this.recordId = null
    this.resetForm()
    this.updateModalTitle('Create Record')
    this.makeUidEditable()
    this.clearTemplateFields()
    this.showModal()
  }

  /**
   * Open modal in edit mode with record data
   */
  async openEdit(recordId) {
    this.mode = 'edit'
    this.recordId = recordId
    this.resetForm()
    this.updateModalTitle('Edit Record')
    this.makeUidReadOnly()

    try {
      this.setLoading(true)
      const response = await fetch(`${this.apiBaseUrl}/${recordId}`)

      if (!response.ok) {
        throw new Error('Failed to load record')
      }

      const result = await response.json()
      this.populateForm(result.data)
      this.renderTemplateFields(result.data.templateFieldValues || [])
      this.showModal()
    } catch (error) {
      this.showError('Failed to load record. Please try again.')
      this.onError(error)
    } finally {
      this.setLoading(false)
    }
  }

  /**
   * Populate form with record data
   */
  populateForm(record) {
    const form = document.getElementById(`${this.modalId}-form`)

    Object.keys(this.formData).forEach((key) => {
      const field = form.querySelector(`[name="${key}"]`)
      if (field && record[key] !== undefined) {
        field.value = record[key] || ''
      }
    })

    this.initialFormData = { ...this.formData }
    this.hasChanges = false
    this.clearErrors()
  }

  /**
   * Render template fields in the modal
   */
  renderTemplateFields(templateFieldValues) {
    const container = document.getElementById(`${this.modalId}-template-fields-container`)
    const section = document.getElementById(`${this.modalId}-template-fields`)

    if (!templateFieldValues || templateFieldValues.length === 0) {
      section.style.display = 'none'
      return
    }

    section.style.display = 'block'
    container.innerHTML = ''

    templateFieldValues.forEach((field) => {
      const fieldHTML = `
        <div class="form-group">
          <label for="template_${field.id}">${field.field_name}</label>
          <input type="text" class="form-control" id="template_${field.id}" 
                 name="templateFields[${field.id}]" 
                 value="${field.field_value || ''}"
                 placeholder="${field.field_name}">
          <small class="form-text text-danger" style="display: none;"></small>
        </div>
      `
      container.insertAdjacentHTML('beforeend', fieldHTML)
    })

    this.templateFields = templateFieldValues
  }

  /**
   * Clear template fields section
   */
  clearTemplateFields() {
    const section = document.getElementById(`${this.modalId}-template-fields`)
    const container = document.getElementById(`${this.modalId}-template-fields-container`)
    section.style.display = 'none'
    container.innerHTML = ''
    this.templateFields = []
  }

  /**
   * Detect if form data has changed
   */
  detectChanges() {
    const form = document.getElementById(`${this.modalId}-form`)
    const currentData = new FormData(form)
    const formObject = Object.fromEntries(currentData)

    this.hasChanges = JSON.stringify(formObject) !== JSON.stringify(this.initialFormData)
  }

  /**
   * Show cancel confirmation dialog
   */
  showCancelConfirmation() {
    if (confirm('Discard changes?')) {
      this.closeModal()
    }
  }

  /**
   * Collect form data
   */
  collectFormData() {
    const form = document.getElementById(`${this.modalId}-form`)
    const formData = new FormData(form)
    const data = Object.fromEntries(formData)

    // Handle template fields
    const templateFields = {}
    Object.keys(data).forEach((key) => {
      if (key.startsWith('templateFields[')) {
        const fieldId = key.match(/\[(\d+)\]/)[1]
        templateFields[fieldId] = data[key]
        delete data[key]
      }
    })

    if (Object.keys(templateFields).length > 0) {
      data.templateFields = templateFields
    }

    return data
  }

  /**
   * Save record (create or update)
   */
  async saveRecord() {
    this.clearErrors()
    const data = this.collectFormData()

    try {
      this.setLoading(true)
      const method = this.mode === 'create' ? 'POST' : 'PUT'
      const url = this.mode === 'create' ? this.apiBaseUrl : `${this.apiBaseUrl}/${this.recordId}`

      const response = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify(data),
      })

      const result = await response.json()

      if (!response.ok) {
        if (response.status === 422 && result.errors) {
          this.displayValidationErrors(result.errors)
        } else {
          this.showError(result.message || 'An error occurred while saving the record.')
        }
        return
      }

      this.hasChanges = false
      this.closeModal()

      if (this.mode === 'create') {
        this.onRecordCreated(result.data)
      } else {
        this.onRecordUpdated(result.data)
      }
    } catch (error) {
      this.showError('Failed to save record. Please try again.')
      this.onError(error)
    } finally {
      this.setLoading(false)
    }
  }

  /**
   * Display validation errors
   */
  displayValidationErrors(errors) {
    const form = document.getElementById(`${this.modalId}-form`)

    Object.keys(errors).forEach((fieldName) => {
      const field = form.querySelector(`[name="${fieldName}"]`)
      if (field) {
        const errorContainer = field.parentElement.querySelector('small.text-danger')
        if (errorContainer) {
          errorContainer.textContent = errors[fieldName][0]
          errorContainer.style.display = 'block'
          field.classList.add('is-invalid')
        }
      }
    })

    this.errors = errors
  }

  /**
   * Clear all errors
   */
  clearErrors() {
    const form = document.getElementById(`${this.modalId}-form`)
    const errorBanner = document.getElementById(`${this.modalId}-error-banner`)

    form.querySelectorAll('.is-invalid').forEach((field) => {
      field.classList.remove('is-invalid')
    })

    form.querySelectorAll('small.text-danger').forEach((error) => {
      error.style.display = 'none'
      error.textContent = ''
    })

    errorBanner.style.display = 'none'
    this.errors = {}
  }

  /**
   * Show error message in banner
   */
  showError(message) {
    const errorBanner = document.getElementById(`${this.modalId}-error-banner`)
    const errorMessage = document.getElementById(`${this.modalId}-error-message`)

    errorMessage.textContent = message
    errorBanner.style.display = 'block'
  }

  /**
   * Reset form to initial state
   */
  resetForm() {
    const form = document.getElementById(`${this.modalId}-form`)
    if (!form) {
      console.error(`CrudModal: Form element not found with ID: ${this.modalId}-form`)
      return
    }
    form.reset()
    this.formData = this.getEmptyFormData()
    this.initialFormData = { ...this.formData }
    this.hasChanges = false
    this.clearErrors()
  }

  /**
   * Update modal title
   */
  updateModalTitle(title) {
    const titleElement = document.getElementById(`${this.modalId}-label`)
    if (titleElement) {
      titleElement.textContent = title
    }
  }

  /**
   * Make UID field editable (for create mode)
   */
  makeUidEditable() {
    const uidField = document.getElementById('uid')
    if (uidField) {
      uidField.removeAttribute('readonly')
      uidField.classList.remove('form-control-plaintext')
    }
  }

  /**
   * Make UID field read-only (for edit mode)
   */
  makeUidReadOnly() {
    const uidField = document.getElementById('uid')
    if (uidField) {
      uidField.setAttribute('readonly', 'readonly')
      uidField.classList.add('form-control-plaintext')
    }
  }

  /**
   * Set loading state
   */
  setLoading(isLoading) {
    this.isLoading = isLoading
    const saveBtn = document.getElementById(`${this.modalId}-save-btn`)
    const form = document.getElementById(`${this.modalId}-form`)

    if (isLoading) {
      saveBtn.disabled = true
      saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span>Saving...'
      form.style.opacity = '0.6'
      form.style.pointerEvents = 'none'
    } else {
      saveBtn.disabled = false
      saveBtn.innerHTML = 'Save Record'
      form.style.opacity = '1'
      form.style.pointerEvents = 'auto'
    }
  }

  /**
   * Show modal
   */
  showModal() {
    const modal = document.getElementById(this.modalId)
    const bootstrapModal = new (window.bootstrap?.Modal || window.$.fn.modal)(modal)
    bootstrapModal.show()

    // Focus on first form field
    setTimeout(() => {
      const firstField = document.getElementById('uid')
      if (firstField) {
        firstField.focus()
      }
    }, 100)
  }

  /**
   * Close modal
   */
  closeModal() {
    const modal = document.getElementById(this.modalId)
    const bootstrapModal = window.bootstrap?.Modal?.getInstance(modal) ||
      (window.$ && window.$.fn.modal ? window.$(modal).data('bs.modal') : null)

    if (bootstrapModal) {
      bootstrapModal.hide()
    } else {
      modal.style.display = 'none'
    }

    this.onModalClosed()
  }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = CrudModal
}
