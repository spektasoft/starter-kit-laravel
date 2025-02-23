import { Alpine } from "alpinejs";
import { AxiosStatic } from "axios";
// @ts-ignore
import { Livewire } from "../../vendor/livewire/livewire/dist/livewire.js";
import { Android } from "./android";

declare global {
    interface Window {
        Alpine: Alpine;
        axios: AxiosStatic;
        Livewire: Livewire;
        Android?: Android;
    }
}

export {};
