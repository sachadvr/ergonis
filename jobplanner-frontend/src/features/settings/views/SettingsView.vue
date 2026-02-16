<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { Server, Inbox, Send, WandSparkles, Save } from 'lucide-vue-next'
import { useSettingsStore } from '../stores/settings.store'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, type SelectOption } from '@/components/ui/select'
import { FOLLOW_UP_TEMPLATE_TYPES } from '@/lib/constants/app.constants'
import type { FollowUpRule, UserMailboxSettings } from '@/types/models.types'

const settingsStore = useSettingsStore()
const { config, mailboxSettings, followUpRules, isLoading, error } = storeToRefs(settingsStore)

const simulationLabel = computed(() => (config.value?.simulationMode ? 'Enabled' : 'Disabled'))
const mailboxForm = ref<Partial<UserMailboxSettings>>({})
const isSavingMailbox = ref(false)
const savingRuleId = ref<number | null>(null)
const successMessage = ref('')

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

const handleMailboxSave = async () => {
  isSavingMailbox.value = true
  try {
    await settingsStore.saveMailboxSettings(mailboxForm.value)
    successMessage.value = 'Mailbox settings saved.'
  } finally {
    isSavingMailbox.value = false
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

    <div v-if="isLoading" class="text-sm text-muted-foreground">Loading settings...</div>

    <div v-else class="grid gap-4 lg:grid-cols-3">
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2"><Server :size="18" /> App Config</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <div class="flex items-center justify-between text-sm">
            <span>Simulation mode</span>
            <Badge :variant="config?.simulationMode ? 'warning' : 'secondary'">{{ simulationLabel }}</Badge>
          </div>
          <p class="text-xs text-muted-foreground">Read-only: this value comes from backend runtime configuration.</p>
        </CardContent>
      </Card>

      <Card class="lg:col-span-2">
        <CardHeader>
          <CardTitle class="flex items-center gap-2"><Inbox :size="18" /> Mailbox Settings</CardTitle>
        </CardHeader>
        <CardContent class="space-y-5 text-sm">
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

          <div class="flex justify-end">
            <Button :disabled="isSavingMailbox || isLoading" @click="handleMailboxSave">
              <Save :size="16" />
              {{ isSavingMailbox ? 'Saving...' : 'Save mailbox settings' }}
            </Button>
          </div>
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
