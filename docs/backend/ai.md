# AI

IA sert 4 choses.

## Usages
- extraire offre depuis contenu extension
- analyser fit CV / offre
- rediger mail relance
- resumer mail recruteur

## Provider
- `AI_PROVIDER=openai|anthropic|ollama|null`
- `AiServiceFactory` choisit implémentation
- `NullAiService` = fallback

## Config
- OpenAI, Anthropic, Ollama ont key/model ou url via env.
- Si rien bon, flux reste sans IA.
