# Backend roles for TYPO3

This TYPO3 extension allows the text-based definition and synchronization of (parts of) `be_groups` records.

## Features

* Define the access definitions for `be_groups` records
* Set up `be_groups` records to be synchronized with the definitions
* Synchronize via TYPO3 backend or CLI command
* Export definitions representing existing `be_groups` records for an easy
  migration towards this extension. Either as download or text-based copy&paste

## Installation

### System requirements

| Backend roles for TYPO3 | TYPO3 | PHP |
| --- | --- | --- |
| `4.x` (dev) | `13.4`<br>`12.4` | `8.2` - `8.4`<br>`8.1` - `8.4` |
| `3.x` (stable) | `12.4` | `8.1` - `8.2` |
| `2.x` (old stable) | `11.5` | `7.4` - `8.2` |
| `1.x` (legacy) | `9.5`, `10.4` | `7.2` - `7.3` |

### Install package

```
composer require christianfutterlieb/backend_roles
```

## Docs

The documentation can be found here:
https://docs.typo3.org/p/christianfutterlieb/backend_roles/main/en-us/

## License

GPLv2.0 or later

## Copyright

2020-2022 by Agentur am Wasser | Maeder & Partner AG (https://www.agenturamwasser.ch)
