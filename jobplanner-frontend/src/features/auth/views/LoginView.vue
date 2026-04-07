<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.store'
import { Button } from '@/components/ui/button'
import { settingsApi } from '@/features/settings/api/settings.api'
import { Input } from '@/components/ui/input'
import { Card, CardHeader, CardContent, CardTitle } from '@/components/ui/card'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const isLoading = ref(false)
const isConnectingSocial = ref(false)

const handleLogin = async () => {
  try {
    error.value = ''
    isLoading.value = true
    await authStore.login(email.value, password.value)
    router.push({ name: 'Dashboard' })
  } catch (e) {
    error.value = 'Invalid email or password'
  } finally {
    isLoading.value = false
  }
}

const handleGoogleLogin = async () => {
  isConnectingSocial.value = true
  try {
    const { url } = await settingsApi.getGoogleAuthUrl()
    window.location.href = url
  } catch (e) {
    error.value = 'Failed to get Google login URL'
  } finally {
    isConnectingSocial.value = false
  }
}

const handleMicrosoftLogin = async () => {
  isConnectingSocial.value = true
  try {
    const { url } = await settingsApi.getMicrosoftAuthUrl()
    window.location.href = url
  } catch (e) {
    error.value = 'Failed to get Microsoft login URL'
  } finally {
    isConnectingSocial.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-background p-4">
    <div class="grid w-full max-w-6xl gap-6 lg:grid-cols-[1.05fr_0.95fr]">
      <Card class="hidden min-h-[640px] overflow-hidden lg:block">
        <CardContent class="flex h-full flex-col justify-between p-10">
          <div>
            <p class="section-kicker mb-4">Career Atelier</p>
            <h1 class="display-title max-w-xl text-6xl font-semibold leading-none">A more elegant way to run your search.</h1>
            <p class="mt-6 max-w-lg text-base leading-7 text-muted-foreground">
              Track applications, recruiter emails, interviews, and follow-ups inside one calm workspace.
            </p>
          </div>
          <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-[1.5rem] bg-primary-light p-4 text-sm text-muted-foreground">Track your pipeline</div>
            <div class="rounded-[1.5rem] bg-secondary/70 p-4 text-sm text-muted-foreground">Review recruiter mails</div>
            <div class="rounded-[1.5rem] bg-accent/70 p-4 text-sm text-muted-foreground">Stay ready for interviews</div>
          </div>
        </CardContent>
      </Card>

      <Card class="mx-auto w-full max-w-md">
      <CardHeader class="space-y-3 text-center">
        <p class="section-kicker">Welcome back</p>
        <CardTitle class="display-title text-4xl">JobPlanner</CardTitle>
        <p class="text-sm text-muted-foreground">Sign in to continue your search</p>
      </CardHeader>

      <CardContent>
        <form class="space-y-6" @submit.prevent="handleLogin">
          <div class="space-y-2">
            <label for="email" class="block text-sm font-medium">Email</label>
            <Input
              id="email"
              v-model="email"
              type="email"
              placeholder="you@example.com"
              required
            />
          </div>

          <div class="space-y-2">
            <label for="password" class="block text-sm font-medium">Password</label>
            <Input
              id="password"
              v-model="password"
              type="password"
              placeholder="Enter your password"
              required
            />
          </div>

          <div v-if="error" class="rounded-lg bg-destructive/10 p-3 text-sm text-destructive">
            {{ error }}
          </div>

          <Button type="submit" :disabled="isLoading || isConnectingSocial" class="w-full">
            {{ isLoading ? 'Signing in...' : 'Sign in' }}
          </Button>

          <div class="relative py-4">
            <div class="absolute inset-0 flex items-center">
              <span class="w-full border-t border-border"></span>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
              <span class="bg-card px-2 text-muted-foreground">Or continue with</span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <Button variant="outline" type="button" @click="handleGoogleLogin" :disabled="isLoading || isConnectingSocial">
              <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24">
                <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" />
                <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
              </svg>
              Google
            </Button>
            <Button variant="outline" type="button" @click="handleMicrosoftLogin" :disabled="isLoading || isConnectingSocial">
              <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24">
                <path fill="#f35325" d="M1 1h10v10H1z" />
                <path fill="#81bc06" d="M13 1h10v10H13z" />
                <path fill="#05a6f0" d="M1 13h10v10H1z" />
                <path fill="#ffba08" d="M13 13h10v10H13z" />
              </svg>
              Microsoft
            </Button>
          </div>
        </form>
      </CardContent>
      </Card>
    </div>
  </div>
</template>
