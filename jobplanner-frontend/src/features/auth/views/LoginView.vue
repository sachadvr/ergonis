<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.store'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardHeader, CardContent, CardTitle } from '@/components/ui/card'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const isLoading = ref(false)

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
            <div class="rounded-[1.5rem] bg-secondary/70 p-4 text-sm text-muted-foreground">Track your pipeline</div>
            <div class="rounded-[1.5rem] bg-secondary/70 p-4 text-sm text-muted-foreground">Review recruiter mails</div>
            <div class="rounded-[1.5rem] bg-secondary/70 p-4 text-sm text-muted-foreground">Stay ready for interviews</div>
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

          <Button type="submit" :disabled="isLoading" class="w-full">
            {{ isLoading ? 'Signing in...' : 'Sign in' }}
          </Button>
        </form>
      </CardContent>
      </Card>
    </div>
  </div>
</template>
