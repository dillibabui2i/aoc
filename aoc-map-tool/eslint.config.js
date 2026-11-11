import js from '@eslint/js'
import pluginVue from 'eslint-plugin-vue'
import prettierConfig from '@vue/eslint-config-prettier'

export default [
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    prettierConfig,
    {
        languageOptions: {
            globals: {
                window: 'readonly',
                document: 'readonly',
                require: 'readonly',
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/component-api-style': ['error', ['script-setup']],
            'vue/block-order': ['error', { order: ['template', 'script', 'style'] }],
            'vue/no-unused-refs': 'error',
            'vue/require-default-prop': 'error',
            'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
        },
    },
]