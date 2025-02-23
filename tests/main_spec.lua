local busted = require('busted')
local json = require('dkjson')

-- Include the file being tested
dofile("src/main.lua")

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

describe("main.lua tests", function()
    local original_popen
    local original_http_request

    -- Setup mocks before each test
    before_each(function()
        original_popen = io.popen
        io.popen = mock_popen

        original_http_request = require("socket.http").request
        package.loaded["socket.http"].request = mock_http_request
    end)

    -- Restore original functions after each test
    after_each(function()
        io.popen = original_popen
        package.loaded["socket.http"].request = original_http_request
    end)

    -- Test get_interface_status function
    it("should get the status of a network interface", function()
        local status = get_interface_status("wan")
        assert.are.same(status.up, true)
        assert.are.same(status.uptime, 12345)
    end)

    -- Test get_wifi_status function
    it("should get the status of wifi radios", function()
        local status = get_wifi_status()
        assert.are.same(status.radio0.up, true)
        assert.are.same(status.radio0.uptime, 12345)
    end)

    -- Test send_post_request function
    it("should send a POST request and receive a response", function()
        local response, code, headers, status = send_post_request("https://spinningcode.org/api/endpoint",
            '{"test": "data"}')
        assert.are.same("OK", response)
        assert.are.same(200, code)
    end)
end)
