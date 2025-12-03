<!-- api/nutrition.php -->
<?php
header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
if (empty($query)) {
    echo json_encode([]);
    exit();
}

$api_key = '792a3dcf31e84b55b3fb3cec95e24a02';  // ← CHANGE THIS!
$url = "https://api.spoonacular.com/food/products/search?query=$query&apiKey=$api_key";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>