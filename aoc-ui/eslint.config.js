import pluginVue from 'eslint-plugin-vue'

export default [
  ...pluginVue.configs['flat/essential'],
  {
    files: ['**/*.js', '**/*.vue'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        document: 'readonly',
        window: 'readonly',
      },
    },
  },
  {
    ignores: ['node_modules', 'dist'],
  },
]
