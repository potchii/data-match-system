/**
 * Unit tests for CrudModal component
 * Tests modal open/close behavior, form validation display, and API integration
 */

const CrudModal = require('../../resources/js/components/CrudModal')

describe('CrudModal Component', () => {
    let modal
    let mockFetch

    beforeEach(() => {
        // Setup DOM
        document.body.innerHTML = ''

        // Mock fetch
        mockFetch = jest.fn()
        global.fetch = mockFetch

        // Mock Bootstrap Modal
        window.bootstrap = {
            Modal: jest.fn(function (element) {
                this.show = jest.fn()
                this.hide = jest.fn()
            }),
        }

        // Create modal instance
        modal = new CrudModal({
            modalId: 'testCrudModal',
            apiBaseUrl: '/api/main-system',
        })
    })

    afterEach(() => {
        jest.clearAllMocks()
    })

    describe('Modal Initialization', () => {
        it('should create modal DOM structure on initialization', () => {
            const modalElement = document.getElementById('testCrudModal')
            expect(modalElement).toBeTruthy()
            expect(modalElement.classList.contains('modal')).toBe(true)
        })

        it('should have all required form fields', () => {
            const form = document.getElementById('testCrudModal-form')
            const requiredFields = ['uid', 'first_name', 'last_name', 'regs_no', 'birthday', 'gender', 'status', 'category']

            requiredFields.forEach((fieldName) => {
                const field = form.querySelector(`[name="${fieldName}"]`)
                expect(field).toBeTruthy()
            })
        })

        it('should have error banner and save button', () => {
            const errorBanner = document.getElementById('testCrudModal-error-banner')
            const saveBtn = document.getElementById('testCrudModal-save-btn')

            expect(errorBanner).toBeTruthy()
            expect(saveBtn).toBeTruthy()
        })
    })

    describe('Create Mode', () => {
        it('should open modal in create mode with empty form', () => {
            modal.openCreate()

            expect(modal.mode).toBe('create')
            expect(modal.recordId).toBeNull()
            expect(document.getElementById('uid').value).toBe('')
            expect(document.getElementById('first_name').value).toBe('')
        })

        it('should make UID field editable in create mode', () => {
            modal.openCreate()

            const uidField = document.getElementById('uid')
            expect(uidField.hasAttribute('readonly')).toBe(false)
        })

        it('should update modal title to "Create Record"', () => {
            modal.openCreate()

            const title = document.getElementById('testCrudModal-label')
            expect(title?.textContent).toBe('Create Record')
        })

        it('should clear template fields section in create mode', () => {
            modal.openCreate()

            const templateSection = document.getElementById('testCrudModal-template-fields')
            expect(templateSection.style.display).toBe('none')
        })
    })

    describe('Edit Mode', () => {
        it('should load record data when opening in edit mode', async () => {
            const mockRecord = {
                id: 1,
                uid: 'TEST001',
                first_name: 'John',
                last_name: 'Doe',
                birthday: '1990-01-01',
                gender: 'Male',
                status: 'active',
                templateFieldValues: [],
            }

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true, data: mockRecord }),
            })

            await modal.openEdit(1)

            expect(modal.mode).toBe('edit')
            expect(modal.recordId).toBe(1)
            expect(document.getElementById('uid').value).toBe('TEST001')
            expect(document.getElementById('first_name').value).toBe('John')
            expect(document.getElementById('last_name').value).toBe('Doe')
        })

        it('should make UID field read-only in edit mode', async () => {
            const mockRecord = {
                id: 1,
                uid: 'TEST001',
                first_name: 'John',
                last_name: 'Doe',
                templateFieldValues: [],
            }

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true, data: mockRecord }),
            })

            await modal.openEdit(1)

            const uidField = document.getElementById('uid')
            expect(uidField.hasAttribute('readonly')).toBe(true)
        })

        it('should update modal title to "Edit Record"', async () => {
            const mockRecord = {
                id: 1,
                uid: 'TEST001',
                first_name: 'John',
                last_name: 'Doe',
                templateFieldValues: [],
            }

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true, data: mockRecord }),
            })

            await modal.openEdit(1)

            const title = document.getElementById('testCrudModal-label')
            expect(title?.textContent).toBe('Edit Record')
        })

        it('should handle API error when loading record', async () => {
            const onError = jest.fn()
            modal.onError = onError

            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 404,
            })

            await modal.openEdit(999)

            expect(onError).toHaveBeenCalled()
        })
    })

    describe('Form Validation Display', () => {
        it('should display validation errors for invalid fields', () => {
            const errors = {
                uid: ['The uid field is required.'],
                first_name: ['The first name field is required.'],
            }

            modal.displayValidationErrors(errors)

            const uidError = document.querySelector('[name="uid"]').parentElement.querySelector('small.text-danger')
            const firstNameError = document.querySelector('[name="first_name"]').parentElement.querySelector('small.text-danger')

            expect(uidError.textContent).toBe('The uid field is required.')
            expect(uidError.style.display).toBe('block')
            expect(firstNameError.textContent).toBe('The first name field is required.')
            expect(firstNameError.style.display).toBe('block')
        })

        it('should add is-invalid class to fields with errors', () => {
            const errors = {
                uid: ['The uid field is required.'],
            }

            modal.displayValidationErrors(errors)

            const uidField = document.querySelector('[name="uid"]')
            expect(uidField.classList.contains('is-invalid')).toBe(true)
        })

        it('should only display errors for invalid fields', () => {
            const errors = {
                uid: ['The uid field is required.'],
            }

            modal.displayValidationErrors(errors)

            const firstNameError = document.querySelector('[name="first_name"]').parentElement.querySelector('small.text-danger')
            expect(firstNameError.style.display).toBe('none')
        })

        it('should clear all errors when clearErrors is called', () => {
            const errors = {
                uid: ['The uid field is required.'],
                first_name: ['The first name field is required.'],
            }

            modal.displayValidationErrors(errors)
            modal.clearErrors()

            const uidError = document.querySelector('[name="uid"]').parentElement.querySelector('small.text-danger')
            const firstNameError = document.querySelector('[name="first_name"]').parentElement.querySelector('small.text-danger')

            expect(uidError.style.display).toBe('none')
            expect(firstNameError.style.display).toBe('none')
            expect(document.querySelector('[name="uid"]').classList.contains('is-invalid')).toBe(false)
        })
    })

    describe('Form Data Preservation', () => {
        it('should preserve form data when validation fails', async () => {
            document.getElementById('uid').value = 'TEST001'
            document.getElementById('first_name').value = 'John'
            document.getElementById('last_name').value = 'Doe'

            const errors = {
                birthday: ['The birthday must be a valid date.'],
            }

            modal.displayValidationErrors(errors)

            expect(document.getElementById('uid').value).toBe('TEST001')
            expect(document.getElementById('first_name').value).toBe('John')
            expect(document.getElementById('last_name').value).toBe('Doe')
        })

        it('should preserve non-invalid fields when displaying errors', () => {
            document.getElementById('uid').value = 'TEST001'
            document.getElementById('first_name').value = 'John'
            document.getElementById('last_name').value = 'Doe'

            const errors = {
                birthday: ['The birthday must be a valid date.'],
            }

            modal.displayValidationErrors(errors)

            const uidError = document.querySelector('[name="uid"]').parentElement.querySelector('small.text-danger')
            const firstNameError = document.querySelector('[name="first_name"]').parentElement.querySelector('small.text-danger')

            expect(uidError.style.display).toBe('none')
            expect(firstNameError.style.display).toBe('none')
        })
    })

    describe('API Integration', () => {
        it('should send POST request when creating record', async () => {
            document.getElementById('uid').value = 'TEST001'
            document.getElementById('first_name').value = 'John'
            document.getElementById('last_name').value = 'Doe'

            mockFetch.mockResolvedValueOnce({
                ok: true,
                status: 201,
                json: async () => ({
                    success: true,
                    data: { id: 1, uid: 'TEST001', first_name: 'John', last_name: 'Doe' },
                }),
            })

            modal.mode = 'create'
            await modal.saveRecord()

            expect(mockFetch).toHaveBeenCalledWith(
                '/api/main-system',
                expect.objectContaining({
                    method: 'POST',
                    headers: expect.objectContaining({
                        'Content-Type': 'application/json',
                    }),
                })
            )
        })

        it('should send PUT request when updating record', async () => {
            document.getElementById('uid').value = 'TEST001'
            document.getElementById('first_name').value = 'John'
            document.getElementById('last_name').value = 'Doe'

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({
                    success: true,
                    data: { id: 1, uid: 'TEST001', first_name: 'John', last_name: 'Doe' },
                }),
            })

            modal.mode = 'edit'
            modal.recordId = 1
            await modal.saveRecord()

            expect(mockFetch).toHaveBeenCalledWith(
                '/api/main-system/1',
                expect.objectContaining({
                    method: 'PUT',
                })
            )
        })

        it('should handle validation errors from API', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 422,
                json: async () => ({
                    success: false,
                    errors: {
                        uid: ['The uid must be unique.'],
                    },
                }),
            })

            modal.mode = 'create'
            await modal.saveRecord()

            const uidError = document.querySelector('[name="uid"]').parentElement.querySelector('small.text-danger')
            expect(uidError.textContent).toBe('The uid must be unique.')
        })

        it('should call onRecordCreated callback after successful create', async () => {
            const onRecordCreated = jest.fn()
            modal.onRecordCreated = onRecordCreated

            const mockRecord = { id: 1, uid: 'TEST001', first_name: 'John', last_name: 'Doe' }

            mockFetch.mockResolvedValueOnce({
                ok: true,
                status: 201,
                json: async () => ({ success: true, data: mockRecord }),
            })

            // Mock closeModal to avoid Bootstrap Modal issues in tests
            modal.closeModal = jest.fn()

            modal.mode = 'create'
            await modal.saveRecord()

            expect(onRecordCreated).toHaveBeenCalledWith(mockRecord)
            expect(modal.closeModal).toHaveBeenCalled()
        })

        it('should call onRecordUpdated callback after successful update', async () => {
            const onRecordUpdated = jest.fn()
            modal.onRecordUpdated = onRecordUpdated

            const mockRecord = { id: 1, uid: 'TEST001', first_name: 'John', last_name: 'Doe' }

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true, data: mockRecord }),
            })

            // Mock closeModal to avoid Bootstrap Modal issues in tests
            modal.closeModal = jest.fn()

            modal.mode = 'edit'
            modal.recordId = 1
            await modal.saveRecord()

            expect(onRecordUpdated).toHaveBeenCalledWith(mockRecord)
            expect(modal.closeModal).toHaveBeenCalled()
        })
    })

    describe('Modal State Management', () => {
        it('should track form changes', () => {
            modal.initialFormData = { uid: '', first_name: '' }
            document.getElementById('uid').value = 'TEST001'

            modal.detectChanges()

            expect(modal.hasChanges).toBe(true)
        })

        it('should detect no changes when form is unchanged', () => {
            // Set initial form data
            document.getElementById('uid').value = 'TEST001'
            document.getElementById('first_name').value = 'John'
            document.getElementById('last_name').value = 'Doe'

            // Capture initial state
            const form = document.getElementById('testCrudModal-form')
            const formData = new FormData(form)
            modal.initialFormData = Object.fromEntries(formData)

            // Detect changes (should be false since nothing changed)
            modal.detectChanges()

            expect(modal.hasChanges).toBe(false)
        })

        it('should reset form to initial state', () => {
            document.getElementById('uid').value = 'TEST001'
            document.getElementById('first_name').value = 'John'

            modal.resetForm()

            expect(document.getElementById('uid').value).toBe('')
            expect(document.getElementById('first_name').value).toBe('')
            expect(modal.hasChanges).toBe(false)
        })
    })

    describe('Template Fields', () => {
        it('should render template fields when provided', () => {
            const templateFields = [
                { id: 1, field_name: 'Custom Field 1', field_value: 'Value 1' },
                { id: 2, field_name: 'Custom Field 2', field_value: 'Value 2' },
            ]

            modal.renderTemplateFields(templateFields)

            const section = document.getElementById('testCrudModal-template-fields')
            expect(section.style.display).toBe('block')

            const field1 = document.querySelector('[name="templateFields[1]"]')
            const field2 = document.querySelector('[name="templateFields[2]"]')

            expect(field1.value).toBe('Value 1')
            expect(field2.value).toBe('Value 2')
        })

        it('should hide template fields section when no fields provided', () => {
            modal.renderTemplateFields([])

            const section = document.getElementById('testCrudModal-template-fields')
            expect(section.style.display).toBe('none')
        })

        it('should clear template fields section', () => {
            const templateFields = [
                { id: 1, field_name: 'Custom Field 1', field_value: 'Value 1' },
            ]

            modal.renderTemplateFields(templateFields)
            modal.clearTemplateFields()

            const section = document.getElementById('testCrudModal-template-fields')
            expect(section.style.display).toBe('none')
            expect(modal.templateFields.length).toBe(0)
        })
    })
})
