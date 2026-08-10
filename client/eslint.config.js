import globals from 'globals';

export default [
    {
        ignores: ['node_modules/', 'css/'],
    },
    {
        files: ['js/**/*.js'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: globals.browser,
        },
        rules: {
            'no-unused-vars': 'error',
            'no-undef': 'error',
            eqeqeq: 'error',
            'no-var': 'error',
            'prefer-const': 'error',
        },
    },
];
