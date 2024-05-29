<?php

namespace com\icemalta\kahuna\model;

use \JsonSerializable;
use \PDO;
use com\icemalta\kahuna\model\DBConnect;

class Transaction implements JsonSerializable
{

  private static $db;
  private int $transactionId;
  private int $userId;
  private int $productId;
  private string $warranty_start_date;
  private string $warranty_end_date;
  private string $purchase_date;

  public function __construct(int $transactionId, int $userId, int $productId, string $warranty_start_date, string $warranty_end_date, string $purchase_date)
  {
    $this->transactionId = $transactionId;
    $this->userId = $userId;
    $this->productId = $productId;
    $this->warranty_start_date = $warranty_start_date;
    $this->warranty_end_date = $warranty_end_date;
    $this->purchase_date = $purchase_date;
    self::$db = DBConnect::getInstance()->getConnection();
  }

  public function getTransactionId(): int
  {
    return $this->transactionId;
  }

  public function setTransactionId(int $transactionId): void
  {
    $this->transactionId = $transactionId;
  }

  public function getUserId(): int
  {
    return $this->userId;
  }

  public function setUserId(int $userId): void
  {
    $this->userId = $userId;
  }

  public function getProductId(): int
  {
    return $this->productId;
  }

  public function setProductId(int $productId): void
  {
    $this->productId = $productId;
  }

  public function getWarrantyStartDate(): string
  {
    return $this->warranty_start_date;
  }

  public function setWarrantyStartDate(string $warranty_start_date): void
  {
    $this->warranty_start_date = $warranty_start_date;
  }

  public function getWarrantyEndDate(): string
  {
    return $this->warranty_end_date;
  }

  public function setWarrantyEndDate(string $warranty_end_date): void
  {
    $this->warranty_end_date = $warranty_end_date;
  }

  public function getPurchaseDate(): string
  {
    return $this->purchase_date;
  }

  public function setPurchaseDate(string $purchase_date): void
  {
    $this->purchase_date = $purchase_date;
  }

  public function jsonSerialize(): array
  {
    return [
      'transactionId' => $this->transactionId,
      'userId' => $this->userId,
      'productId' => $this->productId,
      'warranty_start_date' => $this->warranty_start_date,
      'warranty_end_date' => $this->warranty_end_date,
      'purchase_date' => $this->purchase_date
    ];
  }

  public static function buy(Transaction $transaction): bool
  {
    $sql = 'INSERT INTO Transaction(user_id, product_id, warranty_start_date, warranty_end_date, purchase_date) VALUES (:userId, :productId, :warranty_start_date, :warranty_end_date, :purchase_date)';
    $stmt = self::$db->prepare($sql);
    
    //Assign Values
    $userId = $transaction->getUserId();
    $productId = $transaction->getProductId();
    $warranty_start_date = $transaction->getWarrantyStartDate();
    $warranty_end_date = $transaction->getWarrantyEndDate();
    $purchase_date = $transaction->getPurchaseDate();
    
    //Bind Values
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
    $stmt->bindParam(':warranty_start_date', $warranty_start_date, PDO::PARAM_STR);
    $stmt->bindParam(':warranty_end_date', $warranty_end_date, PDO::PARAM_STR);
    $stmt->bindParam(':purchase_date', $purchase_date, PDO::PARAM_STR);

    try {
      $stmt->execute();
      return true;
    } catch (\PDOException $e) {
      error_log("Purchase failed: " . $e->getMessage());
      return false;
    }
  }

  public static function calculateWarrantyEndDate(int $productId): string
  {
    $db = DBConnect::getInstance()->getConnection();

    $sql = 'SELECT warrantyLength FROM Product WHERE id = :productId';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
    $stmt->execute();

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $warrantyLength = $row['warrantyLength'];
      $purchaseDate = date('Y-m-d');
      $warrantyEndDate = date('Y-m-d', strtotime($purchaseDate . ' + ' . $warrantyLength . ' years'));
      return $warrantyEndDate;
    } else {
      error_log("Warranty Length not found for product id: $productId");
      return '';
    }
  }

  public static function load(): array
  {
    self::$db = DBConnect::getInstance()->getConnection();

    $sql = 'SELECT transaction_id, user_id, product_id, warranty_start_date, warranty_end_date, purchase_date FROM Transaction';
    $sth = self::$db->prepare($sql);
    $sth->execute();
    $transactions = $sth->fetchAll(PDO::FETCH_FUNC, fn(...$fields) => new Transaction(...$fields));
    return $transactions;
  }
}