#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ROOT_DIR}/.env"

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "Missing .env at ${ENV_FILE}"
  exit 1
fi

CURRENT_BRANCH="$(git -C "${ROOT_DIR}" rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")"

# Load .env values for DB connection and source DB.
set -a
source "${ENV_FILE}"
set +a

required=(DB_HOST DB_PORT DB_DATABASE DB_USERNAME)
for key in "${required[@]}"; do
  if [[ -z "${!key:-}" ]]; then
    echo "Missing required variable in .env: ${key}"
    exit 1
  fi
done

SOURCE_DB="${1:-${DB_DATABASE}}"
TARGET_DB="${2:-}"

if [[ -z "${TARGET_DB}" ]]; then
  if [[ "${CURRENT_BRANCH}" == "test" ]]; then
    TARGET_DB="${SOURCE_DB}_test"
  else
    TARGET_DB="${SOURCE_DB}_${CURRENT_BRANCH}"
  fi
fi

if [[ "${SOURCE_DB}" == "${TARGET_DB}" ]]; then
  echo "Source and target DB are the same (${SOURCE_DB}). Nothing to do."
  exit 0
fi

MYSQL_BASE=(mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}")
MYSQLDUMP_BASE=(mysqldump -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}")
if [[ -n "${DB_PASSWORD:-}" ]]; then
  export MYSQL_PWD="${DB_PASSWORD}"
fi

clone_with_mysql_cli() {
  echo "Creating target database if needed: ${TARGET_DB}"
  "${MYSQL_BASE[@]}" -e "CREATE DATABASE IF NOT EXISTS \`${TARGET_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

  echo "Cloning data from ${SOURCE_DB} -> ${TARGET_DB} via mysqldump. This can take a while..."
  "${MYSQLDUMP_BASE[@]}" --single-transaction --quick --routines --triggers "${SOURCE_DB}" | "${MYSQL_BASE[@]}" "${TARGET_DB}"
}

clone_with_php_pdo() {
  echo "Falling back to PHP PDO-based clone path."
  DB_HOST="${DB_HOST}" DB_PORT="${DB_PORT}" DB_USERNAME="${DB_USERNAME}" DB_PASSWORD="${DB_PASSWORD:-}" SOURCE_DB="${SOURCE_DB}" TARGET_DB="${TARGET_DB}" php -r '
    $host = getenv("DB_HOST");
    $port = getenv("DB_PORT");
    $user = getenv("DB_USERNAME");
    $pass = getenv("DB_PASSWORD");
    $source = getenv("SOURCE_DB");
    $target = getenv("TARGET_DB");

    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    $admin = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, $opts);
    $src = new PDO("mysql:host=$host;port=$port;dbname=$source;charset=utf8mb4", $user, $pass, $opts);
    $admin->exec("CREATE DATABASE IF NOT EXISTS `$target` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $admin->exec("SET FOREIGN_KEY_CHECKS=0");

    $tables = $src->query("SHOW FULL TABLES WHERE Table_type = \"BASE TABLE\"")->fetchAll();
    foreach ($tables as $row) {
      $table = array_values($row)[0];
      $admin->exec("DROP TABLE IF EXISTS `$target`.`$table`");
      $createRow = $src->query("SHOW CREATE TABLE `$table`")->fetch();
      $createSql = $createRow["Create Table"];
      $admin->exec("USE `$target`");
      $admin->exec($createSql);
      $admin->exec("INSERT INTO `$target`.`$table` SELECT * FROM `$source`.`$table`");
    }

    $admin->exec("SET FOREIGN_KEY_CHECKS=1");
    fwrite(STDOUT, "PDO clone completed.\n");
  '
}

if command -v mysql >/dev/null 2>&1 && command -v mysqldump >/dev/null 2>&1; then
  if ! clone_with_mysql_cli; then
    clone_with_php_pdo
  fi
else
  clone_with_php_pdo
fi

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

replace_or_append "${ENV_FILE}" "DB_DATABASE" "${TARGET_DB}"

echo "Updated .env DB_DATABASE=${TARGET_DB} for branch ${CURRENT_BRANCH}."
echo "Done. Your branch now points to the cloned database."
