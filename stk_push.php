<?php
header('Content-Type: application/json');

// Daraja API Credentials
$consumerKey = "GLGksYWRsGxbJpqAA1LDHecr1hzPlyW8o2CwtfuIc170y3Lu";
$consumerSecret = "PKTZ48xxpGFyQzy20zLLtYj5aOcyerOCZqVJBEiw0yihUH1p0BpeL03WQ5VPJPzH";
$businessShortCode = "0112947880";
$passkey = "YOUR_PASSKEY_HERE"; // Replace with your Safaricom Passkey
$callbackUrl = "https://yourwebsite.com/callback.php"; // Replace with your callback URL

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$phone = $data['phone'];
$amount = $data['amount'];

// Generate access token
$credentials = base64_encode($consumerKey . ":" . $consumerSecret);
$tokenUrl = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . $credentials]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
$tokenData = json_decode($response, true);
$accessToken = $tokenData['access_token'];
curl_close($ch);

// STK Push Request
$timestamp = date('YmdHis');
$password = base64_encode($businessShortCode . $passkey . $timestamp);
$stkUrl = "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest";
$payload = [
    "BusinessShortCode" => $businessShortCode,
    "Password" => $password,
    "Timestamp" => $timestamp,
    "TransactionType" => "CustomerPayBillOnline",
    "Amount" => $amount,
    "PartyA" => $phone,
    "PartyB" => $businessShortCode,
    "PhoneNumber" => $phone,
    "CallBackURL" => $callbackUrl,
    "AccountReference" => "EarnAsYouWrite",
    "TransactionDesc" => "Account Activation Fee"
];

$ch = curl_init($stkUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $accessToken,
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

// Response to frontend
if (isset($result['ResponseCode']) && $result['ResponseCode'] == "0") {
    echo json_encode(["success" => true, "message" => "STK Push initiated"]);
} else {
    echo json_encode(["success" => false, "message" => $result['errorMessage'] ?? "Unknown error"]);
}
?>

