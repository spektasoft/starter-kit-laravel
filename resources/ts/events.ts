const Android = window.Android;

document.addEventListener("livewire:init", () => {
    window.Livewire.on("scroll-to", (event: { element: string }[]) => {
        document.querySelector(event[0].element ?? "body")?.scrollIntoView();
    });
});

document.addEventListener("livewire:navigate", () => {
    Android?.navigate?.();
});

document.addEventListener("livewire:navigated", () => {
    setTimeout(() => {
        Android?.navigated?.();
    }, 100);
});
