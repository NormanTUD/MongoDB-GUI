# MongoGUI

A very simple GUI for viewing, deleting, adding and updating MongoDB-Databases with PHP without needing to memorize MongoDB's kinda quirky language. 

# Screenshot

![Screenshot](screenshot_alpha.png?raw=true "Screenshot")

# Docker

```console
bash run.sh $MONGO_DB_SERVER_IP $MONGO_DB_PORT $MONGO_DB_DB_NAME $MONGO_DB_COLLECTION_NAME $LOCAL_PORT_FOR_THE_GUI


# use local network ip if you want to run on localhost. make sure you bindIp: 0.0.0.0
bash run.sh localhost 27017 dbname colname 1234 # connect to mongodb on localhost:27017, start gui at 1234
```

# Caveats

The search does not work, yet. Hopefully it will soon.

Thanks to chatGPT for helping writing this script.
