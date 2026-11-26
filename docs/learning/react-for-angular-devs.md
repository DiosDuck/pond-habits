# React for Angular developers

A map from Angular ideas to the React stack of this repo (ADR 0003). Each row links to real code here.
Official guide: https://react.dev/learn

## The big difference

Angular is a full framework: modules, dependency injection, RxJS, forms and router in one box.
React is only the view layer. A component is a plain function that returns JSX.
This repo adds one library for each other job: React Router, TanStack Query, React Hook Form, Zod, Tailwind.

## Map

| Angular | React in this repo | Example |
|---|---|---|
| `NgModule`, `declarations` | Nothing. Each file imports what it uses. | [main.tsx](../../frontend/src/main.tsx) |
| Component class + HTML template | Function that returns JSX (HTML-like syntax in TypeScript) | [TodayPage.tsx](../../frontend/src/pages/TodayPage.tsx) |
| `@Input()` | Props: the function argument, for example `function Card({ title })` | [PlaceholderPage.tsx](../../frontend/src/pages/PlaceholderPage.tsx) |
| `@Output()` + `EventEmitter` | A callback prop, for example `onSave={() => ...}` | (first one in Phase 3) |
| Class field for UI state | `useState` hook | [App.tsx](../../frontend/src/app/App.tsx) |
| `ngOnInit`, `ngOnDestroy` | `useEffect`. Do not use it to load data: use TanStack Query. | - |
| Service + `HttpClient` + RxJS | A plain `fetch` function + TanStack Query `useQuery` | [health.ts](../../frontend/src/api/health.ts) |
| `async` pipe, loading flags | `isPending`, `isError`, `data` from `useQuery` | (first one in Phase 3, login) |
| Shared service for app state | React context. Zustand only if necessary (ADR 0003). | - |
| `Routes` array, `<router-outlet>` | React Router route list, `<Outlet />` | [routes.tsx](../../frontend/src/app/routes.tsx) |
| `routerLink`, `routerLinkActive` | `<Link to="/tasks">`, `<NavLink>` (knows the active page) | [MainNav.tsx](../../frontend/src/layout/MainNav.tsx) |
| Reactive forms + validators | React Hook Form + Zod schema | (Phase 3) |
| `*ngIf` | `{isOpen && <Panel />}` or a ternary | - |
| `*ngFor` | `{items.map((item) => <Row key={item.id} item={item} />)}` | - |
| Pipe | A plain function call inside JSX | - |
| Component CSS file | Tailwind classes in `className`, colour tokens in `index.css` | [index.css](../../frontend/src/index.css) |
| `@angular/localize`, `i18n` attribute | react-i18next: `const { t } = useTranslation()`, then `t('today.title')` | [en.json](../../frontend/src/i18n/en.json) |
| Zone.js change detection | React renders again when state or props change | - |
| `TestBed` | React Testing Library: `render` and `screen` | [navigation.test.tsx](../../frontend/src/app/navigation.test.tsx) |
| `HttpClientTestingModule` | MSW: it answers `fetch` calls in tests | [server.ts](../../frontend/src/test/server.ts) |
| Angular CLI, `ng serve` | Vite, `npm run dev` (the `web` container) | [vite.config.ts](../../frontend/vite.config.ts) |
| `environment.ts` | `import.meta.env` (Vite) | - |

## Rules that catch Angular developers

1. **Rules of hooks.** Call hooks (`useState`, `useQuery`, ...) only at the top level of a component.
   Never in an `if`, a loop or a callback. ESLint (`react-hooks`) checks this.
2. **Do not change state in place.** Make a new object or array: `setItems([...items, newItem])`.
   React compares by reference. A changed object with the same reference does not render again.
3. **Give each list item a stable `key`.** Use the ID, not the array index.
4. **Data from the server is not UI state.** Keep it in TanStack Query, not in `useState`.
5. **StrictMode runs some code two times in development.** This finds side effects. It does not happen in production.
6. **One file, components only.** A file that exports a component exports only components (Fast Refresh).
   That is why [queryClient.ts](../../frontend/src/app/queryClient.ts) is a separate file.

## Read the code in this order

1. [main.tsx](../../frontend/src/main.tsx): start of the app.
2. [App.tsx](../../frontend/src/app/App.tsx): providers (query client, router).
3. [routes.tsx](../../frontend/src/app/routes.tsx): URL to page.
4. [AppLayout.tsx](../../frontend/src/layout/AppLayout.tsx) and [TodayPage.tsx](../../frontend/src/pages/TodayPage.tsx): the frame and one page.
5. [health.ts](../../frontend/src/api/health.ts): one API call with a runtime type check.
6. [navigation.test.tsx](../../frontend/src/app/navigation.test.tsx): component tests.

## Links

- Thinking in React: https://react.dev/learn/thinking-in-react
- Hooks reference: https://react.dev/reference/react/hooks
- TanStack Query: https://tanstack.com/query/latest/docs/framework/react/overview
- React Router: https://reactrouter.com/
- Testing Library: https://testing-library.com/docs/react-testing-library/intro/
