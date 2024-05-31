<?php
require 'com/icemalta/kahuna/util/ApiUtil.php';
require 'com/icemalta/kahuna/model/AccessToken.php';
require 'com/icemalta/kahuna/model/User.php';
require 'com/icemalta/kahuna/model/Product.php';
require 'com/icemalta/kahuna/model/Transaction.php';
require 'com/icemalta/kahuna/model/SupportTicket.php';


use com\icemalta\kahuna\util\ApiUtil;

use com\icemalta\kahuna\model\{AccessToken, User, Product, Transaction, SupportTicket};

cors();

$endPoints = [];
$requestData = [];
header("Content-Type: application/json; charset=UTF-8");

/* BASE URI */
$BASE_URI = '/kahuna/api/';

/* Send Response */
function sendResponse(mixed $data = null, int $code = 200, mixed $error = null): void
{
    if (!is_null($data)) {
        $response['data'] = $data;
    }
    if (!is_null($error)) {
        $response['error'] = $error;
    }
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    http_response_code($code);
}

// Check Token
function checkToken(array $requestData): bool
{
    if (!isset($requestData['token']) || !isset($requestData['user'])) {
        return false;
    }
    $token = new AccessToken($requestData['user'], $requestData['token']);
    return AccessToken::verify($token);
}



/* Get Request Data */
$requestMethod = $_SERVER['REQUEST_METHOD'];
switch ($requestMethod) {
    case 'GET':
        $requestData = $_GET;
        break;
    case 'POST':
        $requestData = $_POST;
        break;
    case 'PATCH':
        parse_str(file_get_contents('php://input'), $requestData);
        ApiUtil::parse_raw_http_request($requestData);
        $requestData = is_array($requestData) ? $requestData : [];
        break;
    case 'DELETE':
        break;
    default:
        sendResponse(null, 405, 'Method not allowed.');
}

/* Extract EndPoint */
$parsedURI = parse_url($_SERVER["REQUEST_URI"]);
$path = explode('/', str_replace($BASE_URI, "", $parsedURI["path"]));
$endPoint = $path[0];
$requestData['dataId'] = isset($path[1]) ? $path[1] : null;
if (empty($endPoint)) {
    $endPoint = "/";
}

/* Extract Token */
if (isset($_SERVER["HTTP_X_API_KEY"])) {
    $requestData["user"] = $_SERVER["HTTP_X_API_USER"];
}
if (isset($_SERVER["HTTP_X_API_KEY"])) {
    $requestData["token"] = $_SERVER["HTTP_X_API_KEY"];
}


/* EndPoint Handlers */
$endpoints["/"] = function (string $requestMethod, array $requestData): void {
    sendResponse('Welcome to Kahuna API!');
};

// Create User Endpoints
$endpoints["user"] = function (string $requestMethod, array $requestData): void {
    if ($requestMethod === 'POST') {
        $email = $requestData['email'];
        $password = $requestData['password'];
        $user = new User($email, $password);
        $user = User::save($user);
        sendResponse($user, 201);
    } else if ($requestMethod === 'PATCH') {
        sendResponse(null, 501, 'Updating a user has not yet been implemented.');
    } else if ($requestMethod === 'DELETE') {
        sendResponse(null, 501, 'Deleting a user has not yet been implemented.');
    } else {
        sendResponse(null, 405, 'Method not allowed.');
    }
};

// Login Endpoint
$endpoints["login"] = function (string $requestMethod, array $requestData): void {
    if ($requestMethod === 'POST') {
        $email = $requestData['email'];
        $password = $requestData['password'];
        $user = new User($email, $password);
        $user = User::authenticate($user);
        if ($user) {
            $token = new AccessToken($user->getId());
            $token = AccessToken::save($token);
            sendResponse(['user' => $user->getId(), 'token' => $token->getToken()]);
        } else {
            sendResponse(null, 401, 'Login failed.');
        }
    } else {
        sendResponse(null, 405, 'Method not allowed.');
    }
};

// Logout Endpoint
$endpoints["logout"] = function (string $requestMethod, array $requestData): void {
    if ($requestMethod === 'POST') {
        if (checkToken($requestData)) {
            $userId = $requestData['user'];
            $token = new AccessToken($userId);
            $token = AccessToken::delete($token);
            sendResponse('You have been logged out.');
        } else {
            sendResponse(null, 403, 'Missing, invalid or expired token.');
        }
    } else {
        sendResponse(null, 405, 'Method not allowed.');
    }
};

// Check Token Endpoint
$endpoints["token"] = function (string $requestMethod, array $requestData): void {
    if ($requestMethod === 'GET') {
        if (checkToken($requestData)) {
            sendResponse(['valid' => true, 'token' => $requestData['token']]);
        } else {
            sendResponse(['valid' => false, 'token' => $requestData['token']]);
        }
    } else {
        sendResponse(null, 405, 'Method not allowed.');
    }
};

// Product Endpoint

