<?php
$ch = curl_init('http://localhost:8080/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP Status: $code\n";
echo substr($body, 0, 3000);
