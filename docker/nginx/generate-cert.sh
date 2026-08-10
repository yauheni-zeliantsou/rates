set -e

CERT_DIR=/etc/nginx/ssl
CERT_FILE="$CERT_DIR/selfsigned.crt"
KEY_FILE="$CERT_DIR/selfsigned.key"

mkdir -p "$CERT_DIR"

if [ ! -f "$CERT_FILE" ] || [ ! -f "$KEY_FILE" ]; then
    echo "generate-cert: creating self-signed TLS certificate for local development"
    openssl req -x509 -nodes -days 825 -newkey rsa:2048 \
        -keyout "$KEY_FILE" -out "$CERT_FILE" \
        -subj "/CN=localhost" \
        -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"
fi