# IELTS Hermes backup

Standalone fallback tutor for the IELTS website. Gemini remains the primary provider. The Render service uses a separately configured OpenAI-compatible model.

Deploy the Render Blueprint and configure its four secrets and model settings. Set `BACKUP_MODEL_BASE_URL` to the provider's HTTPS API base (including `/v1` where required). Use a random `IELTS_BACKUP_KEY` of at least 32 characters. Keep real values out of Git. Monitor `GET /health`; it checks the process. The website sends authenticated `POST /reply` requests.

Hermes Agent is pinned to upstream commit `110afdd60e3098b86950963b06a792e261bd36a4`. See `upstream` for its source and license. Website-side PHP integration is distributed separately.
