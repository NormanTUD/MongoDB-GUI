<?php
class MongoDBHelper {
	private $mongoClient;
	private $namespace;
	private $enableDebug;

	public function __construct($mongodbHost = "localhost", $mongodbPort = 27017, $databaseName = "test", $collectionName = "Tzwei") {
		$mongoConnectionString = "mongodb://{$mongodbHost}:{$mongodbPort}";
		$this->mongoClient = new MongoDB\Driver\Manager($mongoConnectionString);
		$this->namespace = "{$databaseName}.{$collectionName}";
		$this->enableDebug = 0;
	}

	public function setDebug ($val) {
		$this->enableDebug = ($val === 1 ? 1 : 0);
	}

	private function debug ($msg) {
		if($this->enableDebug) {
			print("=================\n");
			print_r($msg);
			debug_print_backtrace();
			print("\n=================\n");
		}
	}

	private function newBulkWrite () {
		$this->debug("newBulkWrite");
		return new MongoDB\Driver\BulkWrite();
	}

	public function deleteEntry($entryId) {
		$this->debug(["deleteEntry" => $entryId]);
		try {
			$bulkWrite = $this->newBulkWrite();
			try {
				$entryId = $this->createId($entryId);
			} catch (\Throwable $e) {
				$str = "Entry-ID:\n";
				$str .= ($entryId);
				$str .= "\n";
				$str .= "Error:\n";
				$str .= $e;
				return json_encode(['error' => 'Error deleting entry: ' . $e->getMessage()] . "\n$str");
			}

			$id = $this->createId($entryId);

			$filter = ['_id' => $id];
			$bulkWrite->delete($filter);

			$this->executeBulkWrite($bulkWrite);

			return json_encode(['success' => 'Entry deleted successfully.', 'entryId' => $entryId]);
		} catch (Exception $e) {
			return json_encode(['error' => 'Error deleting entry: ' . $e->getMessage(), 'entryId' => $entryId]);
		}
	}

	public function replaceDocument($documentId, $newDocument) {
		$this->debug(["replaceDocument" => ["documentId" => $documentId, "newDocument" => $newDocument]]);
		try {
			// Convert the document ID to MongoDB\BSON\ObjectID if needed

			// Delete the existing document
			$filter = ['_id' => $this->createId($documentId)];
			$this->updateIterateDocument($documentId, $newDocument);

			return json_encode(['success' => 'Document updated successfully.', 'documentId' => $documentId]);
		} catch (Exception $e) {
			return json_encode(['error' => 'Error updating document: ' . $e->getMessage()]);
		}
	}

	private function updateIterateDocument($documentId, $document, $path = '') {
		$this->debug(["updateIterateDocument" => ["documentId" => $documentId, "document" => $document, "path" => $path]]);
		foreach ($document as $key => $value) {
			$currentPath = $path . $key;

			if (is_array($value) || is_object($value)) {
				$this->updateIterateDocument($documentId, $value, $currentPath . '.');
			} else {
				$this->insertValue($documentId, $currentPath, $value);
			}
		}
	}


	public function insertValue($documentId, $key, $value) {
		$this->debug(["insertValue" => ["documentId" => $documentId, "key" => $key, "value" => $value]]);
		$bulkWrite = $this->newBulkWrite();
		if($key == '_id' || $key == '$oid') {
			return json_encode(['warning' => 'Not replacing _id', 'documentId' => $documentId]);
		}

		$filter = ['_id' => $this->createId($documentId)];
		$update = ['$set' => [$key => $value]];

		$bulkWrite->update($filter, $update);

		try {
			$this->executeBulkWrite($bulkWrite);

			return json_encode(['success' => 'Value inserted successfully.', 'documentId' => $documentId]);
		} catch (Exception $e) {
			return json_encode(['error' => 'Error inserting value: ' . $e->getMessage()]);
		}
	}

	private function query ($filter=[], $projection=[]) {
		$this->debug(["query" => ["filter" => $filter, "projection" => $projection]]);
		return new MongoDB\Driver\Query($filter, $projection);
	}

	public function find($filter=[], $projection=[]) {
		$this->debug(["find" => ["filter" => $filter, "projection" => $projection]]);
		$query = $this->query($filter, $projection);

		try {
			$cursor = $this->executeQuery($query);
			$entries = $cursor->toArray();
			return json_decode(json_encode($entries), true);
		} catch (\Throwable $e) {
			return json_encode(["error" => $e]);
		}
	}

