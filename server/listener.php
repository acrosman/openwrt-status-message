<?php
// Basic authentication credentials
$valid_username = 'your_username_here';
$valid_password = 'your_password_here';

// Function to send authentication headers
function send_auth_headers()
{
    header('WWW-Authenticate: Basic realm="Restricted Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Unauthorized';
    exit;
}

// Check if the user is authenticated
if (! isset($_SERVER['PHP_AUTH_USER']) || ! isset($_SERVER['PHP_AUTH_PW'])) {
    send_auth_headers();
} else {
    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];

    if ($username !== $valid_username || $password !== $valid_password) {
        send_auth_headers();
    }
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

    $wifi_status     = $data['wifi_status'];
    $wifi_radio      = array_keys($wifi_status)[0] ?? 'N/A';
    $wifi_interfaces = $wifi_status[$wifi_radio]['interfaces'];

    $wifi_ssid_1 = $wifi_interfaces[0]['config']['ssid'] ?? 'N/A';
    $wifi_ssid_2 = isset($wifi_interfaces[1]) ? $wifi_interfaces[1]['config']['ssid'] : 'N/A';

    // Open the CSV file for appending
    $file = fopen('data.csv', 'a');

    // Write the data to the CSV file
    if ($file) {
        fputcsv($file, [
            $timestamp,
            $wan_up,
            $wan_ip,
            $wifi_radio,
            $wifi_ssid_1,
            $wifi_ssid_2,
        ]);
        fclose($file);
    }
}

// Function to display the last 100 lines of the CSV file
function display_last_100_lines()
{
    $lines = [];
    $file  = fopen('data.csv', 'r');

    if ($file) {
        // Read the header line
        $header = fgetcsv($file);

        while (($line = fgetcsv($file)) !== false) {
            $lines[] = $line;
            if (count($lines) > 100) {
                array_shift($lines);
            }
        }
        fclose($file);
    }

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

    echo '<style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid black; padding: 8px; text-align: left; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .down { color: red; }
          </style>';

    echo '<table>';
    echo '<tr><th>Timestamp</th><th>WAN Status</th><th>WAN IP</th><th>WiFi Radio</th><th>WiFi SSID 1</th><th>WiFi SSID 2</th></tr>';
    foreach ($lines as $line) {
        $status_class = $line[1] === 'down' ? 'down' : '';
        echo '<tr>';
        echo '<td>' . htmlspecialchars($line[0]) . '</td>';
        echo '<td class="' . $status_class . '">' . htmlspecialchars($line[1]) . '</td>';
        echo '<td>' . htmlspecialchars($line[2]) . '</td>';
        echo '<td>' . htmlspecialchars($line[3]) . '</td>';
        echo '<td>' . htmlspecialchars($line[4]) . '</td>';
        echo '<td>' . htmlspecialchars($line[5]) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handle_post_request();
    http_response_code(200);
    echo 'Data received and recorded.';
    exit;
}

// Display the last 100 lines of the CSV file on a GET request
display_last_100_lines();
