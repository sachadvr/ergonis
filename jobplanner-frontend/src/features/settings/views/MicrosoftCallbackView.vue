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
const errorDetails = ref<string | null>(null)

onMounted(async () => {
  const code = route.query.code as string
  if (!code) {
    error.value = 'No authorization code received from Microsoft.'
    return
  }

  try {
    const result = await settingsApi.confirmMicrosoftAuth(code)
    if (result.success) {
      if (result.token && result.user) {
        await authStore.completeSocialLogin(result.token, result.user)
        router.push({ name: 'Dashboard' })
      } else {
        router.push({ name: 'Settings', query: { microsoft_connected: 'true' } })
      }
    } else {
      error.value = 'Failed to connect Microsoft account.'
    }
  } catch (e: any) {
    const data = e?.data || e?.response?.data || null
    error.value = data?.error_description || data?.error || e.message || 'An error occurred during authentication.'
    errorDetails.value = data ? JSON.stringify(data, null, 2) : null
  }
})
</script>

<template>
  <div class="flex min-h-[60vh] items-center justify-center p-4">
    <Card class="w-full max-w-md text-center">
      <CardHeader>
        <CardTitle>Microsoft Authentication</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4 py-8">
        <div v-if="!error" class="flex flex-col items-center gap-4">
          <Loader2 class="h-12 w-12 animate-spin text-blue-600" />
          <p class="text-sm text-muted-foreground">Connecting your Outlook account, please wait...</p>
        </div>
        <div v-else class="space-y-4">
          <div class="rounded-lg bg-destructive/10 p-4 text-sm text-destructive">
            {{ error }}
          </div>
          <pre v-if="errorDetails" class="overflow-auto rounded-lg bg-muted p-4 text-left text-xs">{{ errorDetails }}</pre>
          <router-link to="/settings" class="inline-block text-sm font-medium text-blue-600 hover:underline">
            Back to settings
          </router-link>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
