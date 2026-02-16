import { cva, type VariantProps } from 'class-variance-authority'

export const badgeVariants = cva(
  'inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
  {
    variants: {
      variant: {
        default:
          'border-transparent bg-primary text-primary-foreground shadow hover:bg-primary/80',
        secondary:
          'border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80',
        destructive:
          'border-transparent bg-destructive/12 text-destructive shadow-none hover:bg-destructive/18',
        outline: 'border-border/80 bg-card/70 text-foreground',
        success:
          'border-transparent bg-emerald-700/12 text-emerald-800 hover:bg-emerald-700/18',
        warning:
          'border-transparent bg-amber-700/14 text-amber-800 hover:bg-amber-700/18',
        info: 'border-transparent bg-sky-800/12 text-sky-800 hover:bg-sky-800/18',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
)

export type BadgeVariants = VariantProps<typeof badgeVariants>
