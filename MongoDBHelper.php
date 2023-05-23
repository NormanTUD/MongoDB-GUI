<?php
class MongoDBHelper {
	private $mongoClient;
	private $namespace;

	public function __construct($mongodbHost = "localhost", $mongodbPort = 27017, $databaseName = "test", $collectionName = "Tzwei") {
		$mongoConnectionString = "mongodb://{$mongodbHost}:{$mongodbPort}";
		$this->mongoClient = new MongoDB\Driver\Manager($mongoConnectionString);
		$this->namespace = "{$databaseName}.{$collectionName}";
	}

	private function newBulkWrite () {
		return new MongoDB\Driver\BulkWrite();
	}

	public function deleteEntry($entryId) {
		try {
			$bulkWrite = $this->newBulkWrite();
			try {
				$entryId = $this->createId($entryId);
			} catch (\Throwable $e) {
				print "Entry-ID:\n";
				print($entryId);
				print "\n";
				print "Error:\n";
				print($e);
				return json_encode(['error' => 'Error deleting entry: ' . $e->getMessage()]);
			}

			$id = $this->createId($entryId);

			$filter = ['_id' => $id];
			$bulkWrite->delete($filter);

			$this->executeBulkWrite($bulkWrite);

			return json_encode(['success' => 'Entry deleted successfully.', 'entryId' => $entryId]);
		} catch (Exception $e) {
			return json_encode(['error' => 'Error deleting entry: ' . $e->getMessage()]);
		}
	}

	public function replaceDocument($documentId, $newDocument) {
		try {
			// Convert the document ID to MongoDB\BSON\ObjectID if needed

			// Delete the existing document
			$filter = ['_id' => $this->createId($documentId)];
			$this->updateIterateDocument($documentId, $newDocument);

			return json_encode(['success' => 'Document replaced successfully.', 'documentId' => $documentId]);
		} catch (Exception $e) {
			return json_encode(['error' => 'Error replacing document: ' . $e->getMessage()]);
		}
	}

	private function updateIterateDocument($documentId, $document, $path = '') {
		foreach ($document as $key => $value) {
			if (is_array($value)) {
				$this->updateIterateDocument($documentId, $value, $path . $key . '.');
			} else {
				$this->insertValue($documentId, $path . $key, $value);
			}
		}
	}


	public function insertValue($documentId, $key, $value) {
		$bulkWrite = $this->newBulkWrite();
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
		return new MongoDB\Driver\Query($filter, $projection);
	}

	public function find($filter=[], $projection=[]) {
		$query = $this->query($filter, $projection);

		try {
			$cursor = $this->executeQuery($query);
			$entries = $cursor->toArray();
			return json_decode(json_encode($entries), true);
		} catch (\Throwable $e) {
			die($e);
		}
	}

	public function insertDocument($document) {
		if ($document) {
			$bulkWrite = $this->newBulkWrite();
			$bulkWrite->insert($this->convertNumericStrings($document));

			try {
				$this->executeBulkWrite($bulkWrite);
				return json_encode(['success' => 'Entry created successfully.']);
			} catch (Exception $e) {
				return json_encode(['error' => 'Error creating entry: ' . $e->getMessage()]);
			}
		} else {
			die("Document not defined in insertDocument");
		}
	}

	public function getAllEntries() {
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
		$this->mongoClient->executeBulkWrite($this->namespace, $bulkWrite);
	}

	public function findById($id) {
		$id = $this->createId($id);
		$filter = ['_id' => $id];
		$query = $this->query($filter);
		$cursor = $this->executeQuery($query);

		$res = json_decode(json_encode($cursor->toArray()), true);
		return $res;
	}

	public function executeQuery($query) {
		return $this->mongoClient->executeQuery($this->namespace, $query);
	}

	public function createId ($id) {
		if (is_array($id) && isset($id['oid'])) {
			$id = $id['oid'];
		}

		if (!$id && is_array($id) && isset($id['$oid'])) {
			$id = $id['$oid'];
		}

		if(!$id) {
			die("Could not get id");
		}

		if (is_string($id)) {
			$id = new MongoDB\BSON\ObjectID($id);
		}

		return $id;
	}

	private function convertNumericStrings($data) {
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
?>
