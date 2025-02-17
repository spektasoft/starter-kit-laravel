import { AlpineComponent } from "alpinejs";

const Alpine = window.Alpine;

type NavigationMenu = {
    open: boolean;
    lastScrollY: number;
    show: boolean;
    scroll(): void;
    toggle(): void;
};

const navigationMenuComponentFactory: () => AlpineComponent<NavigationMenu> =
    () => ({
        lastScrollY: 0,
        open: false,
        show: true,
        scroll() {
            const currentScrollY = window.scrollY;
            if (currentScrollY > this.lastScrollY && !this.open) {
                this.show = false;
            } else {
                this.show = true;
            }
            this.lastScrollY = currentScrollY;
        },
        toggle() {
            this.open = !this.open;
        },
    });

Alpine.data("navigationMenu", navigationMenuComponentFactory);
