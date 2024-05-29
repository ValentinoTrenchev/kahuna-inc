<?php
namespace com\icemalta\kahuna\model;

require_once 'com/icemalta/kahuna/model/DBConnect.php';

use \JsonSerializable;
use \PDO;
use com\icemalta\kahuna\model\DBConnect;

class AccessToken implements JsonSerializable
{
  private static $db;
  private int $userId;
  private string $token;

  public function __construct(int $userId, ?string $token = null)
  {
      $this->userId = $userId;
      $this->token = $token ?? str_replace("=", "", base64_encode(random_bytes(160 / 8)));
      self::$db = DBConnect::getInstance()->getConnection();
  }

  public static function save(AccessToken $accessToken): AccessToken
  {
      $sql = 'INSERT INTO AccessToken(userId, token) VALUES (:userId, :token)';
      $sth = self::$db->prepare($sql);
      $sth->bindValue('userId', $accessToken->getUserId());
      $sth->bindValue('token', $accessToken->getToken());
      $sth->execute();
      return $accessToken;
  }

  public static function verify(AccessToken $accessToken): bool
  {
      $sql = 'SELECT * FROM AccessToken WHERE userId = :userId AND token = :token';
      $sth = self::$db->prepare($sql);
      $sth->bindValue('userId', $accessToken->getUserId());
      $sth->bindValue('token', $accessToken->getToken());
      $sth->execute();
      $token = $sth->fetch(PDO::FETCH_OBJ);

      if ($token) {
          $birth = strtotime($token->birth);
          $age = abs(strtotime('now') - $birth);
          if ($age < 3600) {
              return true;
          }
      }
      return false;
  }

  public static function delete(AccessToken $accessToken): bool
  {
      $sql = 'DELETE FROM AccessToken WHERE userId = :userId';
      $sth = self::$db->prepare($sql);
      $sth->bindValue('userId', $accessToken->getUserId());
      $sth->execute();
      return $sth->rowCount() > 0;
  }

  public function jsonSerialize(): array
  {
      return [
          'token'=> $this->token
      ];
  }

  public function getUserId(): int
  {
      return $this->userId;
  }

  public function setUserId(int $userId): self
  {
      $this->userId = $userId;
      return $this;
  }

  public function getToken(): string
  {
      return $this->token;
  }
}