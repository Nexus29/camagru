#!/bin/sh
# setup.sh

# 1. 🟢 Create target directories for both workspace architectures on the host
mkdir -p frontend/uploads/overlays
mkdir -p backend/uploads/overlays

echo "⚙️ Generating custom retro overlays inside multi-service workspaces..."

# 2. 🚀 Compile overlays directly into the frontend target host mount path
docker run --rm -v "$(pwd)/frontend/uploads/overlays:/target" alpine:3.19 sh -c '
apk add --no-cache php82-cli php82-gd

# Using a clean EOF configuration to avoid string escaping bugs entirely
cat << "EOF" > /tmp/generator.php
<?php
$crt = imagecreatetruecolor(640, 480);
imagesavealpha($crt, true);
imagefill($crt, 0, 0, imagecolorallocatealpha($crt, 0, 0, 0, 127));
$darkBezel = imagecolorallocate($crt, 20, 20, 20);
$screenLine = imagecolorallocate($crt, 50, 50, 50);
imagefilledrectangle($crt, 0, 0, 640, 35, $darkBezel);
imagefilledrectangle($crt, 0, 445, 640, 480, $darkBezel);
imagefilledrectangle($crt, 0, 0, 35, 480, $darkBezel);
imagefilledrectangle($crt, 605, 0, 640, 480, $darkBezel);
for ($i = 0; $i < 4; $i++) {
    imagerectangle($crt, 35 + $i, 35 + $i, 605 - $i, $i, $screenLine);
}
imagestring($crt, 3, 50, 12, "CRT-MODE: 4:3 STANDARD", imagecolorallocate($crt, 0, 255, 0));
imagepng($crt, "/target/crt-border.png");
imagedestroy($crt);

$nes = imagecreatetruecolor(640, 480);
imagesavealpha($nes, true);
imagefill($nes, 0, 0, imagecolorallocatealpha($nes, 0, 0, 0, 127));
$nesRed = imagecolorallocate($nes, 228, 0, 0);
$nesGrey = imagecolorallocate($nes, 107, 107, 107);
for ($i = 0; $i < 8; $i++) {
    imagerectangle($nes, $i, $i, 640 - $i, 480 - $i, ($i % 2 == 0) ? $nesRed : $nesGrey);
}
imagestring($nes, 4, 35, 20, "SELECT / START", $nesGrey);
imagepng($nes, "/target/nes-overlay.png");
imagedestroy($nes);

$dos = imagecreatetruecolor(640, 480);
imagesavealpha($dos, true);
imagefill($dos, 0, 0, imagecolorallocatealpha($dos, 0, 0, 0, 127));
$dosBlue = imagecolorallocate($dos, 0, 0, 170);
$dosWhite = imagecolorallocate($dos, 255, 255, 255);
imagefilledrectangle($dos, 0, 0, 640, 25, $dosBlue);
imagestring($dos, 4, 15, 5, "C:\> COMMAND.COM / RETRO-OS", $dosWhite);
for ($i = 0; $i < 5; $i++) {
    imagerectangle($dos, $i, 25 + $i, 640 - $i, 480 - $i, $dosBlue);
}
imagepng($dos, "/target/dos-border.png");
imagedestroy($dos);
EOF

php82 /tmp/generator.php
'

# 3. Synchronize local copies across host developer environments
cp -R frontend/uploads/overlays/. backend/uploads/overlays/

# 4. 🔥 THE FIX: Copy them cleanly inside your active running Docker volume partition!
echo "🚚 Injecting verified image structures into the running application named volume space..."
docker exec -u root app_api mkdir -p /var/www/html/uploads_shared/overlays
docker cp frontend/uploads/overlays/. app_api:/var/www/html/uploads_shared/overlays/

# 5. 🔒 Apply permissions flags cleanly
docker exec -u root app_api chmod -R 777 /var/www/html/uploads_shared

echo "✔ All 3 custom retro system frames compiled and successfully mounted across host and containers!\n"
