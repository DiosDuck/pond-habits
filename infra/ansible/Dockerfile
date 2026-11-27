# Ansible runner (ADR 0011). "make provision" runs the playbooks from this image,
# so the workstation needs only Docker. The host UID owns the files, like the other dev images.
FROM python:3.14-slim

ARG UID=1000
ARG GID=1000

# OpenSSH client: Ansible connects to the server over SSH (through the host SSH agent).
RUN apt-get update \
    && apt-get install -y --no-install-recommends openssh-client \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt requirements.yml /tmp/
RUN pip install --no-cache-dir -r /tmp/requirements.txt \
    && ansible-galaxy collection install -r /tmp/requirements.yml -p /usr/share/ansible/collections

# SSH refuses to run for a UID with no user entry.
RUN groupadd -g "${GID}" ansible && useradd -m -u "${UID}" -g "${GID}" ansible
USER ansible
WORKDIR /infra/ansible
