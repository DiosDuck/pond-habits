import eventRepeat from '@material-symbols/svg-400/rounded/event_repeat.svg?raw'
import home from '@material-symbols/svg-400/rounded/home.svg?raw'
import homeFill from '@material-symbols/svg-400/rounded/home-fill.svg?raw'
import moreHoriz from '@material-symbols/svg-400/rounded/more_horiz.svg?raw'
import nutrition from '@material-symbols/svg-400/rounded/nutrition.svg?raw'
import redeem from '@material-symbols/svg-400/rounded/redeem.svg?raw'
import settings from '@material-symbols/svg-400/rounded/settings.svg?raw'
import shieldPerson from '@material-symbols/svg-400/rounded/shield_person.svg?raw'
import taskAlt from '@material-symbols/svg-400/rounded/task_alt.svg?raw'
import timer from '@material-symbols/svg-400/rounded/timer.svg?raw'
import timerFill from '@material-symbols/svg-400/rounded/timer-fill.svg?raw'
import toll from '@material-symbols/svg-400/rounded/toll.svg?raw'

// Material Symbols Rounded, weight 400 (ADR 0024). Vite imports each SVG file as text.
// Only the path is used, so the icon takes the text colour (currentColor).
// Import only the icons that the app uses: each one adds to the bundle.
const files = {
  event_repeat: eventRepeat,
  home,
  'home-fill': homeFill,
  more_horiz: moreHoriz,
  nutrition,
  redeem,
  settings,
  shield_person: shieldPerson,
  task_alt: taskAlt,
  timer,
  'timer-fill': timerFill,
  toll,
}

export type IconName = keyof typeof files

function pathOf(svg: string): string {
  return /\sd="([^"]+)"/.exec(svg)?.[1] ?? ''
}

interface IconProps {
  name: IconName
  size?: number
  className?: string
}

// Decorative: the link or button next to it has the text. Screen readers skip the icon.
export function Icon({ name, size = 24, className }: IconProps) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 -960 960 960"
      fill="currentColor"
      aria-hidden="true"
      focusable="false"
      className={className}
    >
      <path d={pathOf(files[name])} />
    </svg>
  )
}
