export interface Android {
    getPackageName?: () => string | null;
    getVersionCode?: () => number | null;
    navigate?: () => void;
    navigated?: () => void;
    switchLanguage?: (lang: string) => void;
}
