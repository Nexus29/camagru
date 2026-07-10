USER:

docker exec -i app_api php -r "
try {
    // Automatically read your live password from the container environment variables
    \$db_pass = getenv('POSTGRES_PASSWORD') ?: (getenv('DB_PASSWORD') ?: 'camagru');
    
    // Connect using 'database' as the host based on your docker compose profile service setup
    \$pdo = new PDO('pgsql:host=database;dbname=camagru', 'camagru_admin', \$db_pass);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    \$username = 'gimmick_user';
    \$email = 'yedon29504@acoxs.com';
    \$passwordHash = password_hash('password123', PASSWORD_BCRYPT); // Matching BCRYPT configuration[cite: 5]

    \$stmt = \$pdo->prepare('INSERT INTO users (username, email, password, is_verified) VALUES (?, ?, ?, true) ON CONFLICT (username) DO NOTHING');
    \$stmt->execute([\$username, \$email, \$passwordHash]);

    echo '🎉 Success! User injected successfully.\n';
} catch (Exception \$e) {
    echo '❌ Error: ' . \$e->getMessage() . '\n';
}
"
gimmick_user
password123

Overlay:
docker-compose run -T frontend php << 'EOF'
<?php
@mkdir("/var/www/html/frontend/uploads/overlays", 0777, true);

// 📺 1. CREATE RETRO CRT BORDER
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
    imagerectangle($crt, 35 + $i, 35 + $i, 605 - $i, 445 - $i, $screenLine);
}
imagestring($crt, 3, 50, 12, "CRT-MODE: 4:3 STANDARD", imagecolorallocate($crt, 0, 255, 0));
imagepng($crt, "/var/www/html/frontend/uploads/overlays/crt-border.png");
imagedestroy($crt);

// 🕹️ 2. CREATE RETRO NES OVERLAY
$nes = imagecreatetruecolor(640, 480);
imagesavealpha($nes, true);
imagefill($nes, 0, 0, imagecolorallocatealpha($nes, 0, 0, 0, 127));
$nesRed = imagecolorallocate($nes, 228, 0, 0);
$nesGrey = imagecolorallocate($nes, 107, 107, 107);
for ($i = 0; $i < 8; $i++) {
    imagerectangle($nes, $i, $i, 640 - $i, 480 - $i, ($i % 2 == 0) ? $nesRed : $nesGrey);
}
imagestring($nes, 4, 35, 20, "SELECT / START", $nesGrey);
imagepng($nes, "/var/www/html/frontend/uploads/overlays/nes-overlay.png");
imagedestroy($nes);

// 💻 3. CREATE VINTAGE DOS BORDER
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
imagepng($dos, "/var/www/html/frontend/uploads/overlays/dos-border.png");
imagedestroy($dos);

echo "✔ All 3 custom retro system frames compiled perfectly inside frontend/uploads/overlays/\n";
EOF

#######################################################################

Security leaks
check these
Store plain or unencrypted passwords in the database.
• Offer the ability to inject HTML or “user” JavaScript in badly protected variables.
• Offer the ability to upload unwanted content on the server.
• Offer the possibility of altering an SQL query.
• Use an extern form to manipulate so-called private data
when i do make re and i was logged it permit to navigate the logged page...

User managment
DONE

Gallery managment
check everything of the gallery features

Editing managment
Take the photo for now
• The creation of the final image (so among others the superposing of the two images)
must be done on the server side.
• Because not everyone has a webcam, you should allow the upload of a user image
instead of capturing one with the webcam.
• The user should be able to delete his edited images, but only his, not other users’
creations.

Bonus
“AJAXify” exchanges with the server.
• Propose a live preview of the edited result, directly on the webcam preview. We
should note that this is much easier than it looks.
• Do an infinite pagination of the gallery part of the site.
• Offer the possibility to a user to share his images on social networks

check the style.css at the end of the project
