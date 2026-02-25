# Template Fields API Documentation

## Overview

The Template Fields API allows programmatic management of custom fields within column mapping templates. These endpoints enable creating, reading, updating, and deleting template field definitions that extend the core system columns.

## Base URL

All endpoints are prefixed with `/api/templates/{templateId}/fields`

## Authentication

All endpoints require authentication via Laravel Sanctum or session-based authentication. Unauthenticated requests will receive a `401 Unauthorized` response.

## Authorization

All endpoints verify that the authenticated user owns the template. Attempting to access another user's template will result in a `404 Not Found` response.

## Field Types

Template fields support the following data types:

| Type | Description | Valid Values | Example |
|------|-------------|--------------|---------|
| `string` | Text data | Any text value | "Department A" |
| `integer` | Whole numbers | Numeric without decimals | 42, -10, 0 |
| `decimal` | Decimal numbers | Numeric with or without decimals | 3.14, 100.5, 42 |
| `date` | Date values | Any parseable date format | "2024-01-15", "01/15/2024" |
| `boolean` | True/false values | true/false, 1/0, yes/no, y/n | true, 1, "yes" |

---

## Endpoints

### 1. List Template Fields

Retrieve all custom fields defined for a specific template.

**Endpoint:** `GET /api/templates/{templateId}/fields`

**URL Parameters:**
- `templateId` (integer, required) - The ID of the template

**Response:** `200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "template_id": 5,
      "field_name": "department",
      "field_type": "string",
      "is_required": true,
      "created_at": "2024-02-24T10:30:00.000000Z",
      "updated_at": "2024-02-24T10:30:00.000000Z"
    },
    {
      "id": 2,
      "template_id": 5,
      "field_name": "employee_id",
      "field_type": "integer",
      "is_required": true,
      "created_at": "2024-02-24T10:31:00.000000Z",
      "updated_at": "2024-02-24T10:31:00.000000Z"
    },
    {
      "id": 3,
      "template_id": 5,
      "field_name": "salary",
      "field_type": "decimal",
      "is_required": false,
      "created_at": "2024-02-24T10:32:00.000000Z",
      "updated_at": "2024-02-24T10:32:00.000000Z"
    }
  ]
}
```

**Error Responses:**

`401 Unauthorized` - User not authenticated
```json
{
  "message": "Unauthenticated."
}
```

`404 Not Found` - Template not found or access denied
```json
{
  "success": false,
  "message": "Template not found or access denied"
}
```

**Example Request:**

```bash
curl -X GET \
  https://your-domain.com/api/templates/5/fields \
  -H 'Authorization: Bearer YOUR_API_TOKEN' \
  -H 'Accept: application/json'
```

---

### 2. Create Template Field

Add a new custom field to a template.

**Endpoint:** `POST /api/templates/{templateId}/fields`

**URL Parameters:**
- `templateId` (integer, required) - The ID of the template

**Request Body:**

```json
{
  "field_name": "department",
  "field_type": "string",
  "is_required": true
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `field_name` | string | Yes | Field name (alphanumeric and underscores only, max 255 chars) |
| `field_type` | string | Yes | One of: string, integer, date, boolean, decimal |
| `is_required` | boolean | No | Whether field is required (default: false) |

**Validation Rules:**
- `field_name` must match pattern: `/^[a-z0-9_]+$/i`
- `field_name` must be unique within the template
- `field_type` must be one of the supported types

**Response:** `201 Created`

```json
{
  "success": true,
  "message": "Template field created successfully",
  "data": {
    "id": 4,
    "template_id": 5,
    "field_name": "department",
    "field_type": "string",
    "is_required": true,
    "created_at": "2024-02-24T11:00:00.000000Z",
    "updated_at": "2024-02-24T11:00:00.000000Z"
  }
}
```

**Error Responses:**

`401 Unauthorized` - User not authenticated
```json
{
  "message": "Unauthenticated."
}
```

`404 Not Found` - Template not found or access denied
```json
{
  "success": false,
  "message": "Template not found or access denied"
}
```

`422 Unprocessable Entity` - Validation failed
```json
{
  "success": false,
  "message": "Field name already exists in this template"
}
```

```json
{
  "message": "The field name field is required.",
  "errors": {
    "field_name": [
      "The field name field is required."
    ]
  }
}
```

```json
{
  "message": "The field name format is invalid.",
  "errors": {
    "field_name": [
      "The field name format is invalid."
    ]
  }
}
```

**Example Request:**

```bash
curl -X POST \
  https://your-domain.com/api/templates/5/fields \
  -H 'Authorization: Bearer YOUR_API_TOKEN' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "field_name": "department",
    "field_type": "string",
    "is_required": true
  }'
