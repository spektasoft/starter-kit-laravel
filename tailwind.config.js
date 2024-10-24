import colors from "tailwindcss/colors";
import defaultTheme from "tailwindcss/defaultTheme";
import preset from "./vendor/filament/support/tailwind.config.preset";

/** @type {import('tailwindcss').Config} */
export default {
    presets: [preset],
    content: [
        "./app/Filament/**/*.php",
        "./lang/**/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/ts/**/*.ts",
        "./storage/framework/views/*.php",
        "./vendor/filament/**/*.blade.php",
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
    ],

    theme: {
        extend: {
            colors: {
                primary: colors["indigo"],
            },
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
        },
    },
};
