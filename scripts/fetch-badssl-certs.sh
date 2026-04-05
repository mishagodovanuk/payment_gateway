#!/usr/bin/env bash
# Downloads BadSSL's public demo client PEM (cert + encrypted key in one file).
# If .env does not exist, creates it from .env.example with correct paths.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT_DIR="${ROOT}/var/certs"
PEM="${OUT_DIR}/badssl.com-client.pem"
ENV_FILE="${ROOT}/.env"
EXAMPLE="${ROOT}/.env.example"

mkdir -p "${OUT_DIR}"
curl -fsSL -o "${PEM}" "https://badssl.com/certs/badssl.com-client.pem"

echo "Saved: ${PEM}"

if [[ ! -f "${ENV_FILE}" ]]; then
  cp "${EXAMPLE}" "${ENV_FILE}"
  sed -i "s|^MTLS_CLIENT_CERT=.*|MTLS_CLIENT_CERT=${PEM}|" "${ENV_FILE}"
  sed -i "s|^MTLS_CLIENT_KEY=.*|MTLS_CLIENT_KEY=${PEM}|" "${ENV_FILE}"
  sed -i "s|^MTLS_CLIENT_KEY_PASSPHRASE=.*|MTLS_CLIENT_KEY_PASSPHRASE=badssl.com|" "${ENV_FILE}"
  sed -i "s|^HMAC_SECRET=.*|HMAC_SECRET=demo-secret|" "${ENV_FILE}"
  echo ""
  echo "Created ${ENV_FILE} — you can run: php try.php"
else
  echo ""
  echo ".env already exists. Ensure it contains:"
  echo ""
  echo "MTLS_CLIENT_CERT=${PEM}"
  echo "MTLS_CLIENT_KEY=${PEM}"
  echo "MTLS_CLIENT_KEY_PASSPHRASE=badssl.com"
  echo ""
  echo "Or remove .env and run this script again to generate a fresh one."
fi
