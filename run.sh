#!/bin/bash

# Load environment variables from shell
DB_HOST=$1
DB_PORT=$2
DB_NAME=$3
DB_COLLECTION=$4
LOCAL_PORT=$5

ips=$(ip addr | grep inet | grep -v : | sed -e 's#.*inet\s*##' | sed -e 's#/.*##' | grep -v "^127.")
# Check if DB_HOST is localhost or 127.0.0.1
if [[ $DB_HOST == "localhost" || $DB_HOST == "127.0.0.1" ]]; then
  # Create an array of IPs
  ip_array=()
  while read -r ip; do
    ip_array+=("$ip" "")
  done <<< "$ips"

  # Show Whiptail menu
  selected_ip=$(whiptail --title "Local IPs" --menu "Choose a local IP:" 15 60 6 "${ip_array[@]}" 3>&1 1>&2 2>&3)

  # Check if a selection was made
  if [[ -n $selected_ip ]]; then
    echo "Selected IP: $selected_ip"
    # Use the selected IP for DB_HOST
    DB_HOST="$selected_ip"
    echo "DB_HOST set to: $DB_HOST"
    # Add your logic here using the updated DB_HOST variable
  else
    echo "No IP selected. Exiting..."
    exit 1
  fi
fi

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
