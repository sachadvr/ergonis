# Plan Détaillé - Nouveau Frontend Vue 3 + shadcn-vue
## JobPlanner - Application de Suivi de Candidatures

---

## 🎯 Vision & Objectifs

### Design Philosophy
- **Design System Moderne 2026**: Inspiré de Linear, Vercel, Raycast, Arc Browser
- **Micro-interactions fluides**: Animations subtiles avec Framer Motion
- **Interface épurée**: Moins de chrome, plus de contenu
- **Dark-first**: Mode sombre par défaut avec excellent contraste
- **Spatial design**: Utilisation intelligente de l'espace et de la profondeur
- **Cohérence shadcn**: Composants réutilisables avec Radix UI primitives

### Principes SOLID appliqués au Frontend
- **S** (Single Responsibility): Chaque composant/composable a une responsabilité unique
- **O** (Open/Closed): Extensions via composables, fermé aux modifications
- **L** (Liskov Substitution): Composants interchangeables via interfaces communes
- **I** (Interface Segregation): Composables spécialisés plutôt que monolithiques
- **D** (Dependency Inversion): Injection de dépendances via provide/inject

---

## 🏗️ Architecture Technique

### Stack Technologique

```typescript
{
  "framework": "Vue 3.4+ (Composition API + TypeScript)",
  "buildTool": "Vite 6+",
  "language": "TypeScript 5.6+",
  "components": "shadcn-vue (Radix Vue primitives)",
  "styling": "Tailwind CSS 4+",
  "stateManagement": "Pinia 2.2+",
  "routing": "Vue Router 4.4+",
  "forms": "VeeValidate 4+ + Zod",
  "animations": "@vueuse/motion",
  "dates": "date-fns 4+",
  "http": "ofetch (auto-retry, better DX)",
  "icons": "lucide-vue-next",
  "richText": "TipTap 3+",
  "calendar": "@schedule-x/vue 2+",
  "charts": "tremor-vue (better than chart.js)",
  "dragDrop": "@vueuse/gesture",
  "testing": "Vitest + Testing Library Vue"
}
```

### Structure du Projet

