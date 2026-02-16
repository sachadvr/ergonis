<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { settingsApi } from '../api/settings.api'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuthStore } from '@/features/auth/stores/auth.store'
import { Loader2 } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const error = ref<string | null>(null)

onMounted(async () => {
  const code = route.query.code as string
  if (!code) {
    error.value = 'No authorization code received from Google.'
    return
  }

  try {
    const result = await settingsApi.confirmGoogleAuth(code)
    if (result.success) {
      if (result.token && result.user) {
        // This is a login flow
        await authStore.completeSocialLogin(result.token, result.user)
        router.push({ name: 'Dashboard' })
      } else {
        // This is just connecting a mailbox to existing user
        router.push({ name: 'Settings', query: { gmail_connected: 'true' } })
      }
    } else {
      error.value = 'Failed to connect Google account.'
    }
  } catch (e: any) {
    error.value = e.message || 'An error occurred during authentication.'
  }
})
</script>

<template>
  <div class="flex min-h-[60vh] items-center justify-center p-4">
    <Card class="w-full max-w-md text-center">
      <CardHeader>
        <CardTitle>Google Authentication</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4 py-8">
        <div v-if="!error" class="flex flex-col items-center gap-4">
          <Loader2 class="h-12 w-12 animate-spin text-emerald-600" />
          <p class="text-sm text-muted-foreground">Connecting your Gmail account, please wait...</p>
        </div>
        <div v-else class="space-y-4">
          <div class="rounded-lg bg-destructive/10 p-4 text-sm text-destructive">
            {{ error }}
          </div>
          <router-link to="/settings" class="inline-block text-sm font-medium text-emerald-600 hover:underline">
            Back to settings
          </router-link>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
