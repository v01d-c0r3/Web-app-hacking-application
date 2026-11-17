FROM python:3.13-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

WORKDIR /app

# No requirements: the manager is standard library only, so the one container
# that holds the Docker socket pulls in no third-party code.
COPY docker_api.py manager.py labs.json ./

# Runs as root inside the container because the Docker socket on the host is
# owned by root. That grants nothing extra: holding the socket is already
# root-equivalent, which is exactly why this lives apart from the portal.
EXPOSE 8000

CMD ["python", "manager.py"]