```
jobplanner-frontend/
├── .vscode/                          # IDE config
│   ├── settings.json
│   └── extensions.json
├── public/
│   └── favicon.svg
├── src/
│   ├── app/                          # Application core
│   │   ├── config/
│   │   │   ├── app.config.ts        # App-wide config
│   │   │   └── theme.config.ts      # Theme tokens
│   │   ├── providers/               # Global providers
│   │   │   ├── AppProvider.vue      # Root provider
│   │   │   ├── ThemeProvider.vue
│   │   │   └── QueryProvider.vue
│   │   └── router/
│   │       ├── index.ts
│   │       ├── routes.ts
│   │       └── guards.ts            # Auth guards
│   │
│   ├── assets/                       # Static assets
│   │   ├── fonts/                   # Inter Variable, Geist Mono
│   │   └── images/
│   │
│   ├── components/                   # Shared components
│   │   ├── ui/                      # shadcn-vue components
│   │   │   ├── button/
│   │   │   │   ├── Button.vue
│   │   │   │   └── button.variants.ts
│   │   │   ├── card/
│   │   │   ├── dialog/
│   │   │   ├── dropdown-menu/
│   │   │   ├── input/
│   │   │   ├── label/
│   │   │   ├── select/
│   │   │   ├── textarea/
│   │   │   ├── toast/
│   │   │   ├── tooltip/
│   │   │   ├── badge/
│   │   │   ├── avatar/
│   │   │   ├── command/            # ⌘K command palette
│   │   │   ├── popover/
│   │   │   └── skeleton/
│   │   │
│   │   ├── layout/                  # Layout components
│   │   │   ├── AppShell.vue        # Main shell
│   │   │   ├── Sidebar.vue         # Collapsible sidebar
│   │   │   ├── Topbar.vue          # Header
│   │   │   ├── Breadcrumbs.vue
│   │   │   └── PageHeader.vue
│   │   │
│   │   └── shared/                  # Business components
│   │       ├── ApplicationCard.vue
│   │       ├── StatusBadge.vue
│   │       ├── CompanyLogo.vue
│   │       ├── EmptyState.vue
│   │       ├── LoadingState.vue
│   │       └── ErrorState.vue
│   │
│   ├── features/                     # Feature modules (DDD)
│   │   │
│   │   ├── auth/
│   │   │   ├── api/
│   │   │   │   └── auth.api.ts
│   │   │   ├── components/
│   │   │   │   ├── LoginForm.vue
│   │   │   │   └── RegisterForm.vue
│   │   │   ├── composables/
│   │   │   │   └── useAuth.ts
│   │   │   ├── stores/
│   │   │   │   └── auth.store.ts
│   │   │   ├── types/
│   │   │   │   └── auth.types.ts
│   │   │   └── views/
│   │   │       ├── LoginView.vue
│   │   │       └── RegisterView.vue
│   │   │
│   │   ├── dashboard/
│   │   │   ├── components/
│   │   │   │   ├── StatsGrid.vue
│   │   │   │   ├── ActivityFeed.vue
│   │   │   │   ├── QuickActions.vue
│   │   │   │   └── UpcomingInterviews.vue
│   │   │   ├── composables/
│   │   │   │   └── useDashboardData.ts
│   │   │   └── views/
│   │   │       └── DashboardView.vue
│   │   │
│   │   ├── applications/
│   │   │   ├── api/
│   │   │   │   └── applications.api.ts
│   │   │   ├── components/
│   │   │   │   ├── ApplicationBoard/
│   │   │   │   │   ├── ApplicationBoard.vue
│   │   │   │   │   ├── BoardColumn.vue
│   │   │   │   │   ├── BoardCard.vue
│   │   │   │   │   └── BoardFilters.vue
│   │   │   │   ├── ApplicationDetail/
│   │   │   │   │   ├── ApplicationDetail.vue
│   │   │   │   │   ├── ApplicationHeader.vue
│   │   │   │   │   ├── ApplicationTimeline.vue
│   │   │   │   │   ├── ApplicationNotes.vue
│   │   │   │   │   └── ApplicationActions.vue
│   │   │   │   └── ApplicationForm.vue
│   │   │   ├── composables/
│   │   │   │   ├── useApplications.ts
│   │   │   │   ├── useApplicationMutations.ts
│   │   │   │   └── useApplicationFilters.ts
│   │   │   ├── stores/
│   │   │   │   └── applications.store.ts
│   │   │   ├── types/
│   │   │   │   └── application.types.ts
│   │   │   ├── utils/
│   │   │   │   └── application.utils.ts
│   │   │   └── views/
│   │   │       ├── ApplicationsKanbanView.vue
│   │   │       ├── ApplicationsTableView.vue
│   │   │       └── ApplicationDetailView.vue
│   │   │
│   │   ├── job-offers/
│   │   │   ├── api/
│   │   │   │   └── job-offers.api.ts
│   │   │   ├── components/
│   │   │   │   ├── JobOfferCard.vue
│   │   │   │   ├── JobOfferForm.vue
│   │   │   │   └── JobOfferFilters.vue
│   │   │   ├── composables/
│   │   │   │   └── useJobOffers.ts
│   │   │   ├── stores/
│   │   │   │   └── job-offers.store.ts
│   │   │   ├── types/
│   │   │   │   └── job-offer.types.ts
│   │   │   └── views/
│   │   │       └── JobOffersView.vue
│   │   │
│   │   ├── interviews/
│   │   │   ├── api/
│   │   │   │   └── interviews.api.ts
│   │   │   ├── components/
│   │   │   │   ├── InterviewCalendar.vue
│   │   │   │   ├── InterviewCard.vue
│   │   │   │   ├── InterviewForm.vue
│   │   │   │   └── InterviewTypeIcon.vue
│   │   │   ├── composables/
│   │   │   │   └── useInterviews.ts
│   │   │   ├── stores/
│   │   │   │   └── interviews.store.ts
│   │   │   ├── types/
│   │   │   │   └── interview.types.ts
│   │   │   └── views/
│   │   │       └── InterviewsView.vue
│   │   │
│   │   ├── emails/
│   │   │   ├── api/
│   │   │   │   └── emails.api.ts
│   │   │   ├── components/
│   │   │   │   ├── EmailInbox/
│   │   │   │   │   ├── EmailInbox.vue
│   │   │   │   │   ├── EmailList.vue
│   │   │   │   │   ├── EmailListItem.vue
│   │   │   │   │   └── EmailDetail.vue
│   │   │   │   ├── EmailComposer/
│   │   │   │   │   ├── EmailComposer.vue
│   │   │   │   │   └── EmailEditor.vue
│   │   │   │   └── EmailFilters.vue
│   │   │   ├── composables/
│   │   │   │   ├── useEmails.ts
│   │   │   │   └── useEmailComposer.ts
│   │   │   ├── stores/
│   │   │   │   └── emails.store.ts
│   │   │   ├── types/
│   │   │   │   └── email.types.ts
│   │   │   └── views/
│   │   │       └── EmailsView.vue
│   │   │
│   │   └── settings/
│   │       ├── api/
│   │       │   └── settings.api.ts
│   │       ├── components/
│   │       │   ├── MailConfigSection.vue
│   │       │   ├── FollowUpRulesSection.vue
│   │       │   ├── PreferencesSection.vue
│   │       │   └── LogsSection.vue
│   │       ├── composables/
│   │       │   └── useSettings.ts
│   │       ├── stores/
│   │       │   └── settings.store.ts
│   │       ├── types/
│   │       │   └── settings.types.ts
│   │       └── views/
│   │           └── SettingsView.vue
│   │
│   ├── lib/                          # Shared utilities
│   │   ├── api/
│   │   │   ├── client.ts            # HTTP client
│   │   │   ├── interceptors.ts
│   │   │   └── transforms.ts        # JSON-LD transforms
│   │   ├── utils/
│   │   │   ├── cn.ts                # classnames utility
│   │   │   ├── date.ts              # Date formatting
│   │   │   ├── string.ts
│   │   │   └── validation.ts
│   │   ├── hooks/                   # Generic composables
│   │   │   ├── useMediaQuery.ts
│   │   │   ├── useDebounce.ts
│   │   │   ├── useLocalStorage.ts
│   │   │   ├── useCommandPalette.ts
│   │   │   └── useKeyboardShortcut.ts
│   │   └── constants/
│   │       ├── app.constants.ts
│   │       └── api.constants.ts
│   │
│   ├── types/                        # Global types
│   │   ├── api.types.ts
│   │   ├── models.types.ts
│   │   └── env.d.ts
│   │
│   ├── App.vue                       # Root component
│   └── main.ts                       # App entry
│
├── .env.development
├── .env.production
├── .eslintrc.cjs                     # ESLint config
├── .prettierrc.json                  # Prettier config
├── components.json                   # shadcn-vue config
├── index.html
├── package.json
├── tailwind.config.ts
├── tsconfig.json
├── tsconfig.node.json
└── vite.config.ts
```

---

## 🎨 Design System

### Tokens de Design