```

---

### 3. Update Template Field

Modify an existing template field.

**Endpoint:** `PUT /api/templates/{templateId}/fields/{fieldId}`

**URL Parameters:**
- `templateId` (integer, required) - The ID of the template
- `fieldId` (integer, required) - The ID of the field to update

**Request Body:**

All fields are optional. Only include fields you want to update.

```json
{
  "field_name": "department_name",
  "field_type": "string",
  "is_required": false
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `field_name` | string | No | New field name (alphanumeric and underscores only, max 255 chars) |
| `field_type` | string | No | One of: string, integer, date, boolean, decimal |
| `is_required` | boolean | No | Whether field is required |

**Validation Rules:**
- If `field_name` is provided, it must match pattern: `/^[a-z0-9_]+$/i`
- If `field_name` is changed, it must be unique within the template
- If `field_type` is provided, it must be one of the supported types

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Template field updated successfully",
  "data": {
    "id": 4,
    "template_id": 5,
    "field_name": "department_name",
    "field_type": "string",
    "is_required": false,
    "created_at": "2024-02-24T11:00:00.000000Z",
    "updated_at": "2024-02-24T11:15:00.000000Z"
  }
}
```

**Error Responses:**

`401 Unauthorized` - User not authenticated
```json
{
  "message": "Unauthenticated."
}
```

`404 Not Found` - Template or field not found
```json
{
  "success": false,
  "message": "Template not found or access denied"
}
```

```json
{
  "success": false,
  "message": "Template field not found"
}
```

`422 Unprocessable Entity` - Validation failed
```json
{
  "success": false,
  "message": "Field name already exists in this template"
}
```

**Example Request:**

```bash
curl -X PUT \
  https://your-domain.com/api/templates/5/fields/4 \
  -H 'Authorization: Bearer YOUR_API_TOKEN' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "field_name": "department_name",
    "is_required": false
  }'
```

---

### 4. Delete Template Field

Remove a custom field from a template.

**Endpoint:** `DELETE /api/templates/{templateId}/fields/{fieldId}`

**URL Parameters:**
- `templateId` (integer, required) - The ID of the template
- `fieldId` (integer, required) - The ID of the field to delete

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Template field deleted successfully"
}
```

**Error Responses:**

`401 Unauthorized` - User not authenticated
```json
{
  "message": "Unauthenticated."
}
```

`404 Not Found` - Template or field not found
```json
{
  "success": false,
  "message": "Template not found or access denied"
}
```

```json
{
  "success": false,
  "message": "Template field not found"
}
```

**Example Request:**

```bash
curl -X DELETE \
  https://your-domain.com/api/templates/5/fields/4 \
  -H 'Authorization: Bearer YOUR_API_TOKEN' \
  -H 'Accept: application/json'
```

---

## Common Use Cases

### Creating a Template with Multiple Fields

```javascript
// 1. Create template (using existing template API)
const template = await createTemplate({
  name: "Employee Import Template",
  mappings: {
    "First Name": "first_name",
    "Last Name": "last_name",
    "Birthday": "birthday"
  }
});

// 2. Add custom fields
const fields = [
  { field_name: "department", field_type: "string", is_required: true },
  { field_name: "employee_id", field_type: "integer", is_required: true },
  { field_name: "salary", field_type: "decimal", is_required: false },
  { field_name: "hire_date", field_type: "date", is_required: true },
  { field_name: "is_active", field_type: "boolean", is_required: false }
];

for (const field of fields) {
  await fetch(`/api/templates/${template.id}/fields`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(field)
  });
}
```

### Updating Multiple Fields

