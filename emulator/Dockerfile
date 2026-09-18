FROM debian:latest AS unzip
WORKDIR /
RUN apt-get update && apt-get -y install unzip
COPY emulator_data.zip .
RUN unzip emulator_data.zip

FROM php:8.3
RUN docker-php-ext-install sockets
WORKDIR /app
COPY decoder_emulator.php .
COPY --from=unzip /emulator_data emulator_data

EXPOSE 5403
CMD php decoder_emulator.php emulator_data