```typescript
// theme.config.ts
export const themeConfig = {
  colors: {
    // Palette principale - Zinc comme base neutre
    background: {
      base: 'hsl(240 10% 3.9%)',           // #09090b - Presque noir
      elevated: 'hsl(240 5.9% 10%)',       // #18181b - Cards
      hover: 'hsl(240 4.8% 15%)',          // #27272a - Hover states
    },
    
    foreground: {
      primary: 'hsl(0 0% 98%)',            // #fafafa - Texte principal
      secondary: 'hsl(240 5% 64.9%)',      // #a1a1aa - Texte secondaire
      tertiary: 'hsl(240 3.8% 46.1%)',     // #71717a - Texte tertiaire
    },
    
    // Accent - Inspiré de Linear (Indigo/Blue subtil)
    primary: {
      50: 'hsl(228 100% 97%)',
      100: 'hsl(228 95% 93%)',
      500: 'hsl(228 94% 67%)',             // Accent principal
      600: 'hsl(228 87% 60%)',
      700: 'hsl(228 80% 50%)',
    },
    
    // Status colors (subtiles)
    success: 'hsl(142 76% 36%)',           // Vert émeraude
    warning: 'hsl(38 92% 50%)',            // Ambre
    error: 'hsl(0 72% 51%)',               // Rouge
    info: 'hsl(199 89% 48%)',              // Bleu ciel
    
    // Borders
    border: 'hsl(240 3.7% 15.9%)',         // #27272a
    input: 'hsl(240 3.7% 15.9%)',
    ring: 'hsl(228 94% 67%)',              // Focus ring
  },
  
  spacing: {
    // Design spatial (8px grid)
    base: 8,
    scale: [0, 4, 8, 12, 16, 24, 32, 48, 64, 96, 128],
  },
  
  typography: {
    fontFamily: {
      sans: ['Inter Variable', 'system-ui', 'sans-serif'],
      mono: ['Geist Mono', 'Consolas', 'monospace'],
    },
    fontSize: {
      xs: '0.75rem',      // 12px
      sm: '0.875rem',     // 14px
      base: '1rem',       // 16px
      lg: '1.125rem',     // 18px
      xl: '1.25rem',      // 20px
      '2xl': '1.5rem',    // 24px
      '3xl': '1.875rem',  // 30px
      '4xl': '2.25rem',   // 36px
    },
    lineHeight: {
      tight: 1.25,
      normal: 1.5,
      relaxed: 1.75,
    },
  },
  
  borderRadius: {
    sm: '0.25rem',   // 4px
    md: '0.5rem',    // 8px
    lg: '0.75rem',   // 12px
    xl: '1rem',      // 16px
    '2xl': '1.5rem', // 24px
    full: '9999px',
  },
  
  shadows: {
    // Ombres subtiles pour depth
    sm: '0 1px 2px 0 rgb(0 0 0 / 0.15)',
    md: '0 4px 6px -1px rgb(0 0 0 / 0.2), 0 2px 4px -2px rgb(0 0 0 / 0.2)',
    lg: '0 10px 15px -3px rgb(0 0 0 / 0.3), 0 4px 6px -4px rgb(0 0 0 / 0.3)',
    xl: '0 20px 25px -5px rgb(0 0 0 / 0.4), 0 8px 10px -6px rgb(0 0 0 / 0.4)',
  },
  
  animations: {
    // Durées standards
    fast: '150ms',
    base: '250ms',
    slow: '350ms',
    slower: '500ms',
    
    // Easing curves
    easeInOut: 'cubic-bezier(0.4, 0, 0.2, 1)',
    easeOut: 'cubic-bezier(0, 0, 0.2, 1)',
    easeIn: 'cubic-bezier(0.4, 0, 1, 1)',
    spring: 'cubic-bezier(0.34, 1.56, 0.64, 1)',
  },
}
```

### Composants shadcn-vue personnalisés

```vue
<!-- Button.vue - Exemple de variants -->
<script setup lang="ts">
import { computed } from 'vue'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const buttonVariants = cva(
  // Base styles
  'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
  {
    variants: {
      variant: {
        default: 'bg-primary text-primary-foreground hover:bg-primary/90',
        destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
        outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
        secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
        ghost: 'hover:bg-accent hover:text-accent-foreground',
        link: 'text-primary underline-offset-4 hover:underline',
      },
      size: {
        default: 'h-10 px-4 py-2',
        sm: 'h-8 rounded-md px-3 text-sm',
        lg: 'h-12 rounded-lg px-8',
        icon: 'h-10 w-10',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  }
)

type ButtonVariants = VariantProps<typeof buttonVariants>

interface Props {
  variant?: ButtonVariants['variant']
  size?: ButtonVariants['size']
  as?: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size: 'default',
  as: 'button',
})

const classes = computed(() => cn(buttonVariants({ variant: props.variant, size: props.size }), props.class))
</script>

<template>
  <component :is="as" :class="classes">
    <slot />
  </component>
</template>
```

---

## 🎭 UX/UI Patterns Modernes

### 1. Command Palette (⌘K)
```typescript
// Inspiré de Linear, Vercel, GitHub
// Accès rapide à toutes les actions depuis n'importe où

Features:
- Fuzzy search sur candidatures, offres, entretiens
- Actions rapides: créer candidature, ajouter note, changer statut
- Navigation: aller à dashboard, calendrier, emails
- Raccourcis clavier: ⌘K, ⌘P, ⌘B
- Recent history
- Keyboard navigation (↑↓ pour naviguer, Enter pour sélectionner)
```

### 2. Empty States avec illustrations
```vue
<!-- EmptyState.vue -->
<template>
  <div class="flex flex-col items-center justify-center py-16 text-center">
    <!-- Illustration SVG ou Lucide icon -->
    <div class="mb-4 rounded-full bg-muted p-6">
      <Briefcase class="h-8 w-8 text-muted-foreground" />
    </div>
    
    <h3 class="mb-2 text-lg font-semibold">{{ title }}</h3>
    <p class="mb-6 max-w-sm text-sm text-muted-foreground">
      {{ description }}
    </p>
    
    <Button @click="onAction">
      <Plus class="mr-2 h-4 w-4" />
      {{ actionLabel }}
    </Button>
  </div>
</template>
```

### 3. Skeleton Loading States
```vue
<!-- Plutôt que spinners, skeleton screens pour meilleur perceived performance -->
<template>
  <div class="space-y-4">
    <Skeleton class="h-12 w-full" />
    <Skeleton class="h-32 w-full" />
    <Skeleton class="h-24 w-3/4" />
  </div>
</template>
```

### 4. Micro-interactions
```typescript
// Animations subtiles sur interactions utilisateur
import { useMotion } from '@vueuse/motion'

// Hover card scale
const { variant } = useMotion(target, {
  initial: { scale: 1 },
  enter: { scale: 1.02, transition: { duration: 150 } },
})

// Success animation
const animateSuccess = () => {
  confetti({
    particleCount: 50,
    spread: 60,
    origin: { y: 0.6 }
  })
}
```

### 5. Toast Notifications améliorées
```typescript
// Sonner-like toasts avec actions et icônes
toast.success('Candidature créée', {
  description: 'Vous avez postulé chez Acme Corp',
  action: {
    label: 'Voir',
    onClick: () => router.push(`/applications/${id}`)
  },
  icon: '✓'
})
```

### 6. Inline Editing
```vue
<!-- Double-click pour éditer, auto-save on blur -->
<template>
  <div
    v-if="!isEditing"
    @dblclick="startEdit"
    class="cursor-pointer rounded px-2 py-1 hover:bg-muted"
  >
    {{ value || 'Cliquez pour ajouter' }}
  </div>
  
  <Input
    v-else
    v-model="localValue"
    @blur="save"
    @keydown.enter="save"
    @keydown.esc="cancel"
    ref="inputRef"
  />
</template>
```

