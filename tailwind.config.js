import colors from "tailwindcss/colors";
import defaultTheme from "tailwindcss/defaultTheme";
import preset from "./vendor/filament/support/tailwind.config.preset";

/** @type {import('tailwindcss').Config} */
export default {
    presets: [preset],
    content: [
        "./app/Filament/**/*.php",
        "./app/Livewire/**/*.php",
        "./lang/**/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/ts/**/*.ts",
        "./storage/framework/views/*.php",
        "./vendor/awcodes/filament-curator/resources/**/*.blade.php",
        "./vendor/filament/**/*.blade.php",
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
        "<path-to-vendor>/awcodes/filament-tiptap-editor/resources/**/*.blade.php",
    ],

    theme: {
        extend: {
            colors: {
                primary: colors["indigo"],
                secondary: colors["emerald"],
            },
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
        },
    },
};