	public function insertDocument($document) {
		$this->debug(["insertDocument" => ["document" => $document]]);
		if (!$document) {
			$document = [];
		}
		$bulkWrite = $this->newBulkWrite();
		$entryId = json_decode(json_encode($bulkWrite->insert($this->convertNumericStrings($document))), true);

		try {
			$this->executeBulkWrite($bulkWrite);
			return json_encode(['success' => 'Entry created successfully: '.$entryId['$oid'], 'entryId' => $entryId['$oid']]);
		} catch (Exception $e) {
			return json_encode(['error' => 'Error creating entry: ' . $e->getMessage()]);
		}
	}

	public function getAllEntries() {
		$this->debug("getAllEntries");
		$query = $this->query([]);
		try {
			$cursor = $this->executeQuery($query);
		} catch (\Throwable $e) { // For PHP 7
			$serverIP = $_SERVER['SERVER_ADDR'];
			print "There was an error connecting to MongoDB. Are you sure you bound it to 0.0.0.0?<br>\n";
			print "Try, in <code>/etc/mongod.conf</code>, to change the line\n<br>";
			print "<code>bindIp: 127.0.0.1</code>\n<br>";
			print "or:<br>\n";
			print "<code>bindIp: $serverIP</code>\n<br>";
			print "to\n<br>";
			print "<code>bindIp: 0.0.0.0</code>\n<br>";
			print "and then try sudo service mongod restart";
			print "\n<br>\n<br>\n<br>\n";
			print "Error:<br>\n<br>\n";
			print($e);
		}
		$entries = $cursor->toArray();
		return $entries;
	}

	private function executeBulkWrite($bulkWrite) {
		$this->debug(["executeBulkWrite" => ["bulkWrite" => $bulkWrite]]);
		$this->mongoClient->executeBulkWrite($this->namespace, $bulkWrite);
	}

	public function findById($id) {
		$this->debug(["findById" => ["id" => $id]]);
		$id = $this->createId($id);
		$filter = ['_id' => $id];
		$query = $this->query($filter);
		$cursor = $this->executeQuery($query);

		$res = json_decode(json_encode($cursor->toArray()), true);
		return $res;
	}

	public function executeQuery($query) {
		$this->debug(["executeQuery" => ["query" => $query]]);
		return $this->mongoClient->executeQuery($this->namespace, $query);
	}

	public function createId ($id) {
		$this->debug(["createId" => ["id" => $id]]);
		if (is_array($id) && isset($id['oid'])) {
			$id = $id['oid'];
		}

		if (!$id && is_array($id) && isset($id['$oid'])) {
			$id = $id['$oid'];
		}

		if(!$id) {
			return json_encode(["error" => "Could not get id"]);
		}

		if (is_string($id)) {
			$id = new MongoDB\BSON\ObjectID($id);
		}

		return $id;
	}

	private function convertNumericStrings($data) {
		$this->debug(["convertNumericStrings" => ["data" => $data]]);
		if (is_array($data)) {
			$result = [];
			foreach ($data as $key => $value) {
				$result[$key] = convertNumericStrings($value);
			}
			return $result;
		} elseif (is_object($data)) {
			$result = [];
			foreach ($data as $key => $value) {
				$result[$key] = convertNumericStrings($value);
			}
			return $result;
		} elseif (is_string($data)) {
			if (is_numeric($data)) {
				if (strpos($data, '.') !== false) {
					return floatval($data);
				} else {
					return intval($data);
				}
			}
		}

		return $data;
	}

	public function deleteKey($documentId, $key) {
		$this->debug(["deleteKey" => ["documentId" => $documentId, "key" => $key]]);
		$bulkWrite = new MongoDB\Driver\BulkWrite();

		// Convert the document ID to MongoDB\BSON\ObjectID if needed
		$documentId = $this->createId($documentId);

		// Retrieve the existing document
		$existingDocument = $this->findById($documentId);
		if (!$existingDocument) {
			return json_encode(['error' => 'Document not found.']);
		}

		// Delete the specified key
		unset($existingDocument[$key]);

		// Replace the document with the updated key
		$bulkWrite->replace(['_id' => $documentId], $existingDocument);

		try {
			$this->executeBulkWrite($bulkWrite);
			return json_encode(['success' => 'Key deleted successfully.', 'documentId' => $documentId]);
		} catch (Exception $e) {
			return json_encode(['error' => 'Error deleting key: ' . $e->getMessage()]);
		}
	}
}

$GLOBALS["mdh"] = new MongoDBHelper($GLOBALS["mongodbHost"], $GLOBALS["mongodbPort"], $GLOBALS["databaseName"], $GLOBALS["collectionName"]);
$GLOBALS["mdh"]->setDebug(1);
?>
