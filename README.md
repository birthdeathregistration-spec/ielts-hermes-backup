# IELTS Hermes backup

Standalone IELTS fallback service with book retrieval, checked web research and a bilingual voice-chat integration for the v4 cPanel site. The model service runs on Render. The website owns student sessions, private configuration and persistent book passages.

[Installation, book import and teacher setup](SETUP.md)

Hermes Agent is pinned via the upstream submodule to commit 110afdd60e3098b86950963b06a792e261bd36a4. The Dockerfile fetches the same revision at build time. See upstream/LICENSE.
