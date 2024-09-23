<?php
header("Content-Type: text/plain");

$file = 'conversation_state.json';
$state = base64_decode("eyJwcm9tcHQiOiJ7XCJoaXN0b3J5XCI6W3tcInJvbGVcIjpcInVzZXJcIixcImNvbnRlbnRcIjpcImhpIGNoYXRncHQsIGkgd2FudCB5b3UgdG8gcmVwbHkgdXNpbmcgaW5kb25lc2lhbiBpZiBwb3NzaWJsZVwifSx7XCJyb2xlXCI6XCJzeXN0ZW1cIixcImNvbnRlbnRcIjpcIlRlbnR1LCBzYXlhIGJpc2EgbWVuamF3YWIgbWVuZ2d1bmFrYW4gYmFoYXNhIEluZG9uZXNpYS4gU2lsYWthbiBhanVrYW4gcGVydGFueWFhbiBBbmRhLlwifV19IiwiaGlzdG9yeSI6W3sicm9sZSI6InVzZXIiLCJjb250ZW50IjoiaGkgY2hhdGdwdCwgaSB3YW50IHlvdSB0byByZXBseSB1c2luZyBpbmRvbmVzaWFuIGlmIHBvc3NpYmxlIn0seyJyb2xlIjoic3lzdGVtIiwiY29udGVudCI6IlRlbnR1LCBzYXlhIGJpc2EgbWVuamF3YWIgbWVuZ2d1bmFrYW4gYmFoYXNhIEluZG9uZXNpYS4gU2lsYWthbiBhanVrYW4gcGVydGFueWFhbiBBbmRhLiJ9XX0=");

if (isset($_GET['tanya'])) {
    $tanya = $_GET['tanya'];
} else {
    echo "## Chat AI (based from cursor.sh)\n";
    echo "# usage : ".basename($_SERVER['SCRIPT_FILENAME'])."?tanya=pertanyaan";
    die();
}
if (isset($_GET['cookie'])) {
    $file = basename($_COOKIE['ai']);
    $cookie = "/tmp/tempku/".$file;
    if (!file_exists("/tmp/tempku")) {
        @mkdir("/tmp/tempku");
    }
    if (!file_exists($cookie)) {
        file_put_contents($cookie, base64_decode($default));
    }
}

// Function to get the current state
function getCurrentState($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        return json_decode($content, true);
    }
    return ["prompt" => "{\"history\":[]}", "history" => []];
}

// Function to manually construct the prompt JSON string
function constructPromptJson($history) {
    $historyItems = [];
    foreach ($history as $item) {
        $historyItems[] = '{"role":"' . $item['role'] . '","content":"' . addslashes($item['content']) . '"}';
    }
    return '{"history":[' . implode(',', $historyItems) . ']}';
}

// Function to update the state
function updateState($currentState, $newUserContent, $newSystemContent) {
    // Add the new user and system responses to 'prompt' history
    $decodedPromptHistory = json_decode($currentState['prompt'], true)['history'];
    $decodedPromptHistory[] = ["role" => "user", "content" => $newUserContent];
    $decodedPromptHistory[] = ["role" => "system", "content" => $newSystemContent];

    // Also add these to the main 'history' array
    $currentState['history'][] = ["role" => "user", "content" => $newUserContent];
    $currentState['history'][] = ["role" => "system", "content" => $newSystemContent];

    // Manually construct the prompt JSON string
    $currentState['prompt'] = constructPromptJson($decodedPromptHistory);

    return $currentState;
}

function updateStateUser($currentState, $newUserContent) {
    // Add the new user and system responses to 'prompt' history
    $decodedPromptHistory = json_decode($currentState['prompt'], true)['history'];
    $decodedPromptHistory[] = ["role" => "user", "content" => $newUserContent];

    // Manually construct the prompt JSON string
    $currentState['prompt'] = constructPromptJson($decodedPromptHistory);

    return $currentState;
}

function updateStateSystem($currentState, $newUserContent, $newSystemContent) {
    // Add the new user and system responses to 'prompt' history
    $decodedPromptHistory = json_decode($currentState['prompt'], true)['history'];
    $decodedPromptHistory[] = ["role" => "system", "content" => $newSystemContent];

    // Also add these to the main 'history' array
    $currentState['history'][] = ["role" => "user", "content" => $newUserContent];
    $currentState['history'][] = ["role" => "system", "content" => $newSystemContent];

    // Manually construct the prompt JSON string
    $currentState['prompt'] = constructPromptJson($decodedPromptHistory);

    return $currentState;
}

function sendCursorShRequest($postData) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://cursor.sh/api/chat/stream');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $headers = array();
    $headers[] = 'Authority: cursor.sh';
    $headers[] = 'Accept: */*';
    $headers[] = 'Accept-Language: en-US,en;q=0.9';
    $headers[] = 'Content-Type: text/plain;charset=UTF-8';
    $headers[] = 'Origin: https://cursor.sh';
    $headers[] = 'Referer: https://cursor.sh/';
    $headers[] = 'Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"';
    $headers[] = 'Sec-Ch-Ua-Mobile: ?0';
    $headers[] = 'Sec-Ch-Ua-Platform: "Linux"';
    $headers[] = 'Sec-Fetch-Dest: empty';
    $headers[] = 'Sec-Fetch-Mode: cors';
    $headers[] = 'Sec-Fetch-Site: same-origin';
    $headers[] = 'User-Agent: Mozilla/5.0 (iPad; CPU OS 15_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/98.0.4758.85 Mobile/15E148 Safari/604.1';
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);

    return $result;
}

if (isset($cookie) && file_exists($cookie)) {
    # code...
} else {
    $coba = updateStateUser(json_decode($state, true), $tanya);
    $ai = sendCursorShRequest(json_encode($coba));
    if (empty($ai)) {
        $array = array("status" => false);
        echo json_encode($array);
    } else {
        $array = array("status" => true, "content" => $ai);
        echo json_encode($array);
    }
}
die();

$currentState = getCurrentState($file);
$updateuser = updateStateUser($currentState, $pertanyaan);
file_put_contents($file, json_encode($updateuser));
$test = sendCursorShRequest(file_get_contents($file));
if (strlen($test) < 10) {
    file_put_contents($file, json_encode($currentState));
    die("Error Result !");
}
echo $test;
@file_put_contents($file, json_encode(updateStateSystem($updateuser, $pertanyaan, $test)));

die();

// Example usage
$newUserContent = "coba saja dahulu";
$newSystemContent = "Sebagai AI, saya akan mencoba.";

// Get the current state
$currentState = getCurrentState($file);

// Update the state with the new content
$updatedState = updateState($currentState, $newUserContent, $newSystemContent);

$updateStateUser = updateStateUser($updatedState, $newUserContent);

// Save the updated state to the file
file_put_contents($file, json_encode($updateStateUser));

// Output for demonstration
echo '<pre>';
print_r($updatedState);
echo '</pre>';
?>
