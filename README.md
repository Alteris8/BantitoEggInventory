# InventorySystem
## HTML template
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>InventorySystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

## Access Project
**URL:** http://localhost/php-InventorySystem/test.php## Setup Instructions

### 1. Start WAMP Server
- Make sure WAMP is running (icon should be green)

### 2. Create Database
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Create a new database named: `InventorySystem_db`
3. Click on the database
4. Run SQL to create a sample table (optional):
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3. Test Connection
- Visit: http://localhost/InventorySystem/test.php
- You should see "Hello World" if everything is working

## Database Configuration
Database settings for WAMP:
- **DB_HOST:** localhost (default for WAMP)
- **DB_USER:** root (default for WAMP)
- **DB_PASS:** (empty by default for WAMP)
- **DB_NAME:** InventorySystem_db

## Usage Example
```php
<?php
// Database connection example
$conn = new mysqli('localhost', 'root', '', 'InventorySystem_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query example
$result = $conn->query("SELECT * FROM users");
while ($row = $result->fetch_assoc()) {
    echo $row['username'];
}

$conn->close();
?>
```

## Features
- ✓ Simple PHP setup
- ✓ Ready for MySQL database connection
- ✓ WAMP compatible

## Development
- Add your PHP files in: C:/wamp64/www/InventorySystem/
- Access via: http://localhost/InventorySystem/filename.php

## Adding Tailwind CSS

### Option 1: CDN (Quick/Development)
Add this to your `<head>` tag:
```html
<script src="https://cdn.tailwindcss.com"></script>
```

### Option 2: CLI (Production)
```bash
npm init -y
npm install -D tailwindcss@3
npx tailwindcss init
```

Edit `tailwind.config.js`:
```js
module.exports = {
  content: ["./**/*.html", "./**/*.php"],
  theme: { extend: {} },
  plugins: [],
}
```

Create `input.css`:
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

Add to `package.json` scripts:
```json
"dev": "tailwindcss -i ./input.css -o ./output.css --watch"
```

Then run:
```bash
npm run dev
```

Link in your PHP files instead of CDN:
```html
<link rel="stylesheet" href="output.css">
```
