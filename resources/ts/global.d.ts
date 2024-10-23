import { Alpine } from "alpinejs";
import { AxiosStatic } from "axios";

declare global {
    interface Window {
        Alpine: Alpine;
        axios: AxiosStatic;
    }
}

export {};
