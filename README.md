# DNS Query Tool using `dig`

This script provides a PHP function `dig()` that allows executing DNS queries using the `dig` command-line utility. It is useful for querying specific DNS records for a given domain, such as `A`, `MX`, `TXT`, and more.

## Features
- Performs DNS queries for a specific domain and record type.
- Supports multiple DNS record types such as `A`, `MX`, `TXT`, `NS`, and others.
- Uses `proc_open()` for better control over command execution, preventing potential command injection vulnerabilities.
- Limits execution time to prevent the script from hanging indefinitely.
- Measures and returns the execution time of each query in milliseconds.

## Usage

### Function Signature
```php
function dig($domain, $type, $server = "8.8.8.8", $timeout = 10);
```

### Parameters
- `$domain` (string): The domain name for which to execute the DNS query.
- `$type` (string): The type of DNS record to look up (e.g., `A`, `MX`, `TXT`).
- `$server` (string, optional): The IP address of the DNS server to use for the query. Defaults to Google's DNS server (`8.8.8.8`).
- `$timeout` (int, optional): Maximum time (in seconds) to execute the command. Defaults to 10 seconds, with a maximum of 30 seconds.

### Return Value
The function returns an associative array with the following keys:
- **`raw`**: The raw output from the `dig` command as a string.
- **`result`**: An array of parsed DNS record results, containing the keys:
  - `name`: The domain name.
  - `type`: The DNS record type (e.g., `A`, `MX`).
  - `TTL`: The time-to-live value for the record.
  - `data`: The data of the DNS record.
- **`execution_time`**: The time taken to execute the command, in milliseconds, formatted to two decimal places (e.g., `123.45`).

### Example
```php
try {
    $dns_result = dig("example.com", "A");
    echo "Raw Output:\n" . $dns_result['raw'] . "\n";
    echo "Parsed Records:\n";
    print_r($dns_result['result']);
    echo "Execution Time: " . $dns_result['execution_time'] . " ms\n";
} catch (InvalidArgumentException $e) {
    echo "Invalid Argument: " . $e->getMessage();
} catch (RuntimeException $e) {
    echo "Runtime Error: " . $e->getMessage();
}
```

## Requirements
- PHP 7.1 or above.
- The `dig` command must be available on the system.

## Compatibility
The script checks for the operating system to determine how to handle command timeouts:
- **Unix-like systems**: Uses the `timeout` command to limit the duration of the `dig` command.
- **Windows systems**: The `timeout` command is not available, so the script directly runs `dig` without a timeout option.

## Error Handling
The function throws exceptions for the following scenarios:
- **InvalidArgumentException**: If the domain name, record type, DNS server IP, or timeout value is invalid.
- **RuntimeException**: If the `dig` command fails to execute or if the `dig` utility is not available on the system.

## Security Considerations
- The script uses `proc_open()` instead of `exec()` to gain better control over input/output streams, minimizing potential security vulnerabilities.
- Input arguments are sanitized using `escapeshellarg()` to avoid command injection.

## Improvements
- **Cross-Platform Compatibility**: Future versions could incorporate more sophisticated timeout handling for Windows environments.
- **Extensive Logging**: Adding detailed logs can help track errors during execution, which is especially useful for debugging in production environments.

## License
This script is open-source and can be freely used or modified.

## Contribution
Feel free to submit pull requests or suggest features for improvement. Contributions are always welcome!

