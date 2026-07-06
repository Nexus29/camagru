Your backend does not have any condition matching GET /api/posts. Consequently, any attempt to load the gallery hits your catch-all error handling fallback:  

DONE

docker exec -i app_api php -r "
try {
    // Automatically read your live password from the container environment variables
    \$db_pass = getenv('POSTGRES_PASSWORD') ?: (getenv('DB_PASSWORD') ?: 'camagru');
    
    // Connect using 'database' as the host based on your docker compose profile service setup
    \$pdo = new PDO('pgsql:host=database;dbname=camagru', 'camagru_admin', \$db_pass);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    \$username = 'gimmick_user';
    \$email = 'gimmick@example.com';
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


Check the studio

User managment

Post managment


