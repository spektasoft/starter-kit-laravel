import { AlpineComponent } from "alpinejs";

const Alpine = window.Alpine;

type Header = {
    lastScrollY: number;
    show: boolean;
    scroll(): void;
};

const headerComponentFactory: () => AlpineComponent<Header> = () => ({
    lastScrollY: 0,
    show: true,
    scroll() {
        const currentScrollY = window.scrollY;
        if (currentScrollY > this.lastScrollY) {
            this.show = false;
        } else {
            this.show = true;
        }
        this.lastScrollY = currentScrollY;
    },
});

Alpine.data("header", headerComponentFactory);
