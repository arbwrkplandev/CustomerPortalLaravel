#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ROOT_DIR}/.deploy.env"
CURRENT_BRANCH="$(git -C "${ROOT_DIR}" rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")"
DEPLOY_ALLOWED_BRANCH="${DEPLOY_ALLOWED_BRANCH:-main}"

if [[ "${CURRENT_BRANCH}" != "${DEPLOY_ALLOWED_BRANCH}" ]]; then
  echo "Skipping deploy: current branch '${CURRENT_BRANCH}' is not '${DEPLOY_ALLOWED_BRANCH}'."
  echo "Only '${DEPLOY_ALLOWED_BRANCH}' is allowed to deploy to live."
  exit 0
fi

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "Missing .deploy.env. Copy .deploy.env.example to .deploy.env and fill values."
  exit 1
fi

set -a
source "${ENV_FILE}"
set +a

required=(DEPLOY_HOST DEPLOY_USER DEPLOY_PASSWORD DEPLOY_REMOTE_ROOT APP_URL DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD DEPLOY_WEBHOOK_TOKEN)
for key in "${required[@]}"; do
  if [[ -z "${!key:-}" ]]; then
    echo "Missing required variable in .deploy.env: ${key}"
    exit 1
  fi
done

APP_DIR_NAME="${DEPLOY_APP_DIRNAME:-laravel_app}"
REMOTE_APP_DIR="${DEPLOY_REMOTE_ROOT}/${APP_DIR_NAME}"
LFTP_AUTH="${DEPLOY_USER},${DEPLOY_PASSWORD}"
LFTP_TARGET="sftp://${DEPLOY_HOST}"

WORK_TMP="$(mktemp -d)"
trap 'rm -rf "${WORK_TMP}"' EXIT

# Build production .env for server from current local .env
cp "${ROOT_DIR}/.env" "${WORK_TMP}/.env.server"

replace_or_append() {
  local file="$1"
  local key="$2"
  local value="$3"
  local escaped
  escaped="$(printf '%s' "$value" | sed -e 's/[&]/\\\\&/g')"
  if grep -qE "^${key}=" "$file"; then
    sed -i.bak -E "s|^${key}=.*$|${key}=${escaped}|" "$file"
    rm -f "${file}.bak"
  else
    printf '\n%s=%s\n' "$key" "$value" >> "$file"
  fi
}

replace_or_append "${WORK_TMP}/.env.server" "APP_ENV" "production"
replace_or_append "${WORK_TMP}/.env.server" "APP_DEBUG" "false"
replace_or_append "${WORK_TMP}/.env.server" "APP_URL" "${APP_URL}"
replace_or_append "${WORK_TMP}/.env.server" "DB_HOST" "${DB_HOST}"
replace_or_append "${WORK_TMP}/.env.server" "DB_PORT" "${DB_PORT}"
replace_or_append "${WORK_TMP}/.env.server" "DB_DATABASE" "${DB_DATABASE}"
replace_or_append "${WORK_TMP}/.env.server" "DB_USERNAME" "${DB_USERNAME}"
replace_or_append "${WORK_TMP}/.env.server" "DB_PASSWORD" "${DB_PASSWORD}"
replace_or_append "${WORK_TMP}/.env.server" "DB_SOCKET" ""
replace_or_append "${WORK_TMP}/.env.server" "DEPLOY_WEBHOOK_TOKEN" "${DEPLOY_WEBHOOK_TOKEN}"
replace_or_append "${WORK_TMP}/.env.server" "SESSION_DOMAIN" "customer-portal.wrkplan.in"

cat > "${WORK_TMP}/index.php" <<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists(__DIR__.'/__APP_DIR__/storage/framework/maintenance.php')) {
  require __DIR__.'/__APP_DIR__/storage/framework/maintenance.php';
}

require __DIR__.'/__APP_DIR__/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/__APP_DIR__/bootstrap/app.php';

$app->handleRequest(Request::capture());
PHP

sed -i.bak "s|__APP_DIR__|${APP_DIR_NAME}|g" "${WORK_TMP}/index.php"
rm -f "${WORK_TMP}/index.php.bak"

cat > "${WORK_TMP}/.htaccess" <<'HTACCESS'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS

echo "[1/5] Ensuring remote directories exist"
lftp -u "${LFTP_AUTH}" "${LFTP_TARGET}" -e "set sftp:auto-confirm yes; set cmd:fail-exit no; mkdir ${REMOTE_APP_DIR}; bye" || true

echo "[2/5] Uploading Laravel app files to ${REMOTE_APP_DIR}"
lftp -u "${LFTP_AUTH}" "${LFTP_TARGET}" -e "set sftp:auto-confirm yes; set net:timeout 25; set net:max-retries 2; set xfer:clobber yes; set mirror:set-permissions no; mirror -R --verbose=1 --parallel=1 --no-perms --exclude-glob .git --exclude-glob .git/** --exclude-glob node_modules --exclude-glob node_modules/** --exclude-glob tests --exclude-glob tests/** --exclude-glob docs --exclude-glob docs/** --exclude-glob storage/logs/* --exclude-glob storage/framework/cache/* --exclude-glob storage/framework/sessions/* --exclude-glob storage/framework/views/* --exclude-glob storage/api-docs/* --exclude-glob .env --exclude-glob .env.bak --exclude-glob .deploy.env --exclude-glob .phpunit.result.cache --exclude-glob database/database.sqlite --exclude-glob README.md --exclude-glob README.md.bak --exclude-glob .editorconfig --exclude-glob .gitattributes --exclude-glob .gitignore --exclude-glob .npmrc --exclude-glob public/storage ${ROOT_DIR} ${REMOTE_APP_DIR}; bye"

echo "[3/5] Uploading web root files"
lftp -u "${LFTP_AUTH}" "${LFTP_TARGET}" -e "set sftp:auto-confirm yes; set net:timeout 25; set net:max-retries 2; set xfer:clobber yes; set mirror:set-permissions no; mirror -R --verbose=1 --parallel=1 --no-perms --exclude-glob storage ${ROOT_DIR}/public ${DEPLOY_REMOTE_ROOT}; mirror -R --verbose=1 --parallel=1 --no-perms ${ROOT_DIR}/storage/app/public ${DEPLOY_REMOTE_ROOT}/storage; put -O ${DEPLOY_REMOTE_ROOT} ${WORK_TMP}/index.php -o index.php; put -O ${DEPLOY_REMOTE_ROOT} ${WORK_TMP}/.htaccess -o .htaccess; bye"

echo "[4/5] Uploading production .env"
lftp -u "${LFTP_AUTH}" "${LFTP_TARGET}" -e "set sftp:auto-confirm yes; put -O ${REMOTE_APP_DIR} ${WORK_TMP}/.env.server -o .env; bye"

echo "[5/5] Running safe migrations via webhook"
HTTP_CODE=$(curl -s -o "${WORK_TMP}/deploy-response.json" -w "%{http_code}" -X POST "${APP_URL}/__deploy/run-migrations" -H "X-Deploy-Token: ${DEPLOY_WEBHOOK_TOKEN}" || true)
if [[ "${HTTP_CODE}" != "200" ]]; then
  echo "Deploy webhook failed with status ${HTTP_CODE}."
  echo "Response:"
  cat "${WORK_TMP}/deploy-response.json"
  exit 1
fi

echo "Deployment completed successfully."
cat "${WORK_TMP}/deploy-response.json"
