interface PageHeaderProps {
  title: string
}

// The top of each page except Today: the page name as the only h1.
export function PageHeader({ title }: PageHeaderProps) {
  return (
    <header className="px-5 pt-5 pb-3">
      <h1 className="font-display text-3xl font-extrabold">{title}</h1>
    </header>
  )
}
