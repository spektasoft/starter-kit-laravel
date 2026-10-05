import js from "@eslint/js";
import tsPlugin from "@typescript-eslint/eslint-plugin";
import globals from "globals";

const files = ["**/*.ts"];

export default [
    {
        ignores: [
            "**/node_modules/**",
            "vendor/**",
            "public/**",
            "storage/**",
            "bootstrap/cache/**",
        ],
    },
    {
        ...js.configs.recommended,
        files,
        languageOptions: {
            ecmaVersion: "latest",
            sourceType: "module",
            globals: globals.browser,
        },
    },
    ...tsPlugin.configs["flat/recommended"].map((config) => ({
        ...config,
        files,
    })),
    {
        files,
        rules: {
            "@typescript-eslint/ban-ts-comment": "off",
        },
    },
];
