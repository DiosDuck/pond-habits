// A short date in the user's language, for example "Nov 24, 2026".
export function shortDate(iso: string, language: string): string {
  return new Intl.DateTimeFormat(language, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(new Date(iso))
}
