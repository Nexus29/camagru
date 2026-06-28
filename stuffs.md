# Delete the empty directory if it exists to reset it
rm -rf docker/nginx/ssl

# Recreate the path explicitly
mkdir -p docker/nginx/ssl

# Generate the keys directly into that path
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout docker/nginx/ssl/camagru.key \
  -out docker/nginx/ssl/camagru.crt \
  -subj "/C=FR/ST=Paris/L=Paris/O=42/CN=localhost"

# Enter in my container website
docker exec -it app sh

# frame for overlay
docker exec -it app php -r "
  @mkdir('/var/www/html/uploads/overlays', 0777, true);
  
  // Clean PHP execution way to apply permissions recursively
  exec('chmod -R 777 /var/www/html/uploads');

  \$img = imagecreatetruecolor(640, 480);
  imagesavealpha(\$img, true);
  \$trans = imagecolorallocatealpha(\$img, 0, 0, 0, 127);
  imagefill(\$img, 0, 0, \$trans);
  
  // Add a nice neon cyan border matching your Camagru theme colors!
  \$borderColor = imagecolorallocate(\$img, 0, 173, 181);
  for (\$i = 0; \$i < 5; \$i++) {
      imagerectangle(\$img, 10 + \$i, 10 + \$i, 630 - \$i, 470 - \$i, \$borderColor);
  }
  
  imagepng(\$img, '/var/www/html/uploads/overlays/frame1.png');
  imagedestroy(\$img);
  echo '✔ frame1.png built perfectly inside uploads/overlays/\n';
"
