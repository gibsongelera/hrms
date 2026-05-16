#!/bin/bash
set -e

# At runtime, find the GID of the mounted Docker socket and add jenkins to that group.
# This works regardless of the GID used by the host (Docker Desktop on Windows/Mac/Linux).
if [ -S /var/run/docker.sock ]; then
    SOCK_GID=$(stat -c '%g' /var/run/docker.sock)
    # If no group owns this GID yet, create one
    if ! getent group "$SOCK_GID" > /dev/null 2>&1; then
        groupadd -g "$SOCK_GID" dockerhost
    fi
    GROUP_NAME=$(getent group "$SOCK_GID" | cut -d: -f1)
    usermod -aG "$GROUP_NAME" jenkins
fi

# Drop from root back to the jenkins user and hand off to the real Jenkins entrypoint
exec gosu jenkins /usr/bin/tini -- /usr/local/bin/jenkins.sh "$@"
