# JavaScript Unit Tests

This directory contains unit tests for JavaScript modules used in the application.

## Running Tests

```bash
# Install dependencies first
npm install

# Run all tests
npm test

# Run tests in watch mode
npm run test:watch

# Run tests with coverage report
npm run test:coverage
```

## Test Files

- `FieldBreakdownModal.test.js` - Tests for the Field Breakdown Modal functionality including:
  - Loading field breakdown data via AJAX
  - Rendering field comparison tables
  - Filtering fields by status
  - Exporting data to CSV
  - Error handling

## Test Coverage

The tests cover:
- Happy path scenarios
- Error handling
- Edge cases (empty data, null values)
- CSV escaping for special characters
- Filter functionality
- DOM manipulation

## Writing New Tests

Follow the existing test structure:
1. Use `describe` blocks to group related tests
2. Use `beforeEach` to set up test fixtures
3. Use clear, descriptive test names
4. Follow the Arrange-Act-Assert pattern
5. Mock external dependencies (fetch, DOM elements)

## Notes

- Tests use Jest with jsdom environment to simulate browser DOM
- Global objects like `fetch`, `alert`, and `Blob` are mocked
- DOM elements are mocked to avoid dependency on actual HTML structure