### 7. Progressive Disclosure
```typescript
// Montrer progressivement les options avancées
<Collapsible>
  <CollapsibleTrigger>
    Options avancées
    <ChevronDown class="transition-transform" />
  </CollapsibleTrigger>
  <CollapsibleContent>
    <!-- Advanced filters -->
  </CollapsibleContent>
</Collapsible>
```

---

## 🔄 State Management Architecture

### Pinia Stores avec TypeScript

```typescript
// features/applications/stores/applications.store.ts
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { Application, ApplicationStatus } from '../types/application.types'
import { applicationsApi } from '../api/applications.api'

export const useApplicationsStore = defineStore('applications', () => {
  // State
  const applications = ref<Application[]>([])
  const isLoading = ref(false)
  const error = ref<Error | null>(null)
  const selectedApplicationId = ref<string | null>(null)
  
  // Getters
  const selectedApplication = computed(() =>
    applications.value.find(app => app.id === selectedApplicationId.value)
  )
  
  const applicationsByStatus = computed(() => {
    const grouped = new Map<ApplicationStatus, Application[]>()
    
    applications.value.forEach(app => {
      const existing = grouped.get(app.status) || []
      grouped.set(app.status, [...existing, app])
    })
    
    return grouped
  })
  
  const stats = computed(() => ({
    total: applications.value.length,
    active: applications.value.filter(app => 
      ['postulé', 'relancé', 'entretien'].includes(app.status)
    ).length,
    interviews: applications.value.filter(app => 
      app.status === 'entretien'
    ).length,
  }))
  
  // Actions
  const fetchApplications = async () => {
    try {
      isLoading.value = true
      error.value = null
      const data = await applicationsApi.getAll()
      applications.value = data
    } catch (e) {
      error.value = e as Error
      throw e
    } finally {
      isLoading.value = false
    }
  }
  
  const updateApplicationStatus = async (
    id: string,
    status: ApplicationStatus
  ) => {
    // Optimistic update
    const index = applications.value.findIndex(app => app.id === id)
    if (index !== -1) {
      const oldStatus = applications.value[index].status
      applications.value[index].status = status
      
      try {
        await applicationsApi.updateStatus(id, status)
      } catch (e) {
        // Rollback on error
        applications.value[index].status = oldStatus
        throw e
      }
    }
  }
  
  const createApplication = async (data: CreateApplicationDto) => {
    const newApp = await applicationsApi.create(data)
    applications.value.unshift(newApp)
    return newApp
  }
  
  const selectApplication = (id: string | null) => {
    selectedApplicationId.value = id
  }
  
  // Reset store
  const $reset = () => {
    applications.value = []
    isLoading.value = false
    error.value = null
    selectedApplicationId.value = null
  }
  
  return {
    // State
    applications,
    isLoading,
    error,
    selectedApplicationId,
    
    // Getters
    selectedApplication,
    applicationsByStatus,
    stats,
    
    // Actions
    fetchApplications,
    updateApplicationStatus,
    createApplication,
    selectApplication,
    $reset,
  }
})
```

### Composables pour logique réutilisable

```typescript
// features/applications/composables/useApplications.ts
import { storeToRefs } from 'pinia'
import { useApplicationsStore } from '../stores/applications.store'
import { watchEffect } from 'vue'

export function useApplications() {
  const store = useApplicationsStore()
  const { applications, isLoading, error, stats } = storeToRefs(store)
  
  // Auto-fetch on mount
  watchEffect(() => {
    if (applications.value.length === 0 && !isLoading.value) {
      store.fetchApplications()
    }
  })
  
  return {
    applications,
    isLoading,
    error,
    stats,
    refetch: store.fetchApplications,
  }
}

// Composable pour mutations
export function useApplicationMutations() {
  const store = useApplicationsStore()
  const toast = useToast()
  
  const updateStatus = async (id: string, status: ApplicationStatus) => {
    try {
      await store.updateApplicationStatus(id, status)
      toast.success('Statut mis à jour')
    } catch (e) {
      toast.error('Erreur lors de la mise à jour')
      throw e
    }
  }
  
  const createApplication = async (data: CreateApplicationDto) => {
    try {
      const app = await store.createApplication(data)
      toast.success('Candidature créée', {
        description: `Vous avez postulé chez ${app.company}`,
        action: {
          label: 'Voir',
          onClick: () => router.push(`/applications/${app.id}`)
        }
      })
      return app
    } catch (e) {
      toast.error('Erreur lors de la création')
      throw e
    }
  }
  
  return {
    updateStatus,
    createApplication,
  }
}
```

---

## 🎯 Features Détaillées

### 1. Dashboard (Vue d'ensemble)

**Layout:**
```
┌────────────────────────────────────────────────────────┐
│  Dashboard                                    [⌘K]      │
├────────────────────────────────────────────────────────┤
│                                                         │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐     │
│  │ Total   │ │ Active  │ │Interview│ │ Relance │     │
│  │ 24      │ │ 12      │ │ 3       │ │ 8       │     │
│  │ ↑ 12%   │ │ ↓ 5%    │ │ →       │ │ ↑ 20%   │     │
│  └─────────┘ └─────────┘ └─────────┘ └─────────┘     │
│                                                         │
│  ┌────────────────────────┐ ┌───────────────────────┐ │
│  │ Prochains entretiens   │ │ Actions requises      │ │
│  │ ┌──────────────────┐   │ │ ┌─────────────────┐   │ │
│  │ │ 🏢 Acme Corp     │   │ │ │ ⚡ Relancer ACME │   │ │
│  │ │ Senior Dev       │   │ │ │ 📧 Répondre Beta│   │ │
│  │ │ Demain 14h       │   │ │ │ 📝 Préparer XYZ │   │ │
│  │ └──────────────────┘   │ │ └─────────────────┘   │ │
│  └────────────────────────┘ └───────────────────────┘ │
│                                                         │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Candidatures récentes                             │ │
│  │ [Timeline view avec dernières activités]          │ │
│  └──────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────┘
```

**Composants:**
- `StatsGrid.vue`: 4 cartes de métriques avec trend indicators
- `UpcomingInterviews.vue`: Liste des 3 prochains entretiens
- `QuickActions.vue`: Actions urgentes (relances, réponses)
- `ActivityFeed.vue`: Timeline des dernières activités

### 2. Applications Kanban (Vue Pipeline)

