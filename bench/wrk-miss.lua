-- Vary last octet to stress cache miss + MMDB lookup paths through HTTP.
local counter = 0

request = function()
    counter = counter + 1
    local last = (counter % 254) + 1

    return wrk.format("GET", "/bench/geo?ip=8.8.8." .. last)
end
