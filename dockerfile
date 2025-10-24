FROM php:8.2-apache

# Install PMD and OpenJDK
RUN apt-get update && apt-get install -y unzip && \
    echo "deb http://archive.debian.org/debian buster main" > /etc/apt/sources.list.d/buster.list && \
    echo "Acquire::Check-Valid-Until false;" > /etc/apt/apt.conf.d/99no-check-valid-until && \
    apt-get update && apt-get install -y openjdk-11-jdk && \
    cd /opt && \
    wget https://github.com/pmd/pmd/releases/download/pmd_releases/7.17.0/pmd-bin-7.17.0.zip && \
    unzip pmd-bin-7.17.0.zip && \
    rm pmd-bin-7.17.0.zip && \
    ln -s /opt/pmd-bin-7.17.0/bin/pmd /usr/local/bin/pmd

# Copy app
COPY . /var/www/html/

# Apache config
RUN a2enmod rewrite
EXPOSE 80
