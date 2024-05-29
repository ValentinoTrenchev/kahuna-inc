<?php
namespace com\icemalta\kahuna\model;

require_once 'com/icemalta/kahuna/model/DBConnect.php';

use \PDO;
use \JsonSerializable;
use com\icemalta\kahuna\model\DBConnect;

class Product implements JsonSerializable{

  private static $db;
  private int $id = 0;
  private ?int $userId; // Make userId nullable to indicate unregistered products (Chat GPT Help)
  private string $serial = '';

  private string $name = '';

  private int $warrantyLength = 0;

  public function __construct(string $serial, string $name, int $warrantyLength, ?int $userId = null, int $id = 0) {
    $this->id = $id;
    $this->userId = $userId;
    $this->serial = $serial;
    $this->name = $name;
    $this->warrantyLength = $warrantyLength;
    self::$db = DBConnect::getInstance()->getConnection();
}

  public function getId(): int{
    return $this->id;
  }

  public function setId(int $id): self{
    $this->id = $id;
    return $this;
  }

  public function getUserId(): ?int {
    return $this->userId;
  }

  public function setUserId(?int $userId): void{
    $this->userId = $userId;
  }

  public function getSerial(): string{
    return $this->serial;
  }

  public function setSerial(string $serial): self{
    $this->serial = $serial;
    return $this;
  }

  public function getName(): string{
    return $this->name;
  }

  public function setName(string $name): self{
    $this->name = $name;
    return $this;
  }

  public function getWarrantyLength(): int{
    return $this->warrantyLength;
  }

  public function setWarrantyLength(int $warrantyLength): self{
    $this->warrantyLength = $warrantyLength;
    return $this;
  }

  public function registerToUser(int $userId): void {
    $this->userId = $userId;
  }

  public function jsonSerialize(): array{
    return [
      'id' => $this->id,
      'userId' => $this->userId,
      'serial' => $this->serial,
      'name' => $this->name,
      'warrantyLength' => $this->warrantyLength
    ];

    //return get_object_vars($this);
  }


//Chat GPT Help Save

public static function save(Product $product): Product {
  if ($product->getId() === 0) {
      // New Product (insert)
      $sql = 'INSERT INTO Product (serial, name, warrantyLength) VALUES (:serial, :name, :warrantyLength)';
      $sth = self::$db->prepare($sql);
      $sth->bindValue('serial', $product->getSerial());
      $sth->bindValue('name', $product->getName());
      $sth->bindValue('warrantyLength', $product->getWarrantyLength());
      $sth->execute();

      // Assign the product to the user if it has a user ID
      if ($product->getUserId() !== null) {
          $registeredProductSql = 'INSERT INTO RegisteredProducts (userId, serialNumber) VALUES (:userId, :serialNumber)';
          $registeredProductSth = self::$db->prepare($registeredProductSql);
          $registeredProductSth->bindValue('userId', $product->getUserId());
          $registeredProductSth->bindValue('serialNumber', $product->getSerial());
          $registeredProductSth->execute();
      }

      $product->setId(self::$db->lastInsertId());
  } else {
      // Existing Product (update)
      $sql = 'UPDATE Product SET serial = :serial, name = :name, warrantyLength = :warrantyLength WHERE id = :id';
      $sth = self::$db->prepare($sql);
      $sth->bindValue('id', $product->getId());
      $sth->bindValue('serial', $product->getSerial());
      $sth->bindValue('name', $product->getName());
      $sth->bindValue('warrantyLength', $product->getWarrantyLength());
      $sth->execute();

      // Update the RegisteredProducts table if the product is associated with a user
      if ($product->getUserId() !== null) {
          $registeredProductSql = 'REPLACE INTO RegisteredProducts (userId, serialNumber) VALUES (:userId, :serialNumber)';
          $registeredProductSth = self::$db->prepare($registeredProductSql);
          $registeredProductSth->bindValue('userId', $product->getUserId());
          $registeredProductSth->bindValue('serialNumber', $product->getSerial());
          $registeredProductSth->execute();
      }
  }

  return $product;
}


public static function getProductBySerial(string $serial): ?Product {
  self::$db = DBConnect::getInstance()->getConnection();
  $sql = 'SELECT p.id, rp.userId, p.serial, p.name, p.warrantyLength 
          FROM Product p
          LEFT JOIN RegisteredProducts rp ON p.serial = rp.serialNumber
          WHERE p.serial = :serial';
  $sth = self::$db->prepare($sql);
  $sth->bindValue('serial', $serial);
  $sth->execute();
  $productData = $sth->fetch(PDO::FETCH_ASSOC);

  if ($productData) {
      // Create a new Product object using the retrieved data
      return new Product(
          $productData['serial'],
          $productData['name'],
          $productData['warrantyLength'],
          $productData['userId'], // Assuming userId is retrieved from the RegisteredProducts table
          $productData['id']
      );
  } else {
      return null; // Product with the specified serial number not found
  }
}



