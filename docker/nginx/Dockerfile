FROM nginx:1.27-alpine

# Needed to generate the self-signed certificate in the entrypoint script below.
RUN apk add --no-cache openssl

COPY docker/nginx/generate-cert.sh /docker-entrypoint.d/50-generate-cert.sh
RUN chmod +x /docker-entrypoint.d/50-generate-cert.sh