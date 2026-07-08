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

#######################################################################

Security leaks
check these
Store plain or unencrypted passwords in the database.
• Offer the ability to inject HTML or “user” JavaScript in badly protected variables.
• Offer the ability to upload unwanted content on the server.
• Offer the possibility of altering an SQL query.
• Use an extern form to manipulate so-called private data

User managment
DONE

Gallery managment
check everything of the gallery features

Editing managment
Superposable images must be selectable and the button allowing to take the pic-
ture should be inactive (not clickable) as long as no superposable image has been
selected.
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