  //Keith Save
  /*public static function save(Product $product): Product {
        if ($product->getId() === 0) {
            // New Product (insert)
            $sql = 'INSERT INTO Product (serial, name, warrantyLength) VALUES (:serial, :name, :warrantyLength)';
            $sth = self::$db->prepare($sql);
    } else{
      //Existing Product (update)
      $sql = 'UPDATE Product SET serial = :serial, name = :name, warrantyLength = :warrantyLength, userId = :userId WHERE id = :id';
      $sth = self::$db->prepare($sql);
      $sth->bindValue('id', $product->getId());
      $sth->bindValue('userId', $product->getUserId());
    }

    $sth->bindValue('serial', $product->getSerial());
    $sth->bindValue('name', $product->getName());
    $sth->bindValue('warrantyLength', $product->getWarrantyLength());
    $sth->execute();

    if ($sth->rowCount() > 0 && $product->getId() === 0) {
      $product->setId(self::$db->lastInsertId());
    }

    return $product;
  }*/


public static function getProductListWithRegisteredUser(int $userId): array {
  // Get the database connection
  self::$db = DBConnect::getInstance()->getConnection();

  // SQL query to fetch the product list with registered user
  $sql = "SELECT p.id, p.serial, p.name, p.warrantyLength, r.userId
          FROM Product p
          LEFT JOIN RegisteredProducts r ON p.serial = r.serialNumber
          WHERE r.userId = :userId";

  // Prepare and execute the SQL query
  $stmt = self::$db->prepare($sql);
  $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
  $stmt->execute();

  // Fetch the results
  $productList = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Return the product list
  return $productList;
}


public static function isProductRegisteredToLoggedUser(string $serial, int $userId): bool {
  self::$db = DBConnect::getInstance()->getConnection();
  
  $sql = 'SELECT COUNT(*) AS count FROM RegisteredProducts WHERE serialNumber = :serial AND userId = :userId';
  $stmt = self::$db->prepare($sql);
  $stmt->bindValue(':serial', $serial);
  $stmt->bindValue(':userId', $userId);
  
  if ($stmt->execute()) {
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($result && isset($result['count'])) {
          return intval($result['count']) > 0;
      }
  }
  
  // Something went wrong, return false
  return false;
}

public static function isProductRegisteredToAnyUser(string $serial): ?int {
  self::$db = DBConnect::getInstance()->getConnection();
  $sql = 'SELECT userId FROM RegisteredProducts WHERE serialNumber = :serial';
  $stmt = self::$db->prepare($sql);
  $stmt->bindValue('serial', $serial);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  return $result ? intval($result['userId']) : null;
}

  /*KEITH LOAD*/
  public static function load(): array{
    self::$db = DBConnect::getInstance()->getConnection();
    $sql = 'SELECT id, userId, serial, name, warrantyLength FROM Product ORDER BY id DESC';
    
    $sth = self::$db->prepare($sql);
    $sth->execute();
    $products = $sth->fetchAll(PDO::FETCH_FUNC, fn(...$fields) => new Product(...$fields));
    return $products;
  }


  /*VALENTINO LOAD*/

  // public static function load(Product $product): array{
  //   //self::$db = DBConnect::getInstance()->getConnection();
  //   $sql = 'SELECT userId, serial, name, warrantyLength, id FROM Product WHERE userId = :userId ORDER BY birth DESC';
  //   $sth = self::$db->prepare($sql);
  //   $sth->bindValue('userId', $product->getUserId());
  //   $sth->execute();
  //   $products = $sth->fetchAll(PDO::FETCH_FUNC, fn(...$fields) => new Product(...$fields));
  //   return $products;
  // }


  //Check if a product exists by serial number
  public static function existsBySerial(string $serial): bool
  {
      self::$db = DBConnect::getInstance()->getConnection();
      $sql = 'SELECT COUNT(*) FROM Product WHERE serial = :serial';
      $sth = self::$db->prepare($sql);
      $sth->bindValue('serial', $serial);
      $sth->execute();
      $count = $sth->fetchColumn(); 
      return $count > 0; 
  }
  public static function isAllowedSerial(string $serial): bool 
    {
    
        $allowedSerialNumbers = ["KHWM8199911", "KHWM8199912", "KHMW789991", "KHWP890001", "KHWP890002", "KHSS988881", "KHSS988882", "KHSS988883", "KHHM89762", "KHSB0001"];
        return in_array($serial, $allowedSerialNumbers);
    }
}