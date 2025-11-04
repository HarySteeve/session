# Utilise une image PHP officielle avec support MySQLi
FROM php:8.2-cli

# Dossier de travail dans le conteneur
WORKDIR /var/www/html

# Copie les fichiers PHP dans le conteneur
COPY . /var/www/html

# Installe l’extension MySQLi
RUN docker-php-ext-install mysqli

# Expose le port 8000
EXPOSE 8000

# Démarre le serveur PHP intégré
CMD ["php", "-S", "0.0.0.0:8000"]
