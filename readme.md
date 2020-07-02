# Routee task
## Description
Simple implementation of service, that checks temperature with openweathermap API and sends SMS notifications. Implemented in pure (vanilla) PHP 7.
## Launching

### Prerequirements
Installed one of:
* PHP 7 with curl and json modules
* docker (recommended)

### Executing as standalone console application
Just run `./app.php`

### Executing with docker
1. go to task folder
2. run: `docker run -v $(pwd):/app --rm --name routee-task php:7.2-cli-stretch /app/app.php`

