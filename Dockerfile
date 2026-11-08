FROM python:3.14-slim
RUN apt-get update && apt-get install -y --no-install-recommends git build-essential libffi-dev && rm -rf /var/lib/apt/lists/*
WORKDIR /opt/hermes
# Pin the same revision as the upstream submodule, including on hosts that skip submodules.
RUN git init . && git remote add origin https://github.com/NousResearch/hermes-agent.git && git fetch --depth=1 origin 110afdd60e3098b86950963b06a792e261bd36a4 && git checkout --detach FETCH_HEAD && pip install --no-cache-dir . aiohttp
COPY ielts_backup/ /opt/hermes/ielts_backup/
RUN useradd --create-home --uid 10001 tutor
USER tutor
ENV PYTHONPATH=/opt/hermes
CMD ["python","/opt/hermes/ielts_backup/server.py"]
