#!/bin/bash

# Load environment variables from shell
DB_HOST=$1
DB_PORT=$2
DB_NAME=$3
DB_COLLECTION=$4
LOCAL_PORT=$5

export DB_HOST
export DB_PORT
export DB_NAME
export DB_COLLECTION
export LOCAL_PORT

# Write environment variables to .env file
echo "#!/bin/bash" > .env
echo "DB_HOST=$DB_HOST" >> .env
echo "DB_PORT=$DB_PORT" >> .env
echo "DB_NAME=$DB_NAME" >> .env
echo "DB_COLLECTION=$DB_COLLECTION" >> .env
echo "LOCAL_PORT=$LOCAL_PORT" >> .env

sudo docker-compose build

# Start the Docker container
sudo docker-compose up -d
