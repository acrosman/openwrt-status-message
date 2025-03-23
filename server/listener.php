<?php
// Pre-shared token
$valid_token = 'your_pre_shared_token_here';

// Function to send unauthorized response
function send_unauthorized_response()
{
    header('HTTP/1.0 401 Unauthorized');
    echo 'Unauthorized';
    exit;
}

// Function to check the token for POST requests
function check_post_token()
{
    global $valid_token;

    if (! isset($_SERVER['HTTP_LISTENER_AUTH'])) {
        return false;
    }

    $token = $_SERVER['HTTP_LISTENER_AUTH'];
    return $token == $valid_token;
}

// Function to check the token for GET requests
function check_get_token()
{
    global $valid_token;

    if (! isset($_GET['token'])) {
        return false;
    }
    return $valid_token == $_GET['token'];

}

// Function to handle incoming POST requests
function handle_post_request()
{
    // Get the posted JSON data
    $json_data = file_get_contents('php://input');
    $data      = json_decode($json_data, true);

    // Extract important details
    $timestamp  = $data['timestamp'];
    $wan_status = $data['wan_status'];
    $wan_up     = $wan_status['up'] ? 'up' : 'down';
    $wan_ip     = $wan_status['ipv4-address'][0]['address'] ?? 'N/A';

    $wifi_status  = $data['wifi_status'];
    $wifi_status0 = $wifi_status['radio0']['up'] ? 'up' : 'down';
    $wifi_status1 = $wifi_status['radio1']['up'] ? 'up' : 'down';

    // Open the CSV file for appending
    $file = fopen('data.csv', 'a');

    // Write the data to the CSV file
    if ($file) {
        fputcsv($file, [
            $timestamp,
            $wan_up,
            $wan_ip,
            $wifi_status0,
            $wifi_status1,
        ]);
        fclose($file);
        return true;
    }

    // Return false if the file could not be opened
    echo 'Failed to open the file';
    return false;

}

// Function to display the last 100 lines of the CSV file
function display_last_100_lines()
{
    $lines = [];
    $file  = fopen('data.csv', 'r');

    if ($file) {
        while (($line = fgetcsv($file)) !== false) {
            $lines[] = $line;
            if (count($lines) > 100) {
                array_shift($lines);
            }
        }
        fclose($file);
    }

    ob_start();
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Home WAN Status</title>';
    echo '<style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid black; padding: 8px; text-align: left; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .down { color: red; }
            .up { color: green; }</style>';
    echo '</head>';
    echo '<body>';
    echo '<h1>Home WAN Status</h1>';

    // Display the "Up" message if the latest WAN status is up
    if (! empty($lines)) {
        $latest_record = end($lines);
        if ($latest_record[1] === 'up') {
            echo '<h2>Up</h2>';
        } else {
            echo '<h2>Down</h2>';
        }
    } else {
        echo '<h2>No Valid Data</h2>';
    }

    echo '<table>';
    echo '<tr><th>Timestamp</th><th>WAN Status</th><th>WAN IP</th><th>WiFi Radio 0 Status</th><th>WiFi Radio 1 Status</th></tr>';
    foreach ($lines as $line) {
        $status_class1 = $line[1] === 'up' ? 'up' : 'down';
        $status_class2 = $line[1] === 'up' ? 'up' : 'down';
        $status_class3 = $line[1] === 'up' ? 'up' : 'down';
        echo '<tr>';
        echo '<td>' . htmlspecialchars($line[0]) . '</td>';
        echo '<td class="' . $status_class1 . '">' . htmlspecialchars($line[1]) . '</td>';
        echo '<td>' . htmlspecialchars($line[2]) . '</td>';
        echo '<td class="' . $status_class2 . '">' . htmlspecialchars($line[3]) . '</td>';
        echo '<td class="' . $status_class3 . '">' . htmlspecialchars($line[4]) . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    $output = ob_get_clean();
    echo $output;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (check_post_token()) {
        if (handle_post_request()) {
            http_response_code(200);
            echo 'Data received and recorded.';
            exit;
        } else {
            http_response_code(500);
            echo 'Error recording data.';
            exit;
        }
    } else {
        send_unauthorized_response();
    }
    exit;
}

// Check if it's a GET request
if (check_get_token()) {
    display_last_100_lines();
    exit;
} else {
    send_unauthorized_response();
}
