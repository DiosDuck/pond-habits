// Shown for a moment while the code of a page loads (lazy routes). Usually too short to see.
// aria-busy tells a screen reader that the page is not ready yet.
export function PageLoading() {
  return <div aria-busy="true" className="min-h-dvh" />
}
