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
DONE

Bonus
“AJAXify” exchanges with the server.
• Propose a live preview of the edited result, directly on the webcam preview. We
should note that this is much easier than it looks.
• Do an infinite pagination of the gallery part of the site.
• Offer the possibility to a user to share his images on social networks

check the style.css at the end of the project
