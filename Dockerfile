FROM php:8.3-cli

COPY index.php /app/
WORKDIR /app

# Start PHP built-in server on Render's $PORT
CMD ["php", "-S", "0.0.0.0:${PORT:-8080}", "index.php"]
