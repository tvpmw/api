<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Shipment</title>
    <style>
        .result-container {
            border: 1px solid #ccc;
            padding: 10px;
            margin-top: 20px;
            max-width: 600px;
        }
        .status-item {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .status-item .timestamp {
            font-weight: bold;
            color: #333;
        }
        .status-item .status-text {
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <h2>Track Shipment</h2>
    <form method="POST" action="">
        <label for="waybill">Waybill Number:</label><br>
        <input type="text" id="waybill" name="waybill" required><br><br>
        <button type="submit">Track</button>
    </form>
    
    <?php
    // Set timezone to GMT+8
    date_default_timezone_set('Asia/Jakarta');

    function encodedKey($resi): string
    {
        $key  = "0ebfffe63d2a481cf57fe7d5ebdc9fd6"; // key fetched from https://deo.shopeemobile.com/shopee/spx-website-live/static/js/19.192f41e8.chunk.js
        $data = [
            'key'  => base64_encode($key), // MGViZmZmZTYzZDJhNDgxY2Y1N2ZlN2Q1ZWJkYzlmZDY=
            'time' => time() // return unix timestamp code
        ];
    
        $parameter = $resi . "|" . $data['time'] . hash('sha256', ($resi . $data['time'] . $data['key']));
    
        return $parameter;
    }

    function shopeeWaybillTrack($waybill): array
    {
        $waybill  = strtoupper($waybill);
        $encoded_key = urlencode(encodedKey($waybill));
        $curl     = curl_init();
    
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://spx.co.id/api/v2/fleet_order/tracking/search?sls_tracking_number=$encoded_key",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => array(
                "accept: application/json, text/plain, */*",
                "accept-language: en-US,en;q=0.8",
                "cookie: fms_language=id",
                "priority: u=1, i",
                "referer: https://spx.co.id/",
                "sec-ch-ua: \"Not/A)Brand\";v=\"8\", \"Chromium\";v=\"126\", \"Brave\";v=\"126\"",
                "sec-ch-ua-mobile: ?0",
                "sec-ch-ua-platform: \"Windows\"",
                "sec-fetch-dest: empty",
                "sec-fetch-mode: cors",
                "sec-fetch-site: same-origin",
                "sec-gpc: 1",
                "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36"
            ),
        ));
    
        $response = curl_exec($curl);
        $err      = curl_error($curl);
    
        curl_close($curl);
    
        return $err ? ['error' => $err] : json_decode($response, true);
    }

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $waybill = $_POST["waybill"];
        $result = shopeeWaybillTrack($waybill);
        
        // Display results
        if (isset($result['data']) && !empty($result['data'])) {
            echo "<div class='result-container'>";
            echo "<h3>Tracking Result:</h3>";
            echo "<p><strong>Waybill Number:</strong> " . htmlspecialchars($result['data']['sls_tracking_number']) . "</p>";
            echo "<p><strong>Recipient:</strong> " . htmlspecialchars($result['data']['recipient_name']) . "</p>";
            echo "<p><strong>Phone:</strong> " . htmlspecialchars($result['data']['phone']) . "</p>";
            echo "<h4>Tracking List:</h4>";
            foreach ($result['data']['tracking_list'] as $tracking) {
                echo "<div class='status-item'>";
                echo "<p class='timestamp'>Timestamp: " . date('n/j/Y, g:i:s A', $tracking['timestamp']) . "</p>";
                echo "<p class='status-text'><strong>Status:</strong> " . htmlspecialchars($tracking['status']) . "</p>";
                echo "<p><strong>Message:</strong> " . htmlspecialchars($tracking['message']) . "</p>";
                echo "</div>";
            }
        } else {
            echo "<p>Tracking information not found. Please check the waybill number and try again.</p>";
        }
    }
    ?>
</body>
</html>
