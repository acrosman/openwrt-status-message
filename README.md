# OpenWRT Status Message

Sends a status message to a pre-defined https endpoint.

_Warning: Most of this is AI generated code for an experiment on the state of the field. See [this blog post](https://spinningcode.org/2025/03/experiments-with-ai-coding/) for more details._

While I do run a version of this code for my own purposes, it is not something I would consider production worthy or ready.

## Setup

This isn't what I'd call a polished project, at least not yet. So there is some work to do to get everything working.

First, drop the `listener.php` file onto your web server. Update the pre-shared key on line 3 to be custom to you. This key should be a long generated string of random characters.

Next, update the main.lua file to have the URL and pre-shared key to match.

### Router Required Libraries

Ensure that the luasocket, luasec, and lua-dkjson libraries are installed on your OpenWrt device. You can install them using the following commands:

`opkg update && opkg install luasocket luasec dkjson`