$endpoints["product"] = function (string $requestMethod, array $requestData): void {
    // User is logged in
    if ($requestMethod === 'GET') {

        if (checkToken($requestData)) {
        $token = new AccessToken($requestData['user'], $requestData['token']);
        $loggedInUserId = $requestData['user'];

        // Get products for the logged-in user
        $products = Product::getProductListWithRegisteredUser($loggedInUserId);
        sendResponse($products);
        } else { 
            sendResponse(null, 401, 'Unauthorized.');
        }
    } else if ($requestMethod === 'POST') {
        // Check if the user is authenticated
        if (!checkToken($requestData)) {
            sendResponse(null, 401, 'Unauthorized.');
            return;
        }
    
        // Extract request data
        $loggedInUserId = $requestData['user'];
        $serial = $requestData['serial'];
        $name = $requestData['name'];
        $warrantyLength = $requestData['warrantyLength'];
    
        // Check if serial number is allowed
        if (!Product::isAllowedSerial($serial)) {
            sendResponse(null, 400, 'Serial number ' . $serial . ' is not allowed.');
            return;
        }
    
        // Check if product already exists
        if (Product::existsBySerial($serial)) {
            // Check if the product is already registered to the user
            if (Product::isProductRegisteredToLoggedUser($serial, $loggedInUserId)) {
                sendResponse(null, 400, 'You already have this product registered.');
                return;
            }
            // Check if the product is already registered to any user
            if (Product::isProductRegisteredToAnyUser($serial)) {
                sendResponse(null, 400, 'This project is registered to another user.');
                return;
            }
    
            // If the product exists, update it instead of creating a new one
            $existingProduct = Product::getProductBySerial($serial);
            $existingProduct->setName($name);
            $existingProduct->setWarrantyLength($warrantyLength);
            $existingProduct->registerToUser($loggedInUserId);
            $product = Product::save($existingProduct);
            sendResponse($product);
            return; 
        }
    
        // If the product doesn't exist, create a new one
        $product = new Product($serial, $name, $warrantyLength);
        $product->registerToUser($loggedInUserId);
        $product = Product::save($product);
        sendResponse($product);
    } else if ($requestMethod === 'PATCH') {
        // Edit a product
        $serial = $requestData['serial'];
        $name = $requestData['name'];
        $warrantyLength = $requestData['warrantyLength'];
        $id = $requestData['id'];

        // Check if serial number is allowed
        if (!Product::isAllowedSerial($serial)) {
            sendResponse(null, 409, 'Serial number ' . $serial . ' is not allowed.');
            return;
        }

        $product = new Product($serial, $name, $warrantyLength, $id);
        $product = Product::save($product);
        sendResponse($product);
    } else {
        sendResponse(null, 405, 'Method not allowed.');
    }

}; 

// Transaction Endpoint
$endpoints["transaction"] = function (string $requestMethod, array $requestData): void {
    if ($requestMethod === 'GET') {
        // Get transactions
        $transaction = Transaction::load();
        sendResponse($transaction);    
    } else if ($requestMethod === 'POST') {
        $userId = (int) $requestData['userId'];
        $productId = (int) $requestData['productId'];
        $warrantyStartDate = date('Y-m-d');

        $warrantyEndDate = Transaction::calculateWarrantyEndDate($productId);
        
        if (!$warrantyEndDate) {
            sendResponse('Failed to calculate warranty end date.', 400);
            return;
        }

        $purchaseDate = date('Y-m-d');

        $transaction = new Transaction(0, $userId, $productId, $warrantyStartDate, $warrantyEndDate, $purchaseDate);

        if (Transaction::buy($transaction)) {
           sendResponse(['message' => 'Purchase successful', 'warrantyEndDate' => $warrantyEndDate]);
           } else {
                sendResponse('Purchase failed.', 400);
            }  
    }
};

// Support Ticket Endpoints

$endpoints["supportTicket"] = function (string $requestMethod, array $requestData): void {
    // if (checkToken($requestData))
    if ($requestMethod === 'GET') {
        $SupportTicket = SupportTicket::load();
        sendResponse($SupportTicket);
    } else if ($requestMethod === 'POST') {
        $name = $requestData['name'];
        $description = $requestData['description'];
        $SupportTicket = new SupportTicket($name, $description);
        $SupportTicket = SupportTicket::save($SupportTicket);
        sendResponse($SupportTicket, 201);

    } else {
        sendResponse(null, 405, 'Method not allowed.');
    }

};



$endpoints["404"] = function (string $requestMethod, array $requestData): void {
    sendResponse(null, 404, "Endpoint " . $requestData["endPoint"] . " not found.");
};

// Cross Origin Resource Sharing
function cors()
{
    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
        // you want to allow, and if so:
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            // may also be using PUT, PATCH, HEAD etc
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PATCH, DELETE");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }
}

try {
    if (isset($endpoints[$endPoint])) {
        $endpoints[$endPoint]($requestMethod, $requestData);
    } else {
        $endpoints["404"]($requestMethod, array("endPoint" => $endPoint));
    }
} catch (Exception $e) {
    sendResponse(null, 500, $e->getMessage());
} catch (Error $e) {
    sendResponse(null, 500, $e->getMessage());
}