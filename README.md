# PovodiAPI
![PovodiAPI](https://repository-images.githubusercontent.com/202905193/358d5c00-c2b0-11e9-9209-86d8a0b79280)
Get water info in a simple API. This PHP class supports [Povodí Labe](http://www.pla.cz), [Povodí Odry](https://www.pod.cz), [Povodí Ohře](https://www.poh.cz) and [Povodí Vltavy](http://www.pvl.cz).

## Usage
### Nádrže
```
./povodiapi.php?website=pla&channel=nadrze&station=100|1&response=json&values=all
```
Required to enter:
- *website*: **pla**, **pod**, **poh**, **pvl**
- *channel*: **nadrze** (used for this example), **sap**, **srazky**
- *station*: ID of station, available in [/stations](/stations)
- *response*: **json**, **rss**
- *values*: **all**, **latest**

### SaP (Stavy a průtoky)
```
./povodiapi.php?website=pla&channel=sap&station=9|1&response=json&values=all
```
Required to enter:
- *website*: **pla**, **pod**, **poh**, **pvl**
- *channel*: **nadrze**, **sap** (used for this example), **srazky**
- *station*: ID of station, available in [/stations](/stations)
- *response*: **json**, **rss**
- *values*: **all**, **latest**

### Srážky
```
./povodiapi.php?website=pla&channel=srazky&station=215|1&response=json&values=latest
```
Required to enter:
- *website*: **pla**, **pod**, **poh**, **pvl**
- *channel*: **nadrze**, **sap**, **srazky** (used for this example)
- *station*: ID of station, available in [/stations](/stations)
- *response*: **json**, **rss**
Optional:
- *values*: **all**, **latest** (including total value), **total**
- *temp*: Only for `rss` response, channel `srazky` and set values to `all`. Allow showing temperature in title. Options: **yes**, **no**

##### License
The script is available under the [MIT license](/LICENSE).
