/**
 * Compile-time regression guard for the inferred `Window["Livewire"]` type.
 *
 * Livewire ships no type declarations, so `typeof Livewire` in `global.d.ts`
 * is inferred from the bundled JS. If a future Livewire release degrades that
 * inference to `any` or removes `on`, `tsc --noEmit` fails on this file.
 *
 * This file is type-only and is never imported at runtime.
 */

type IsAny<T> = 0 extends 1 & T ? true : false;
type Assert<T extends true> = T;

/** `Window["Livewire"]` must be a real inferred type, not `any`. */
export type LivewireIsNotAny = Assert<
    IsAny<Window["Livewire"]> extends false ? true : false
>;

/** `Window["Livewire"]` must expose an `on` member. */
export type LivewireExposesOn = Assert<
    "on" extends keyof Window["Livewire"] ? true : false
>;

/** Mirrors the listener registration in `events.ts`; must type-check under `--strict`. */
export function assertScrollToListenerSignature(): void {
    window.Livewire.on("scroll-to", (event: { element: string }[]) => {
        void event;
    });
}
