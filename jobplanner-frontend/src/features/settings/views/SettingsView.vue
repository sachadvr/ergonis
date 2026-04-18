<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { settingsApi } from '../api/settings.api'
import { storeToRefs } from 'pinia'
import { Server, Inbox, Send, WandSparkles, Save, PlugZap } from 'lucide-vue-next'
import { useSettingsStore } from '../stores/settings.store'
import { detectMailConfig } from '../utils/mail-detect'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Loader2 } from 'lucide-vue-next'
import { Select, type SelectOption } from '@/components/ui/select'
import { FOLLOW_UP_TEMPLATE_TYPES } from '@/lib/constants/app.constants'
import type { FollowUpRule, UserMailboxSettings } from '@/types/models.types'
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'

const route = useRoute()
const settingsStore = useSettingsStore()
const { mailboxSettings, followUpRules, isLoading, error } = storeToRefs(settingsStore)

const mailboxForm = ref<Partial<UserMailboxSettings>>({})
const isSavingMailbox = ref(false)
const isTestingMailbox = ref(false)
const mailboxTestResult = ref<{ success: boolean; message: string } | null>(null)
const savingRuleId = ref<number | null>(null)
const successMessage = ref('')
const isConnectingGoogle = ref(false)
const isConnectingMicrosoft = ref(false)
const showManualForm = ref(false)

const handleGoogleConnect = async () => {
  isConnectingGoogle.value = true
  try {
    const { url } = await settingsApi.getGoogleAuthUrl()
    window.location.href = url
  } catch (e) {
    console.error('Failed to get Google auth URL', e)
  } finally {
    isConnectingGoogle.value = false
  }
}

const handleMicrosoftConnect = async () => {
  isConnectingMicrosoft.value = true
  try {
    const { url } = await settingsApi.getMicrosoftAuthUrl()
    window.location.href = url
  } catch (e) {
    console.error('Failed to get Microsoft auth URL', e)
  } finally {
    isConnectingMicrosoft.value = false
  }
}

const encryptionOptions: SelectOption[] = [
  { value: 'ssl', label: 'SSL' },
  { value: 'tls', label: 'TLS' },
  { value: 'none', label: 'None' },
]

const templateOptions: SelectOption[] = FOLLOW_UP_TEMPLATE_TYPES.map((type) => ({
  value: type,
  label: type.replace('_', ' '),
}))

watch(mailboxSettings, (values) => {
  const mailbox = values[0]
  mailboxForm.value = mailbox
    ? { ...mailbox }
    : {
        imapHost: '',
        imapPort: 993,
        imapEncryption: 'ssl',
        imapUser: '',
        imapPassword: '',
        imapFolder: 'INBOX',
        smtpHost: '',
        smtpPort: 587,
        smtpEncryption: 'tls',
        smtpUser: '',
        smtpPassword: '',
        isActive: true,
      }
}, { immediate: true })

watch(() => mailboxForm.value.imapUser, (newEmail) => {
  if (!newEmail || !newEmail.includes('@')) return
  
  if (mailboxForm.value.imapHost && !mailboxForm.value.imapHost.includes('example.com')) return

  const config = detectMailConfig(newEmail)
  if (config) {
    Object.assign(mailboxForm.value, config)
    if (!mailboxForm.value.smtpUser) {
        mailboxForm.value.smtpUser = newEmail
    }
  }
})

const handleMailboxSave = async () => {
  isSavingMailbox.value = true
  try {
    await settingsStore.saveMailboxSettings(mailboxForm.value)
    successMessage.value = 'Mailbox settings saved.'
  } finally {
    isSavingMailbox.value = false
  }
}

const handleMailboxTest = async () => {
  isTestingMailbox.value = true
  mailboxTestResult.value = null
  successMessage.value = ''
  try {
    await settingsStore.saveMailboxSettings(mailboxForm.value)
    const result = await settingsStore.testMailboxConnection()
    mailboxTestResult.value = result
    console.log('Test result:', result)
  } catch (e: unknown) {
    console.error('Test error:', e)
    const err = e as { success?: boolean; message?: string }
    mailboxTestResult.value = { 
      success: err.success ?? false, 
      message: err.message ?? 'Failed to test connection' 
    }
  } finally {
    isTestingMailbox.value = false
  }
}

