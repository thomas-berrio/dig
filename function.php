<?php
/**
 * Function to execute a DNS query using the "dig" tool.
 *
 * This function performs DNS queries for a specific domain and a given DNS record type.
 * It uses the "dig" tool to retrieve the desired information.
 *
 * @param string $domain The domain name for which to perform the DNS query.
 * @param string $type The type of DNS record to look up (e.g., "A", "MX", "TXT").
 * @param string $server The IP address of the DNS server to use for the query (default: "8.8.8.8").
 * @param int $timeout The timeout (in seconds) to execute the command (default: 10 seconds, maximum: 30 seconds).
 *
 * @return array Returns an associative array with three keys:
 *               - "raw": The raw output of the dig command as a string.
 *               - "result": An array of parsed DNS record results, with the keys "name", "type", "TTL", and "data".
 *               - "execution_time": The execution duration of the command in milliseconds, formatted to two decimal places (e.g., 123.45).
 *
 * @throws InvalidArgumentException If the record type, domain name, server address, or timeout is invalid.
 * @throws RuntimeException If an error occurs during the execution of the dig command or if dig is not available.
 */
function dig($domain, $type, $server = "8.8.8.8", $timeout = 10) {
    $valid_types = [
        "A", "AAAA", "ALL", "CAA", "CDNSKEY", "CDS", "CERT", "CNAME", "DNAME", 
        "DNSKEY", "DS", "HINFO", "HTTPS", "INTEGRITY", "IPSECKEY", "KEY", "MX", 
        "NAPTR", "NS", "NSEC", "NSEC3", "NSEC3PARAM", "PTR", "RP", "RRSIG", 
        "SIG", "SOA", "SPF", "SRV", "SSHFP", "SVCB", "TLSA", "TXT", "WKS"
    ];

    // Validate DNS record types
    $type = strtoupper(trim($type));
    if (!in_array($type, $valid_types)) {
        throw new InvalidArgumentException("Invalid DNS record type provided.");
    }

    // Validate domain name
    if ($domain !== "." && !filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        throw new InvalidArgumentException("Invalid domain name provided.");
    }

    // Validate DNS server IP address
    if (!filter_var($server, FILTER_VALIDATE_IP)) {
        throw new InvalidArgumentException("Invalid DNS server IP address provided.");
    }

    // Validate timeout and limit it to 30 seconds
    if (!is_int($timeout) || $timeout <= 0) {
        throw new InvalidArgumentException("Timeout must be a positive integer.");
    }
    if ($timeout > 30) {
        $timeout = 30;
    }

    // Check if the dig command is available
    $output = null;
    $return_val = null;
    exec("command -v dig", $output, $return_val);
    if ($return_val !== 0) {
        throw new RuntimeException("The 'dig' command is not available on this system.");
    }

    // Build the dig command using escapeshellarg
    $escaped_domain = escapeshellarg($domain);
    $escaped_server = escapeshellarg($server);
    $escaped_type = escapeshellarg($type);
    
    // Determine the timeout command based on the OS
    if (stripos(PHP_OS, 'WIN') === 0) {
        // On Windows, 'timeout' is not available by default
        $command = sprintf("dig @%s %s %s +noall +answer", $escaped_server, $escaped_domain, $escaped_type);
    } else {
        // On Unix-like systems, use the 'timeout' command
        $command = sprintf("timeout %d dig @%s %s %s +noall +answer", $timeout, $escaped_server, $escaped_domain, $escaped_type);
    }

    // Measure the execution time of the command
    $start_time = microtime(true);

    // Execute the command using proc_open for better control over streams
    $descriptorspec = [
        1 => ['pipe', 'w'], // stdout
        2 => ['pipe', 'w']  // stderr
    ];

    $process = proc_open($command, $descriptorspec, $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException("Failed to execute the dig command.");
    }

    // Read the command output
    $output = stream_get_contents($pipes[1]);
    $error_output = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $return_val = proc_close($process);

    $end_time = microtime(true);
    $execution_time = number_format(($end_time - $start_time) * 1000, 2); // Duration in milliseconds formatted to two decimal places

    if ($return_val != 0) {
        throw new RuntimeException("Error occurred during DNS lookup: " . $error_output);
    }

    // Process the response
    $results = [];
    foreach (explode("\n", $output) as $line) {
        if (!empty($line) && strpos($line, ";") === false) {
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 5) {
                $data = implode(" ", array_slice($parts, 4));
                $results[] = [
                    "name" => $parts[0],
                    "type" => $parts[3],
                    "TTL" => (int)$parts[1],
                    "data" => $data
                ];
            }
        }
    }

    // Return the results, raw output, and execution time
    return [
        "raw" => $output,
        "result" => $results,
        "execution_time" => $execution_time
    ];
}
?>
