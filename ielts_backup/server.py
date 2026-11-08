"""Server-side IELTS fallback. Each request gets a fresh, tool-free Hermes agent."""
import asyncio
import hmac
import os

from aiohttp import web
from research import research

KEY = os.environ["IELTS_BACKUP_KEY"]
if len(KEY) < 32:
    raise ValueError("Set IELTS_BACKUP_KEY to a random secret of at least 32 characters")
MODEL = os.environ["BACKUP_MODEL"]
URL = os.environ["BACKUP_MODEL_BASE_URL"]
TOKEN = os.environ["BACKUP_MODEL_KEY"]
slot = asyncio.Lock()


def tutor(system, message, limit):
    from run_agent import AIAgent

    agent = AIAgent(provider="custom", base_url=URL, api_key=TOKEN, model=MODEL,
                    enabled_toolsets=[], skip_memory=True, skip_context_files=True,
                    skip_background_review=True, max_iterations=1, max_tokens=limit,
                    quiet_mode=True, run_budget_seconds=50)
    if agent.tools:
        raise RuntimeError("Student tutor requires an empty toolset")
    result = agent.run_conversation(message, system_message=system)
    answer = result.get("final_response")
    if result.get("failed") or result.get("error") or not isinstance(answer, str) or not answer.strip():
        raise RuntimeError("Provider unavailable")
    return answer


async def health(request):
    return web.json_response({"status": "ok"})


async def reply(request):
    if not hmac.compare_digest(request.headers.get("Authorization", "").encode(), ("Bearer " + KEY).encode()):
        raise web.HTTPUnauthorized()
    try:
        body = await request.json()
        system, message, limit = body["system"], body["message"], body["limit"]
        if not all(isinstance(s, str) and s.strip() for s in (system, message)):
            raise ValueError()
        if type(limit) is not int or not 1 <= limit <= 4096:
            raise ValueError()
    except (ValueError, TypeError, KeyError):
        raise web.HTTPBadRequest()
    if slot.locked():
        raise web.HTTPServiceUnavailable()
    async with slot:
        try:
            if body.get("research"):
                lang = "bn" if body.get("language") == "bn" else "en"
                answer = await research(message, tutor, lang)
            else:
                answer = await asyncio.to_thread(tutor, system, message, limit)
        except Exception:
            raise web.HTTPBadGateway(text="Backup model unavailable") from None
    return web.json_response({"answer": answer})


app = web.Application(client_max_size=512 * 1024)
app.add_routes([web.get("/health", health), web.post("/reply", reply)])
if __name__ == "__main__":
    web.run_app(app, port=int(os.getenv("PORT", "10000")), access_log=None)
