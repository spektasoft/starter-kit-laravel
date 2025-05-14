import { Alpine } from "alpinejs";
import { AxiosStatic } from "axios";
import { Livewire } from "../../vendor/livewire/livewire/dist/livewire.esm";
import { Android } from "./android";

declare global {
    interface Window {
        Alpine: Alpine;
        axios: AxiosStatic;
        Livewire: typeof Livewire;
        Android?: Android;
    }
}

export {};
