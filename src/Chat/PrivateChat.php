<?php

namespace Chat;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class PrivateChat implements MessageComponentInterface
{
  protected $clients;
  private $list = [];

  public function __construct()
  {
    $this->clients = new \SplObjectStorage;
  }

  public function onOpen(ConnectionInterface $conn)
  {
    // Store the new connection to send messages to later
    $queryString = $conn->WebSocket->request->getQuery()->toArray();
    $userId = $queryString["userId"];
    $this->list[$conn->resourceId] = $conn;
    // print_r($queryString->data["getID"]);
    $this->clients->attach($conn);
    echo "New connection! ({$conn->resourceId})\n";
  }

  public function onMessage(ConnectionInterface $from, $msg)
  {
    $queryString = $from->WebSocket->request->getQuery()->toArray();
    $msg = json_decode($msg, true);
    $numRecv = count($this->clients) - 1;
    foreach ($this->clients as $client) {
      if ($from !== $client) {
        // The sender is not the receiver, send to each client connected
        $client->send(json_encode(["data" => $msg["message"], "resourceId" => $from->resourceId]));
      }
    }
  }

  public function msgToUser($msg, $id)
  {
    $this->list[$id]->send($msg);
  }

  public function onClose(ConnectionInterface $conn)
  {
    // The connection is closed, remove it, as we can no longer send it messages
    // $this->clients->detach($conn);
    // echo "Connection {$conn->resourceId} has disconnected\n";
    $a = array();
    $b = array();

    //Gets all the client Ids
    foreach ($this->clients as $client) {
      array_push($a, $client->resourceId);
    }

    //Deletes the disconnected client
    $this->clients->detach($conn);

    //Gets all the new client Ids
    foreach ($this->clients as $client) {
      array_push($b, $client->resourceId);
    }

    //array is made that includes the disconnceted client id by comparing both arrays made earlier
    $closedClientArray = array_diff($a, $b);

    //Client id is extracted from array and put in variable as a string
    $closeClientString = $closedClientArray[array_keys($closedClientArray)[0]];
    print_r($closeClientString);
  }

  public function onError(ConnectionInterface $conn, \Exception $e)
  {
    echo "An error has occurred: {$e->getMessage()}\n";
    $conn->close();
  }
}
