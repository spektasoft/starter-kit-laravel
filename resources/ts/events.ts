document.addEventListener("livewire:init", () => {
    window.Livewire.on("scroll-to", (event: { element: string }[]) => {
        document.querySelector(event[0].element ?? "body")?.scrollIntoView();
    });
});