**Layout:**
```
┌────────────────────────────────────────────────────────┐
│  Applications                 [Filtres] [+Nouveau]     │
├────────────────────────────────────────────────────────┤
│                                                         │
│  Wishlist│Postulé │Relancé │Entretien│Refusé │Accepté│
│  (5)     │(8)     │(4)     │(3)      │(2)    │(2)    │
│  ────────┼────────┼────────┼─────────┼───────┼───────│
│  ┌─────┐ │┌─────┐ │┌─────┐ │┌──────┐│       │┌─────┐│
│  │Acme │ ││Beta │ ││Gamma││ │Delta │ │       ││Echo ││
│  │Sr.  │ ││Jr.  │ ││Mid  ││ │Sr.   │ │       ││Lead ││
│  │Paris│ ││Lyon │ ││Remote││ │Nice  │ │       ││Paris││
│  │💼📧 │ ││💼📧 │ ││💼📧││ │💼📅 │ │       ││💼✓ ││
│  └─────┘ │└─────┘ │└─────┘ │└──────┘│       │└─────┘│
│  ┌─────┐ │┌─────┐ │        │        │       │       │
│  │...  │ ││...  │ │        │        │       │       │
│  └─────┘ │└─────┘ │        │        │       │       │
└────────────────────────────────────────────────────────┘
```

**Features:**
- Drag & drop entre colonnes avec animation fluide
- Compteurs par colonne
- Cards avec logo entreprise (API Clearbit/Brandfetch)
- Indicateurs: emails reçus, entretiens planifiés, notes
- Filtres: date, entreprise, localisation, remote
- Recherche globale
- Collapse/expand colonnes
- Vue compacte/étendue

**Interactions:**
- Click sur card → Ouvre drawer latéral avec détails
- Drag card → Animation smooth, preview de drop zone
- Hover card → Elevation subtile + actions rapides
- Long press → Multi-sélection pour actions en batch

### 3. Application Detail Drawer

**Layout:**
```
┌────────────────────────────────────────┐
│  [←] Acme Corp - Senior Developer   [×]│
├────────────────────────────────────────┤
│  📍 Paris, France  •  Remote 2j/semaine│
│  📧 recruiter@acme.com  •  Posté il y a│
│                                  5 jours│
│  ┌──────────────────────────────────┐  │
│  │ Statut: [Postulé ▼]         │  │
│  └──────────────────────────────────┘  │
│                                         │
│  [Actions rapides]                      │
│  ┌──┐ ┌──┐ ┌──┐ ┌──┐                   │
│  │📧│ │📅│ │📝│ │⋯ │                   │
│  └──┘ └──┘ └──┘ └──┘                   │
│                                         │
│  ┌─ Notes ────────────────────────┐    │
│  │ [Rich text editor]             │    │
│  │                                │    │
│  └────────────────────────────────┘    │
│                                         │
│  ┌─ Timeline ─────────────────────┐    │
│  │ ● Candidature envoyée - 5j     │    │
│  │ ● Email reçu - 2j              │    │
│  │   "Bonjour, nous avons bien..."│    │
│  │ ● Statut changé - 1j           │    │
│  └────────────────────────────────┘    │
│                                         │
│  ┌─ Entretiens ───────────────────┐    │
│  │ 📅 15 Mars 2026 - 14h00        │    │
│  │    Entretien technique (Visio) │    │
│  │    [Préparer] [Rejoindre]      │    │
│  └────────────────────────────────┘    │
└────────────────────────────────────────┘
```

**Features:**
- Drawer sliding depuis la droite (1/3 écran)
- Header avec breadcrumb et actions
- Inline editing sur lieu, email (double-click)
- Dropdown statut avec raccourcis clavier
- Actions rapides: envoyer email, planifier entretien, ajouter note
- Timeline inversée (plus récent en haut)
- Email preview dans timeline avec AI summary
- Notes avec markdown support
- Interviews cards avec countdown
- Attachments (CV, lettre de motivation)

### 4. Interviews Calendar

**Layout:**
```
┌────────────────────────────────────────────────────────┐
│  Calendrier              [Semaine▼] [Aujourd'hui]      │
├────────────────────────────────────────────────────────┤
│  Lun 10  Mar 11  Mer 12  Jeu 13  Ven 14  Sam 15  Dim 16│
│  ────────────────────────────────────────────────────  │
│  08:00                                                  │
│  09:00   ┌────────┐                                    │
│  10:00   │ Acme   │                                    │
│  11:00   │ Tech   │    ┌────────┐                      │
│  12:00   └────────┘    │ Beta   │                      │
│  13:00                 │ HR     │                      │
│  14:00                 └────────┘  ┌────────┐          │
│  15:00                             │ Gamma  │          │
│  16:00                             │ Final  │          │
│  17:00                             └────────┘          │
│  18:00                                                  │
└────────────────────────────────────────────────────────┘
```

**Features:**
- Vues: Semaine, Mois, Liste
- Time slots de 30min
- Color coding par type (visio, tel, présentiel)
- Drag & drop pour reschedule
- Click event → Popover avec détails
- Quick add: click sur slot vide
- Sync avec Google Calendar (future)
- Reminders automatiques 24h et 1h avant
- Zoom meeting links avec "Join" button
- Préparation interview accessible depuis event

### 5. Emails Inbox

**Layout (3 colonnes inspiré Gmail/Superhuman):**
```
┌──────┬──────────────┬────────────────────────────────┐
│ 📥   │ Acme Corp    │ Re: Votre candidature          │
│ 📤   │ Subject line │ ┌────────────────────────────┐ │
│ ⭐   │ 2j ago       │ │ From: recruiter@acme.com   │ │
│      │              │ │ To: me                      │ │
│ 🏷️   │ Beta Inc     │ │ Date: 8 Mars 2026          │ │
│ Work │ Welcome!     │ ├────────────────────────────┤ │
│ WIP  │ 1h ago       │ │                            │ │
│      │ ⭐           │ │ Bonjour,                   │ │
│ ────│              │ │                            │ │
│ 📅   │ Gamma Ltd    │ │ Nous avons bien reçu      │ │
│ 14h  │ Interview    │ │ votre candidature...       │ │
│ 15h  │ Tomorrow     │ │                            │ │
│      │              │ │ 💡 AI: Réponse positive,  │ │
│      │              │ │    entretien proposé      │ │
│      │              │ │                            │ │
│      │              │ ├────────────────────────────┤ │
│      │              │ │ [Répondre] [Relancer]     │ │
│      │              │ └────────────────────────────┘ │
└──────┴──────────────┴────────────────────────────────┘
```

