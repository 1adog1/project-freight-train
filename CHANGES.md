# Minor Version Update Pickup – 1 – 0

## Features
- Added a site-wide `Minimum Price`
- Added a route-specific `Minimum Price` option
- Added the ability to disable high-collateral penalties on routes

### UPDATE INSTRUCTIONS (From Version Pickup – 0 – *)

1. Pause operation of the webserver.
2. Execute the following SQL Commands:
> ALTER TABLE options ADD minimumprice BIGINT;
> ALTER TABLE routes ADD minimumpriceoverride BIGINT;
> ALTER TABLE routes ADD disablehighcollateral TINYINT;
3. Sync up files with the repository.
4. Restart operation of the webserver.


# Patch Version Update Pickup – 0 – 1

## Bugfixes
- Fixed a Warning in the Calculator that occurred when an invalid system was entered.
- Fixed a Deprecated Code Error in the page handler caused by a `null` subject being passed to `preg_split()`

### UPDATE INSTRUCTIONS (From Version Pickup – 0 – *)

1. Sync up files with the repository.
