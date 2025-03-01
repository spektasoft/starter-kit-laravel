export interface Android {
    navigate?: () => void;
    navigated?: () => void;
    switchLanguage?: (lang: string) => void;
}
