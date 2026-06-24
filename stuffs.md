# Delete the empty directory if it exists to reset it
rm -rf docker/nginx/ssl

# Recreate the path explicitly
mkdir -p docker/nginx/ssl

# Generate the keys directly into that path
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout docker/nginx/ssl/camagru.key \
  -out docker/nginx/ssl/camagru.crt \
  -subj "/C=FR/ST=Paris/L=Paris/O=42/CN=localhost"