```javascript
const updates = [
  { id: 1, changes: { is_required: false } },
  { id: 2, changes: { field_name: "emp_id" } },
  { id: 3, changes: { field_type: "integer" } }
];

for (const update of updates) {
  await fetch(`/api/templates/${templateId}/fields/${update.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(update.changes)
  });
}
```

### Retrieving and Displaying Fields

```javascript
// Fetch all fields for a template
const response = await fetch(`/api/templates/${templateId}/fields`, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const { data: fields } = await response.json();

// Display in UI
fields.forEach(field => {
  console.log(`${field.field_name} (${field.field_type})${field.is_required ? ' *' : ''}`);
});
```

---

## Field Validation

When files are uploaded using a template with custom fields, the system validates:

1. **Column Presence**: All template fields must be present in the uploaded file
2. **No Extra Columns**: Files cannot contain columns not defined in the template
3. **Type Validation**: Values are validated against the field type (sampled from first 10 rows)
4. **Required Fields**: Required fields cannot be empty

### Type Validation Examples

**Integer Field:**
- ✅ Valid: `42`, `-10`, `0`
- ❌ Invalid: `3.14`, `abc`, `42.0`

**Decimal Field:**
- ✅ Valid: `3.14`, `100`, `-5.5`
- ❌ Invalid: `abc`, `not a number`

**Date Field:**
- ✅ Valid: `2024-01-15`, `01/15/2024`, `15-Jan-2024`
- ❌ Invalid: `not a date`, `99/99/9999`

**Boolean Field:**
- ✅ Valid: `true`, `false`, `1`, `0`, `yes`, `no`, `y`, `n` (case-insensitive)
- ❌ Invalid: `maybe`, `2`, `unknown`

**String Field:**
- ✅ Valid: Any text value

---

## Error Handling

### Standard Error Response Format

All error responses follow a consistent format:

```json
{
  "success": false,
  "message": "Error description"
}
```

For validation errors (422), Laravel's validation format is used:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "The field name field is required.",
      "The field name format is invalid."
    ]
  }
}
```

### HTTP Status Codes

| Code | Meaning | When Used |
|------|---------|-----------|
| 200 | OK | Successful GET, PUT, DELETE |
| 201 | Created | Successful POST |
| 401 | Unauthorized | Missing or invalid authentication |
| 404 | Not Found | Template or field not found, or access denied |
| 422 | Unprocessable Entity | Validation failed |

---

## Security Considerations

### Authentication
All endpoints require valid authentication. Use Laravel Sanctum tokens or session-based authentication.

### Authorization
The system enforces ownership checks:
- Users can only access templates they own
- Attempting to access another user's template returns 404 (not 403) to prevent information disclosure

### Input Validation
- Field names are restricted to alphanumeric characters and underscores
- Field types are validated against a whitelist
- Maximum field name length is 255 characters
- Uniqueness is enforced at the database level

### Logging
All operations are logged with:
- Template ID
- Field ID (for updates/deletes)
- Field name
- User name
- Timestamp

---

## Rate Limiting

API endpoints follow Laravel's default rate limiting configuration. Typically:
- Authenticated requests: 60 requests per minute
- Check your application's `RouteServiceProvider` for specific limits

---

## Best Practices

### Field Naming
- Use lowercase with underscores: `employee_id`, `hire_date`
- Be descriptive but concise: `dept` vs `department_name_full_description`
- Avoid special characters and spaces
- Use consistent naming conventions across templates

### Field Types
- Choose the most specific type: Use `integer` instead of `string` for numeric IDs
- Use `decimal` for monetary values
- Use `boolean` for yes/no flags
- Use `date` for date values to enable proper validation

### Required Fields
- Mark fields as required only when truly necessary
- Consider data availability when marking fields required
- Required fields will cause upload failures if missing

### Error Handling
- Always check the `success` field in responses
- Handle 404 errors gracefully (template may have been deleted)
- Display validation errors to users clearly
- Log errors for debugging

---

## Changelog

### Version 1.0 (February 2024)
- Initial API release
- Support for five field types: string, integer, decimal, date, boolean
- CRUD operations for template fields
- Ownership-based authorization
- Field uniqueness validation

---

## Support

For issues or questions:
- Check application logs for detailed error messages
- Verify authentication tokens are valid
- Ensure template ownership before operations
- Review field name format requirements