**Features:**
- Folders: Inbox, Sent, Starred, Drafts, Trash
- Labels personnalisables avec couleurs
- Search puissante (subject, sender, body)
- AI summary badge sur chaque email
- AI suggested replies (3 options)
- Rich text composer avec templates
- Keyboard shortcuts (Gmail-like)
- Mark as read/unread
- Snooze email
- Archive
- Link to application automatique

### 6. Settings

**Tabs:**
1. **Connexion Mail**
   - IMAP/SMTP config avec test
   - Auto-detection pour Gmail, Outlook, iCloud
   - Sécurité: passwords masqués

2. **Règles de Relance**
   - Table des règles
   - Add/Edit dialog avec formulaire
   - Toggle enable/disable
   - Preview de l'email généré

3. **Préférences**
   - Theme (Dark/Light/System)
   - Langue
   - Notifications
   - Raccourcis clavier

4. **Logs & Sécurité**
   - Activity log
   - AI usage stats
   - Export data (RGPD)
   - Delete account

---

## 🔐 Sécurité & Performance

### Sécurité
```typescript
// JWT token refresh automatique
// HTTP-only cookies si possible (discuter avec backend)
// XSS protection via sanitization
// CSRF tokens
// Rate limiting sur API calls
```

### Performance
```typescript
// Code splitting par route
// Lazy loading des composants lourds
// Virtual scrolling pour listes longues (TanStack Virtual)
// Debounce sur recherche
// Optimistic updates
// Service Worker pour offline support
// Image lazy loading avec IntersectionObserver
```

### Monitoring
```typescript
// Sentry pour error tracking
// PostHog pour analytics
// Web Vitals monitoring
```

---

## 📱 Responsive Design

### Breakpoints
```typescript
mobile: 0-640px      // Stack vertical, drawer plein écran
tablet: 641-1024px   // Sidebar collapsible, 2 colonnes
desktop: 1025px+     // Full layout, 3 colonnes
```

### Mobile-First Adaptations
- Kanban: Scroll horizontal avec snap
- Drawer: Full screen modal
- Sidebar: Bottom navigation bar
- Tables: Cards au lieu de table
- Command Palette: Fullscreen sur mobile

---

## ✅ Conventions & Best Practices

### Naming Conventions

```typescript
// Composants: PascalCase
ApplicationCard.vue
StatusBadge.vue

// Composables: camelCase avec "use" prefix
useApplications.ts
useAuth.ts

// Types: PascalCase avec Type suffix
ApplicationType
CreateApplicationDto

// Constants: SCREAMING_SNAKE_CASE
API_BASE_URL
MAX_UPLOAD_SIZE

// Variables/Functions: camelCase
const applicationData = ...
function fetchApplications() {}

// Store actions: camelCase verbs
fetchApplications()
updateApplicationStatus()

// CSS classes: kebab-case (Tailwind)
<div class="flex items-center gap-4">
```

### Code Organization

```typescript
// 1. Imports groupés et ordonnés
import { ref, computed, watch } from 'vue'  // Vue core
import { storeToRefs } from 'pinia'        // Libraries
import type { Application } from '@/types'  // Types
import { Button } from '@/components/ui'    // Components
import { cn } from '@/lib/utils'            // Utils

// 2. Types/Interfaces en haut
interface Props {
  application: Application
  onUpdate?: (app: Application) => void
}

// 3. Props/Emits
const props = defineProps<Props>()
const emit = defineEmits<{
  update: [application: Application]
}>()

// 4. Composables
const { applications } = useApplications()
const { updateStatus } = useApplicationMutations()

// 5. Reactive state
const isEditing = ref(false)
const localValue = ref(props.application.title)

// 6. Computed properties
const displayName = computed(() => ...)

// 7. Watchers
watch(() => props.application, ...)

// 8. Methods
const handleSave = async () => { ... }

// 9. Lifecycle hooks
onMounted(() => { ... })
```

### TypeScript Best Practices

```typescript
// Typage strict partout
import type { Component } from 'vue'

// Utiliser les types discriminated unions pour status
type ApplicationStatus = 
  | 'wishlist'
  | 'postulé'
  | 'relancé'
  | 'entretien'
  | 'refusé'
  | 'accepté'

// Types pour API responses
interface ApiResponse<T> {
  data: T
  meta: {
    total: number
    page: number
  }
}

// Utility types
type Nullable<T> = T | null
type Optional<T> = T | undefined
type AsyncState<T> = {
  data: T | null
  loading: boolean
  error: Error | null
}

// Generic composable avec types
function useAsyncData<T>(fetcher: () => Promise<T>) {
  const state = ref<AsyncState<T>>({
    data: null,
    loading: false,
    error: null
  })
  
  // ...
  
  return state
}
```

### Composables Patterns

```typescript
// Pattern 1: Single Responsibility
// ✅ Bon
function useApplicationFilters() {
  const searchQuery = ref('')
  const statusFilter = ref<ApplicationStatus | null>(null)
  
  const filteredApplications = computed(() => {
    // filtering logic
  })
  
  return { searchQuery, statusFilter, filteredApplications }
}

// ❌ Mauvais - fait trop de choses
function useApplications() {
  // fetching, filtering, mutations, UI state...
}

// Pattern 2: Return values explicites
// ✅ Bon
return {
  data: applications,
  loading: isLoading,
  error,
  refetch
}

// ❌ Mauvais - retourne tout le store
return store

// Pattern 3: Cleanup dans onUnmounted
function useWebSocket(url: string) {
  const socket = ref<WebSocket>()
  
  onMounted(() => {
    socket.value = new WebSocket(url)
  })
  
  onUnmounted(() => {
    socket.value?.close()
  })
  
  return { socket }
}
```

### Component Patterns

