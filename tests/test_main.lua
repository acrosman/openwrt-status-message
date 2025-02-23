local luatest = require('luatest')
local g = luatest.group('main_tests')

g.test_hello_world = function()
    luatest.assert_equals(1 + 1, 2)
end