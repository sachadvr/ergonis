export interface MailConfig {
  imapHost: string
  imapPort: number
  imapEncryption: 'ssl' | 'tls' | 'none'
  smtpHost: string
  smtpPort: number
  smtpEncryption: 'ssl' | 'tls' | 'none'
}

const COMMON_PROVIDERS: Record<string, MailConfig> = {
  'gmail.com': {
    imapHost: 'imap.gmail.com',
    imapPort: 993,
    imapEncryption: 'ssl',
    smtpHost: 'smtp.gmail.com',
    smtpPort: 465,
    smtpEncryption: 'ssl',
  },
  'outlook.com': {
    imapHost: 'outlook.office365.com',
    imapPort: 993,
    imapEncryption: 'ssl',
    smtpHost: 'smtp.office365.com',
    smtpPort: 587,
    smtpEncryption: 'tls',
  },
  'hotmail.com': {
    imapHost: 'outlook.office365.com',
    imapPort: 993,
    imapEncryption: 'ssl',
    smtpHost: 'smtp.office365.com',
    smtpPort: 587,
    smtpEncryption: 'tls',
  },
  'live.com': {
    imapHost: 'outlook.office365.com',
    imapPort: 993,
    imapEncryption: 'ssl',
    smtpHost: 'smtp.office365.com',
    smtpPort: 587,
    smtpEncryption: 'tls',
  },
  'icloud.com': {
    imapHost: 'imap.mail.me.com',
    imapPort: 993,
    imapEncryption: 'ssl',
    smtpHost: 'smtp.mail.me.com',
    smtpPort: 587,
    smtpEncryption: 'tls',
  },
  'yahoo.com': {
    imapHost: 'imap.mail.yahoo.com',
    imapPort: 993,
    imapEncryption: 'ssl',
    smtpHost: 'smtp.mail.yahoo.com',
    smtpPort: 465,
    smtpEncryption: 'ssl',
  },
}

export function detectMailConfig(email: string): MailConfig | null {
  const domain = email.split('@')[1]?.toLowerCase()
  if (!domain) return null
  return COMMON_PROVIDERS[domain] || null
}