```vue
<!-- Pattern 1: Composition API avec <script setup> -->
<script setup lang="ts">
// Préférer setup sugar plutôt que Options API

// Pattern 2: Props avec defaults
interface Props {
  title: string
  subtitle?: string
  variant?: 'default' | 'outlined'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  subtitle: ''
})

// Pattern 3: Emits typés
const emit = defineEmits<{
  update: [value: string]
  delete: []
}>()

// Pattern 4: Slots typés
defineSlots<{
  default(props: { item: Application }): any
  empty(): any
}>()

// Pattern 5: Expose public API si nécessaire
defineExpose({
  focus: () => inputRef.value?.focus()
})
</script>

<!-- Pattern 6: Template avec v-bind shorthand -->
<template>
  <div :class="cn('base-classes', props.class)">
    <!-- Pattern 7: Conditional rendering -->
    <template v-if="isLoading">
      <Skeleton />
    </template>
    
    <template v-else-if="error">
      <ErrorState :error="error" />
    </template>
    
    <template v-else>
      <!-- Content -->
    </template>
  </div>
</template>
```

### API Layer Patterns

```typescript
// api/client.ts
import { ofetch } from 'ofetch'

export const apiClient = ofetch.create({
  baseURL: import.meta.env.VITE_API_URL,
  
  // Auto-retry avec backoff
  retry: 2,
  retryDelay: 500,
  
  // Timeouts
  timeout: 10000,
  
  // Interceptors
  async onRequest({ options }) {
    const token = localStorage.getItem('auth_token')
    if (token) {
      options.headers = {
        ...options.headers,
        Authorization: `Bearer ${token}`
      }
    }
  },
  
  async onResponseError({ response }) {
    if (response.status === 401) {
      // Redirect to login
      router.push('/auth/login')
    }
  }
})

// api/applications.api.ts
export const applicationsApi = {
  getAll: () => 
    apiClient<Application[]>('/applications.jsonld'),
  
  getById: (id: string) =>
    apiClient<Application>(`/applications/${id}.jsonld`),
  
  create: (data: CreateApplicationDto) =>
    apiClient<Application>('/applications.jsonld', {
      method: 'POST',
      body: data
    }),
  
  update: (id: string, data: Partial<Application>) =>
    apiClient<Application>(`/applications/${id}.jsonld`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/merge-patch+json' },
      body: data
    }),
  
  delete: (id: string) =>
    apiClient(`/applications/${id}`, { method: 'DELETE' })
}
```

### Error Handling

```typescript
// Centralized error handling
class AppError extends Error {
  constructor(
    message: string,
    public code: string,
    public statusCode?: number
  ) {
    super(message)
    this.name = 'AppError'
  }
}

// In composables
const handleError = (error: unknown) => {
  if (error instanceof AppError) {
    toast.error(error.message)
  } else if (error instanceof Error) {
    toast.error('Une erreur est survenue')
    console.error(error)
  } else {
    toast.error('Erreur inconnue')
  }
}

// Try/catch avec feedback utilisateur
const updateApplication = async (id: string, data: Partial<Application>) => {
  try {
    isLoading.value = true
    await applicationsApi.update(id, data)
    toast.success('Candidature mise à jour')
  } catch (error) {
    handleError(error)
  } finally {
    isLoading.value = false
  }
}
```

---

## 🧪 Testing Strategy

### Testing Pyramid

```
         ╱╲
        ╱E2E╲         ← Few (Critical user flows)
       ╱─────╲
      ╱Integ. ╲       ← Some (Component interactions)
     ╱─────────╲
    ╱   Unit    ╲     ← Many (Utils, composables, stores)
   ╱─────────────╲
```

### Unit Tests (Vitest)

```typescript
// lib/utils/__tests__/date.test.ts
import { describe, it, expect } from 'vitest'
import { formatRelativeTime } from '../date'

describe('formatRelativeTime', () => {
  it('formats dates correctly', () => {
    const now = new Date()
    const yesterday = new Date(now.getTime() - 24 * 60 * 60 * 1000)
    
    expect(formatRelativeTime(yesterday)).toBe('il y a 1 jour')
  })
})

// features/applications/composables/__tests__/useApplications.test.ts
import { describe, it, expect, vi } from 'vitest'
import { useApplications } from '../useApplications'
import { setActivePinia, createPinia } from 'pinia'

describe('useApplications', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })
  
  it('fetches applications on mount', async () => {
    const { applications, isLoading } = useApplications()
    
    expect(isLoading.value).toBe(true)
    await flushPromises()
    expect(applications.value.length).toBeGreaterThan(0)
  })
})
```

### Component Tests (Testing Library)

```typescript
// components/ui/button/__tests__/Button.test.ts
import { render, screen, fireEvent } from '@testing-library/vue'
import { describe, it, expect, vi } from 'vitest'
import Button from '../Button.vue'

describe('Button', () => {
  it('renders with correct text', () => {
    render(Button, {
      slots: {
        default: 'Click me'
      }
    })
    
    expect(screen.getByText('Click me')).toBeInTheDocument()
  })
  
  it('calls onClick when clicked', async () => {
    const onClick = vi.fn()
    render(Button, {
      props: { onClick }
    })
    
    await fireEvent.click(screen.getByRole('button'))
    expect(onClick).toHaveBeenCalledOnce()
  })
  
  it('is disabled when disabled prop is true', () => {
    render(Button, {
      props: { disabled: true }
    })
    
    expect(screen.getByRole('button')).toBeDisabled()
  })
})
```

### E2E Tests (Playwright)

```typescript
// e2e/applications.spec.ts
import { test, expect } from '@playwright/test'

test.describe('Applications Flow', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/auth/login')
    await page.fill('[name="email"]', 'test@example.com')
    await page.fill('[name="password"]', 'password')
    await page.click('button[type="submit"]')
    await expect(page).toHaveURL('/dashboard')
  })
  
  test('can create new application', async ({ page }) => {
    await page.click('text=Applications')
    await page.click('text=Nouveau')
    
    await page.fill('[name="company"]', 'Acme Corp')
    await page.fill('[name="position"]', 'Senior Developer')
    await page.click('button:has-text("Créer")')
    
    await expect(page.locator('text=Acme Corp')).toBeVisible()
  })
  
  test('can drag and drop application', async ({ page }) => {
    await page.goto('/applications')
    
    const card = page.locator('[data-testid="application-card"]').first()
    const targetColumn = page.locator('[data-status="postulé"]')
    
    await card.dragTo(targetColumn)
    
    await expect(page.locator('text=Statut mis à jour')).toBeVisible()
  })
})
```

---

## 🚀 Roadmap d'Implémentation

