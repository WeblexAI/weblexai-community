FROM python:3.13.5-alpine3.22

RUN apk add --no-cache docker-cli docker-cli-compose
WORKDIR /agent
COPY docker/update-agent/server.py /agent/server.py
COPY scripts/update-docker.sh /usr/local/bin/update-weblex
RUN chmod 0555 /usr/local/bin/update-weblex
EXPOSE 8080
CMD ["python", "/agent/server.py"]
