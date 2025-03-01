import { AlpineComponent } from "alpinejs";

const Alpine = window.Alpine;
const Android = window.Android;

type LanguageSwitcher = {
    switchLanguage(lang: string): void;
};

const languageSwitcherComponentFactory: () => AlpineComponent<LanguageSwitcher> =
    () => ({
        switchLanguage(lang: string) {
            Android?.switchLanguage?.(lang === "id" ? "in" : lang);
        },
    });

Alpine.data("languageSwitcher", languageSwitcherComponentFactory);
