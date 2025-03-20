import laravel from "laravel-vite-plugin";
import { defineConfig, loadEnv } from "vite";

export default defineConfig(({ mode }) => {
    process.env = { ...process.env, ...loadEnv(mode, process.cwd()) };

    return {
        server: {
            hmr: {
                host: process.env.VITE_HOST,
            },
        },
        plugins: [
            laravel({
                input: ["resources/css/app.css", "resources/ts/app.ts"],
                refresh: true,
            }),
        ],
    };
});
