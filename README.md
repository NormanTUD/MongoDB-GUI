# MongoGUI

A very simple GUI for viewing, deleting, adding and updating MongoDB-Databases with PHP without needing to memorize MongoDB's kinda quirky language. 

# Screenshot

![Screenshot](screenshot_alpha.png?raw=true "Screenshot")

# How to chose DB

```console
echo $DBNAME > dbname
echo $COLLNAME > collname
```

# Docker

I'm still working on making docker work. But when it's ready you should be able to run:

```console
bash run.sh $MONGO_DB_IP $MONGO_DB_PORT $MONGO_DB_DB_NAME $MONGO_DB_COLLECTION_NAME $LOCAL_PORT_FOR_THE_GUI
```

# Caveats

The search does not work, yet. Hopefully it will soon.

Thanks to chatGPT for helping writing this script.
