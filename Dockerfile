# Use the official PHP 8.3 image
FROM php:8.3-cli

# Set working directory
WORKDIR /app

# Copy all files into the container
COPY . /app

# Expose the Render default port
EXPOSE 10000

# Start PHP's built-in web server
CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
