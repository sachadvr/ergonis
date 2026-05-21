# AI

IA sert 5 choses.

## Usages
- extraire offre depuis contenu extension
- analyser fit CV / offre
- rediger mail relance (follow_up / thank_you / spontaneous via `FollowUpEmailPromptBuilder`)
- resumer mail recruteur
- suggerer reponses rapides au mail recruteur

## Provider
- `AI_PROVIDER=openai|anthropic|ollama|null`
- `AiServiceFactory` choisit implémentation
- `NullAiService` = fallback

## Config
- OpenAI, Anthropic, Ollama ont key/model ou url via env.
- Si rien bon, flux reste sans IA.
