import { cva, type VariantProps } from 'class-variance-authority'

export const badgeVariants = cva(
  'inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
  {
    variants: {
      variant: {
        default:
          'border-transparent bg-primary text-primary-foreground shadow-sm hover:bg-primary-hover',
        secondary:
          'border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80',
        destructive:
          'border-transparent bg-rose-500/12 text-rose-700 shadow-none hover:bg-rose-500/18',
        outline: 'border-border/80 bg-card/70 text-foreground',
        success:
          'border-transparent bg-emerald-500/12 text-emerald-700 hover:bg-emerald-500/18',
        warning:
          'border-transparent bg-violet-500/12 text-violet-700 hover:bg-violet-500/18',
        info: 'border-transparent bg-sky-500/12 text-sky-700 hover:bg-sky-500/18',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
)

export type BadgeVariants = VariantProps<typeof badgeVariants>