const handleRuleSave = async (rule: FollowUpRule) => {
  savingRuleId.value = rule.id
  try {
    await settingsStore.saveFollowUpRule(rule.id, {
      daysWithoutReply: rule.daysWithoutReply,
      templateType: rule.templateType,
      enabled: rule.enabled,
    })
    successMessage.value = 'Follow-up rule saved.'
  } finally {
    savingRuleId.value = null
  }
}

onMounted(() => {
  settingsStore.fetchSettings()
  if (route.query.gmail_connected) {
    successMessage.value = 'Your Gmail account has been connected successfully!'
  }
  if (route.query.microsoft_connected) {
    successMessage.value = 'Your Outlook account has been connected successfully!'
  }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="section-kicker mb-3">System controls</p>
        <h1 class="display-title text-4xl font-semibold">Settings</h1>
        <p class="mt-2 text-muted-foreground">Operational configuration, mailboxes, and follow-up automation.</p>
      </div>
      <Button variant="outline" :disabled="isLoading" @click="settingsStore.fetchSettings()">
        Refresh
      </Button>
    </div>

    <div v-if="error" class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
      {{ error }}
    </div>

    <div v-if="successMessage" class="rounded-lg border border-emerald-700/20 bg-emerald-700/8 p-4 text-sm text-emerald-900">
      {{ successMessage }}
    </div>

    <div v-if="isLoading" class="grid gap-4 lg:grid-cols-3">
      <Card v-for="i in 3" :key="i" class="overflow-hidden">
        <CardHeader class="pb-2">
          <Skeleton class="h-6 w-3/4" />
        </CardHeader>
        <CardContent class="space-y-4">
          <Skeleton class="h-4 w-full" />
          <Skeleton class="h-4 w-5/6" />
          <Skeleton class="h-4 w-2/3" />
        </CardContent>
      </Card>
    </div>

    <div v-else class="grid gap-4 lg:grid-cols-3">
      <Card class="lg:col-span-3">
        <CardHeader>
          <CardTitle class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2"><Inbox :size="18" /> Mailbox Settings</div>
            <Button v-if="showManualForm" size="sm" variant="ghost" class="h-8 text-xs underline" @click="showManualForm = false">
                Back to options
            </Button>
          </CardTitle>
        </CardHeader>
        
        <div v-if="mailboxSettings[0]?.oauthProvider === 'google' || mailboxSettings[0]?.oauthProvider === 'microsoft'" class="space-y-6 p-10 text-center">
            <div class="mx-auto inline-flex h-20 w-20 items-center justify-center rounded-full bg-blue-50 text-blue-600 shadow-sm">
                <svg v-if="mailboxSettings[0]?.oauthProvider === 'google'" class="h-10 w-10" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                  <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                  <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" />
                  <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                </svg>
                <svg v-else class="h-10 w-10" viewBox="0 0 24 24">
                  <path fill="#f35325" d="M1 1h10v10H1z" />
                  <path fill="#81bc06" d="M13 1h10v10H13z" />
                  <path fill="#05a6f0" d="M1 13h10v10H1z" />
                  <path fill="#ffba08" d="M13 13h10v10H13z" />
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-semibold">{{ String(mailboxSettings[0]?.oauthProvider ?? '') === 'google' ? 'Gmail' : 'Outlook' }} account synchronized</h3>
                <p class="mt-1 text-muted-foreground">Your application emails are automatically read from:</p>
                <div class="mt-2 text-lg font-medium text-blue-600">{{ mailboxSettings[0].imapUser }}</div>
            </div>
            <div class="flex justify-center gap-4 pt-2">
                <Button variant="outline" @click="handleMailboxTest" :disabled="isTestingMailbox" class="gap-2">
                    <PlugZap :size="18" />
                    {{ isTestingMailbox ? 'Test in progress...' : 'Test connection' }}
                </Button>
                <Button variant="ghost" class="text-destructive hover:bg-destructive/10 hover:text-destructive" @click="mailboxForm = { imapHost: '', imapPort: 993, imapEncryption: 'ssl', imapUser: '', imapPassword: '', imapFolder: 'INBOX', isActive: false, smtpHost: '', smtpPort: 587, smtpEncryption: 'tls', smtpUser: '', smtpPassword: '', oauthProvider: null }; handleMailboxSave()">
                    Disconnect {{ String(mailboxSettings[0]?.oauthProvider ?? '') === 'google' ? 'Gmail' : 'Outlook' }}
                </Button>
            </div>
        </div>
        
        <div v-else-if="!showManualForm" class="grid gap-6 p-8 md:grid-cols-2">
            <button 
                class="group relative flex flex-col items-center justify-center rounded-xl border-2 border-border p-8 transition-all hover:border-blue-500 hover:bg-blue-50/50"
                :class="String(mailboxSettings[0]?.oauthProvider ?? '') === 'google' ? 'border-blue-500' : 'border-border'"
                :disabled="isConnectingGoogle"
                @click="handleGoogleConnect"
            >
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 text-blue-600 transition-transform group-hover:scale-110">
                    <svg class="h-8 w-8" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                        <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                        <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" />
                        <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                    </svg>
                </div>
                <h4 class="text-lg font-bold font-heading">Google (Gmail)</h4>
                <p class="mt-2 text-center text-sm text-muted-foreground">Recommended. Secure connection in one click.</p>
                <div v-if="isConnectingGoogle" class="absolute inset-0 flex items-center justify-center rounded-xl bg-white/60">
                    <Loader2 class="h-8 w-8 animate-spin text-blue-600" />
                </div>
            </button>

            <button 
                class="group relative flex flex-col items-center justify-center rounded-xl border-2 border-border p-8 transition-all hover:border-blue-700 hover:bg-blue-50/50"
                :class="String(mailboxSettings[0]?.oauthProvider ?? '') === 'microsoft' ? 'border-blue-700' : 'border-border'"
                :disabled="isConnectingMicrosoft"
                @click="handleMicrosoftConnect"
            >
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 text-blue-700 transition-transform group-hover:scale-110">
                    <svg class="h-8 w-8" viewBox="0 0 24 24">
                        <path fill="#f35325" d="M1 1h10v10H1z" />
                        <path fill="#81bc06" d="M13 1h10v10H13z" />
                        <path fill="#05a6f0" d="M1 13h10v10H1z" />
                        <path fill="#ffba08" d="M13 13h10v10H13z" />
                    </svg>
                </div>
                <h4 class="text-lg font-bold font-heading">Microsoft (Outlook)</h4>
                <p class="mt-2 text-center text-sm text-muted-foreground">Enterprise or Personal. Connection via Office 365.</p>
                <div v-if="isConnectingMicrosoft" class="absolute inset-0 flex items-center justify-center rounded-xl bg-white/60">
                    <Loader2 class="h-8 w-8 animate-spin text-blue-700" />
                </div>
            </button>

            <button 
                class="group flex flex-col items-center justify-center rounded-xl border-2 border-border p-8 transition-all hover:border-emerald-500 hover:bg-emerald-50/50"
                :class="!String(mailboxSettings[0]?.oauthProvider ?? '') ? 'border-emerald-500' : 'border-border'"
                @click="showManualForm = true"
            >
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 transition-transform group-hover:scale-110">
                    <Server :size="32" />
                </div>
                <h4 class="text-lg font-bold font-heading">Manual Configuration</h4>
                <p class="mt-2 text-center text-sm text-muted-foreground">Custom servers. For experts or specific configurations.</p>
            </button>
        </div>

        <form v-else @submit.prevent>
          <CardContent class="space-y-5 text-sm">
            <h1 class="text-2xl font-bold font-heading mb-2">Manual configuration</h1>
            <div class="border p-4">
              <h2 class="text-lg font-bold font-heading mb-2">Retrieve emails (IMAP)</h2>
              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <label class="text-sm font-medium">IMAP host</label>
                  <Input v-model="mailboxForm.imapHost" />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">IMAP user</label>
                  <Input v-model="mailboxForm.imapUser" />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">IMAP password</label>
                  <Input v-model="mailboxForm.imapPassword" type="password" />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">IMAP folder</label>
                  <Input v-model="mailboxForm.imapFolder" />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">IMAP port</label>
                  <Input v-model.number="mailboxForm.imapPort" type="number" />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">IMAP encryption</label>
                  <Select v-model="mailboxForm.imapEncryption" :options="encryptionOptions" />
                </div>
              </div>
            </div>
            <div class="border p-4">
            <h2 class="text-lg font-bold font-heading mb-2">Retrieve emails (IMAP)</h2>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <label class="text-sm font-medium">SMTP host</label>
                <Input v-model="mailboxForm.smtpHost" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">SMTP user</label>
                <Input v-model="mailboxForm.smtpUser" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">SMTP password</label>
                <Input v-model="mailboxForm.smtpPassword" type="password" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">SMTP port</label>
                <Input v-model.number="mailboxForm.smtpPort" type="number" />
              </div>
              <div class="space-y-2 md:col-span-2">
                <label class="text-sm font-medium">SMTP encryption</label>
                <Select v-model="mailboxForm.smtpEncryption" :options="encryptionOptions" />
              </div>
            </div>
          </div>
            <div class="flex gap-2 justify-end">
              <Button 
                :disabled="isTestingMailbox" 
                :variant="mailboxTestResult?.success === false ? 'destructive' : 'outline'" 
                type="button" 
                @click="handleMailboxTest"
              >
                <PlugZap :size="16" />
                <span v-if="isTestingMailbox">Connection in progress...</span>
                <span v-else-if="mailboxTestResult?.success === false">IMAP connection failed</span>
                <span v-else>Test connection</span>
              </Button>
              <Button :disabled="isSavingMailbox || isLoading" type="button" @click="handleMailboxSave">
                <Save :size="16" />
                {{ isSavingMailbox ? 'Saving...' : 'Save mailbox settings' }}
              </Button>
            </div>
          </CardContent>
        </form>
      </Card>

      <Card v-if="mailboxTestResult" class="lg:col-span-3" :class="mailboxTestResult.success ? 'border-emerald-700/30' : 'border-destructive/30'">
        <CardContent class="py-4">
          <p :class="mailboxTestResult.success ? 'text-emerald-700' : 'text-destructive'">
            {{ mailboxTestResult.message }}
          </p>
        </CardContent>
      </Card>

      <Card class="lg:col-span-3">
        <CardHeader>
          <CardTitle class="flex items-center gap-2"><WandSparkles :size="18" /> Follow-up Rules</CardTitle>
        </CardHeader>
        <CardContent class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <div v-for="rule in followUpRules" :key="rule.id" class="rounded-lg border border-border p-4 text-sm space-y-4">
            <div class="flex items-center justify-between">
              <strong>{{ rule.daysWithoutReply }} days</strong>
              <Badge :variant="rule.enabled ? 'success' : 'secondary'">{{ rule.enabled ? 'Active' : 'Disabled' }}</Badge>
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium">Days without reply</label>
              <Input v-model.number="rule.daysWithoutReply" type="number" min="1" />
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium">Template type</label>
              <Select v-model="rule.templateType" :options="templateOptions" />
            </div>
            <label class="flex items-center gap-3 text-sm text-muted-foreground">
              <input v-model="rule.enabled" type="checkbox" class="h-4 w-4 rounded border-border" />
              Enabled
            </label>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2 text-muted-foreground"><Send :size="14" /> {{ rule.templateType }}</div>
              <Button size="sm" :disabled="savingRuleId === rule.id || isLoading" @click="handleRuleSave(rule)">
                <Save :size="14" />
                {{ savingRuleId === rule.id ? 'Saving...' : 'Save' }}
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
