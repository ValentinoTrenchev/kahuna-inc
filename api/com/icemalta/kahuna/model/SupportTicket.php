<?php
namespace com\icemalta\kahuna\model;

use \JsonSerializable;
use \PDO;
use com\icemalta\kahuna\model\DBConnect;

class SupportTicket implements JsonSerializable
{

  private static $db;
  private int $id;
  private string $name;
  private string $description;

  public function __construct(string $name, string $description, int $id = 0)
  {
    $this->name = $name;
    $this->description = $description;
    $this->id = $id;    
    self::$db = DBConnect::getInstance()->getConnection();
  }

  public function getId(): int
  {
    return $this->id;
  }

  public function setId(int $id): void
  {
    $this->id = $id;
  }

  public function getName(): string
  {
      return $this->name;
  }

  public function setName(string $name): void
  {
      $this->name = $name;
  }

  public function getDescription(): string
  {
      return $this->description;
  }

  public function setDescription(string $description): void
  {
      $this->description = $description;
  }

  public function jsonSerialize(): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'description' => $this->description
    ];
  }

  public static function save(SupportTicket $SupportTicket): SupportTicket
  {
      if ($SupportTicket->getId() == 0) {
          // New SupportTicket (insert)
          $sql = 'INSERT INTO SupportTicket(name, description) VALUES (:name, :description)';
          $sth = self::$db->prepare($sql);
    } else{
      //Update SupportTicket(update)
      $sql = 'UPDATE SupportTicket SET name = :name, description = :description WHERE id = :id';
      $sth = self::$db->prepare($sql);
      $sth->bindValue('id', $SupportTicket->getId());
    }

      $sth->bindValue('name', $SupportTicket->getName());
      $sth->bindValue('description', $SupportTicket->getDescription());
      $sth->execute();

      if($sth->rowCount() > 0 && $SupportTicket->getId() === 0){
          $SupportTicket->setId(self::$db->lastInsertId());
      }

      return $SupportTicket;

  }

  public static function load(): array
    {
        self::$db = DBConnect::getInstance()->getConnection();
        $sql ='SELECT name, description, id FROM SupportTicket';
        $sth = self::$db->prepare($sql);
        $sth->execute();
        $products = $sth->fetchAll(PDO::FETCH_FUNC, fn(...$fields) => new SupportTicket(...$fields));
        return $products;
    }
  
}