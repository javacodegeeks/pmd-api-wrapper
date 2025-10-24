FROM php:8.2-apache

# Install PMD and OpenJDK
RUN apt-get update && apt-get install -y unzip wget && \
    cd /opt && \
    wget https://github.com/adoptium/temurin11-binaries/releases/download/jdk-11.0.20+8/OpenJDK11U-jdk_x64_linux_hotspot_11.0.20_8.tar.gz && \
    tar -xzf OpenJDK11U-jdk_x64_linux_hotspot_11.0.20_8.tar.gz && \
    rm OpenJDK11U-jdk_x64_linux_hotspot_11.0.20_8.tar.gz && \
    ln -s /opt/jdk-11.0.20+8/bin/java /usr/local/bin/java && \
    wget https://github.com/pmd/pmd/releases/download/pmd_releases/7.17.0/pmd-bin-7.17.0.zip && \
    unzip pmd-bin-7.17.0.zip && \
    rm pmd-bin-7.17.0.zip && \
    ln -s /opt/pmd-bin-7.17.0/bin/pmd /usr/local/bin/pmd

# Copy app
COPY . /var/www/html/

# Apache config
RUN a2enmod rewrite
EXPOSE 80
