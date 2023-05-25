#!/bin/bash

# Import the MongoDB GPG key
wget -qO - https://www.mongodb.org/static/pgp/server-5.0.asc | sudo apt-key add -

# Add the MongoDB repository
echo "deb [ arch=amd64,arm64 ] https://repo.mongodb.org/apt/debian buster/mongodb-org/5.0 main" | sudo tee /etc/apt/sources.list.d/mongodb-org-5.0.list

# Update package lists
sudo apt-get update

# Install MongoDB
sudo apt-get install -y mongodb-org php docker-compose

# Start MongoDB service
echo "Starting MongoDB service..."
sudo systemctl start mongod

# Wait for MongoDB to start
sleep 5

# Check MongoDB service status
sudo systemctl status mongod

# Generate random data and insert into MongoDB
echo "Generating random documents..."
NUM_DOCUMENTS=100  # Change this value to set the number of documents

for ((i=1; i<=$NUM_DOCUMENTS; i++))
do
  DOCUMENT=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 10 | head -n 1)
  echo "Inserting document $i: $DOCUMENT"
  mongo --eval "db.test.insert({data: '$DOCUMENT'})" >/dev/null 2>&1
done

echo "Random document insertion complete!"

