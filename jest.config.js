export default {
    testEnvironment: 'jsdom',
    testMatch: ['**/tests/JavaScript/**/*.test.js'],
    collectCoverageFrom: [
        'resources/views/**/*.blade.php',
        '!**/node_modules/**',
        '!**/vendor/**'
    ],
    coveragePathIgnorePatterns: [
        '/node_modules/',
        '/vendor/'
    ],
    transform: {},
    moduleFileExtensions: ['js', 'json'],
    verbose: true
};
