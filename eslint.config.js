import prettier from 'eslint-config-prettier';
import vue from 'eslint-plugin-vue';

import tseslint from 'typescript-eslint';

export default tseslint.config(
    tseslint.configs.recommended,
    vue.configs['flat/essential'],
    {
        files: ['**/*.vue'],
        languageOptions: {
            parserOptions: {
                parser: tseslint.parser,
                extraFileExtensions: ['.vue'],
            },
        },
    },
    {
        ignores: ['vendor', 'node_modules', 'public', 'bootstrap/ssr', 'tailwind.config.js', 'resources/js/components/ui/*'],
    },
    {
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/no-mutating-props': ['error', { shallowOnly: true }],
            '@typescript-eslint/no-explicit-any': 'off',
        },
    },
    prettier,
);
