import { useId, type ComponentProps } from 'react'

// ComponentProps<'input'> includes "ref" (React 19), so react-hook-form can register the input.
interface FormFieldProps extends ComponentProps<'input'> {
  label: string
  error?: string | undefined
  hint?: string
}

// A labelled input with its hint and its error. The error is linked to the input
// (aria-describedby, aria-invalid), so a screen reader reads it.
export function FormField({ label, error, hint, ...input }: FormFieldProps) {
  const id = useId()
  const hintId = `${id}-hint`
  const errorId = `${id}-error`
  const describedBy =
    [hint ? hintId : null, error ? errorId : null].filter(Boolean).join(' ') ||
    undefined

  return (
    <div className="flex flex-col gap-1.5">
      <label htmlFor={id} className="text-sm font-semibold">
        {label}
      </label>
      <input
        id={id}
        aria-invalid={error ? true : undefined}
        aria-describedby={describedBy}
        className="border-line bg-ground focus-visible:outline-primary aria-invalid:border-minus min-h-11 rounded-xl border px-3 text-base"
        {...input}
      />
      {hint ? (
        <p id={hintId} className="text-muted text-sm">
          {hint}
        </p>
      ) : null}
      {error ? (
        <p id={errorId} className="text-minus text-sm font-semibold">
          {error}
        </p>
      ) : null}
    </div>
  )
}
