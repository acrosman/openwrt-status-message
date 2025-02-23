local luatest = require('luatest')
local g = luatest.group('main_tests')

-- Mocking io.popen to simulate ubus call responses
local function mock_popen(command)
    local handle = {}
    function handle:read()
        if command == "ubus call network.interface.wan status" then
            return '{"up": true, "pending": false, "available": true, "autostart": true, "uptime": 12345}'
        elseif command == "ubus call network.wireless status" then
            return '{"radio0": {"up": true, "pending": false, "autostart": true, "uptime": 12345}}'
        end
    end

    function handle:close() end

    return handle
end

-- Mocking http.request to simulate HTTP POST request
local function mock_http_request(params)
    return "OK", 200, {}, "HTTP/1.1 200 OK"
end

-- Test get_interface_status function
g.test_get_interface_status = function()
    _G.io.popen = mock_popen
    local status = get_interface_status("wan")
    luatest.assert_equals(status.up, true)
    luatest.assert_equals(status.uptime, 12345)
end

-- Test get_wifi_status function
g.test_get_wifi_status = function()
    _G.io.popen = mock_popen
    local status = get_wifi_status()
    luatest.assert_equals(status.radio0.up, true)
    luatest.assert_equals(status.radio0.uptime, 12345)
end

-- Test send_post_request function
g.test_send_post_request = function()
    _G.http.request = mock_http_request
    local response, code, headers, status = send_post_request("https://spinningcode.org/api/endpoint", '{"test": "data"}')
    luatest.assert_equals(response, "OK")
    luatest.assert_equals(code, 200)
end
