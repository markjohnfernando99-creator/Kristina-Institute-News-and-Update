# TODO — Improve Kristina Bot knowledgeability

## Step 1: Update plan implemented
- [x] Gathered current chatbot logic (frontend + `api/chat.php`).
- [x] Improve intent classification keywords + topics.
- [x] Add richer fixed templates.
- [x] Strengthen grounded retrieval formatting and next-steps.
- [x] Keep deterministic/grounded behavior.

## Step 2: Frontend consistency
- [x] Mirror improved topic routing in `assets/chatbot.js` fallback.

## Step 3: verification
- [ ] Validate responses are grounded and actionable.

## Step 4: add OpenAI fallback when grounded retrieval fails
- [x] Update `assets/chatbot.js` to call `api/openai_chat.php` when `api/chat.php` returns no/weak match.
- [x] Send `conversation_id` consistently for OpenAI conversation history.
- [ ] Manual test of failure paths (unknown query → OpenAI answer).