### Phase 1 : Fondations (Semaine 1-2)
```
✓ Setup projet (Vite + Vue 3 + TypeScript)
✓ Configuration Tailwind + shadcn-vue
✓ Structure des dossiers
✓ Design tokens et theme
✓ Composants UI de base (Button, Input, Card, etc.)
✓ HTTP client et API layer
✓ Router avec guards
✓ Auth store et login/register
```

### Phase 2 : Features Core (Semaine 3-5)
```
✓ Dashboard avec widgets
✓ Applications Store + API
✓ Kanban Board avec drag & drop
✓ Application Detail Drawer
✓ Job Offers view
✓ Forms avec validation (VeeValidate + Zod)
```

### Phase 3 : Features Avancées (Semaine 6-8)
```
✓ Calendar view (interviews)
✓ Email inbox
✓ Email composer avec rich text
✓ Settings (mail config, follow-up rules)
✓ Command Palette (⌘K)
```

### Phase 4 : Polish & Optimisation (Semaine 9-10)
```
✓ Animations et micro-interactions
✓ Loading states et skeletons
✓ Error boundaries
✓ Responsive design mobile
✓ Accessibility (ARIA labels, keyboard nav)
✓ Performance optimization
✓ Tests (unit + e2e)
```

### Phase 5 : Déploiement (Semaine 11)
```
✓ CI/CD pipeline
✓ Monitoring (Sentry, PostHog)
✓ Documentation
✓ Deployment (Vercel/Netlify)
```

---

## 📦 Package.json Structure

```json
{
  "name": "jobplanner-frontend",
  "version": "2.0.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vue-tsc && vite build",
    "preview": "vite preview",
    "test": "vitest",
    "test:e2e": "playwright test",
    "test:ui": "vitest --ui",
    "lint": "eslint . --ext .vue,.ts,.tsx --fix",
    "format": "prettier --write \"src/**/*.{vue,ts,tsx,css}\"",
    "type-check": "vue-tsc --noEmit"
  },
  "dependencies": {
    "vue": "^3.4.34",
    "vue-router": "^4.4.0",
    "pinia": "^2.2.0",
    "radix-vue": "^1.9.0",
    "class-variance-authority": "^0.7.0",
    "clsx": "^2.1.1",
    "tailwind-merge": "^2.4.0",
    "lucide-vue-next": "^0.400.0",
    "@vueuse/core": "^10.11.0",
    "@vueuse/motion": "^2.2.0",
    "ofetch": "^1.3.4",
    "date-fns": "^4.0.0",
    "zod": "^3.23.0",
    "vee-validate": "^4.13.0",
    "@tiptap/vue-3": "^3.19.0",
    "@tiptap/starter-kit": "^3.19.0",
    "@schedule-x/vue": "^2.0.0",
    "vaul-vue": "^0.2.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.1.0",
    "vite": "^6.0.0",
    "typescript": "^5.6.0",
    "vue-tsc": "^2.0.0",
    "tailwindcss": "^4.1.17",
    "autoprefixer": "^10.4.20",
    "postcss": "^8.4.41",
    "@types/node": "^22.0.0",
    "vitest": "^2.0.5",
    "@testing-library/vue": "^8.1.0",
    "@playwright/test": "^1.45.0",
    "eslint": "^9.7.0",
    "eslint-plugin-vue": "^9.27.0",
    "@typescript-eslint/eslint-plugin": "^7.17.0",
    "@typescript-eslint/parser": "^7.17.0",
    "prettier": "^3.3.3",
    "prettier-plugin-tailwindcss": "^0.6.5"
  }
}
```

---

## 🎨 Design Inspiration References

### Applications similaires à étudier
1. **Linear** - Issue tracking avec excellent UX
2. **Notion** - Flexible workspace avec bonne hiérarchie visuelle
3. **Superhuman** - Email client avec keyboard shortcuts
4. **Raycast** - Command palette et micro-interactions
5. **Arc Browser** - Spatial design et animations
6. **Vercel Dashboard** - Clean design moderne
7. **Stripe Dashboard** - Data visualization
8. **Attio** - CRM avec beau design

### Design systems à référencer
- **shadcn/ui** - Composants et patterns
- **Tailwind UI** - Components premium
- **Radix Themes** - Design tokens
- **Untitled UI** - Figma components

---

## 📝 Notes Importantes

### Migration depuis ancien frontend
```typescript
// Mapping des concepts
Ancien               → Nouveau
─────────────────────────────────
PrimeVue             → shadcn-vue
Options API          → Composition API
JavaScript           → TypeScript
Axios                → ofetch
No state management  → Pinia
Manual forms         → VeeValidate + Zod
FullCalendar         → @schedule-x/vue
Inline styles        → Tailwind utilities
```

### Performance Targets
```
First Contentful Paint: < 1.5s
Time to Interactive: < 3s
Lighthouse Score: > 95
Bundle size: < 200kb (gzipped)
```

### Accessibility Goals
```
WCAG 2.1 Level AA compliance
Keyboard navigation complète
Screen reader support
Focus management
Color contrast ratios > 4.5:1
```

---

## ✅ Checklist de Démarrage

- [ ] Initialiser projet Vite + Vue 3 + TypeScript
- [ ] Setup Tailwind CSS 4
- [ ] Installer shadcn-vue CLI
- [ ] Configurer ESLint + Prettier
- [ ] Setup Pinia
- [ ] Configurer Vue Router
- [ ] Setup API client (ofetch)
- [ ] Créer design tokens
- [ ] Implémenter composants UI de base
- [ ] Setup auth store et guards
- [ ] Créer layout de base (AppShell)
- [ ] Implémenter page de login
- [ ] Tests de connexion avec API

---

## 🎯 Conclusion

Ce plan fournit une architecture **scalable**, **maintenable** et **moderne** pour le frontend JobPlanner. L'utilisation de **shadcn-vue** avec **Radix Vue** garantit des composants accessibles et réutilisables. La structure **feature-based** facilite la collaboration et l'évolution du code.

Les **principes SOLID** sont appliqués via:
- **S**: Composants et composables à responsabilité unique
- **O**: Extensions via composables, pas de modifications
- **L**: Interfaces communes pour composants
- **I**: Composables spécialisés
- **D**: Injection de dépendances via provide/inject

Le design **dark-first** inspiré de Linear et Raycast offrira une **expérience utilisateur moderne et fluide** avec des **micro-interactions soignées**.

**Ready to build! 🚀**
