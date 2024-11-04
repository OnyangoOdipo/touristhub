<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        /* Custom Safari Theme Styles */
        :root {
            --color-safari-brown: #8B4513;
            --color-safari-gold: #DAA520;
            --color-safari-green: #228B22;
            --color-safari-sand: #F4A460;
        }

        body {
            font-family: 'Poppins', sans-serif;
        }

        h1, h2, h3, .safari-heading {
            font-family: 'Playfair Display', serif;
        }

        .safari-pattern {
            background-image: url('data:image/svg+xml,<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h20v20H0z" fill="%23F4A460" fill-opacity="0.1"/></svg>');
        }

        .safari-gradient {
            background: linear-gradient(to right, var(--color-safari-brown), var(--color-safari-gold));
        }

        .safari-shadow {
            box-shadow: 0 4px 12px rgba(139, 69, 19, 0.1);
        }
    </style>
</head>
<body class="bg-[#FDF8F3] min-h-screen flex flex-col">
    <?php include 'navbar.php'; ?>
</body>
</html> 