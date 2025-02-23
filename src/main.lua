-- Ensure that the luasocket, luasec, and lua-dkjson libraries are installed on your OpenWrt device.
-- You can install them using the following commands:
-- opkg update && opkg install luasocket luasec lua-dkjson

local http = require("socket.http")
local ltn12 = require("ltn12")
local json = require("dkjson") -- Make sure to install lua-dkjson if not already installed

-- Function to get the status of a network interface using ubus
local function get_interface_status(interface)
    local handle = io.popen("ubus call network.interface." .. interface .. " status")
    local result = handle:read("*a")
    handle:close()
    return json.decode(result)
end

-- Function to get the status of wifi radios using ubus
local function get_wifi_status()
    local handle = io.popen("ubus call network.wireless status")
    local result = handle:read("*a")
    handle:close()
    return json.decode(result)
end

local api_url = "https://spinningcode.org/api/endpoint"

-- Get the status of wan and wifi radios
local wan_status = get_interface_status("wan")
local wifi_status = get_wifi_status()

local json_data = {
    wan_status = wan_status,
    wifi_status = wifi_status,
    timestamp = os.date("!%Y-%m-%d %H:%M:%S") -- Adding a timestamp for reference
}

-- Convert Lua table to JSON string
local json_string = json.encode(json_data)

-- Function to send a POST request
local function send_post_request(url, data)
    local response_body = {}

    local res, code, response_headers, status = http.request {
        url = url,
        method = "POST",
        headers = {
            ["Content-Type"] = "application/json",
            ["Content-Length"] = tostring(#data)
        },
        source = ltn12.source.string(data), -- Create a source from the JSON string
        sink = ltn12.sink.table(response_body) -- Collect the response data into a table
    }

    return table.concat(response_body), code, response_headers, status
end

-- Send the POST request and print the response
local response, code, headers, status = send_post_request(api_url, json_string)
print("Response code: " .. code)
print("Response from server: " .. response)
