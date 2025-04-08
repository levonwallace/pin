<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user = $_GET["user"] ?? '';
$context = $_GET["context"] ?? 'General';

// Sanitize context but preserve spaces
$sanitizedContext = str_replace(['/', '\\', '..'], '', $context);
$file = "chat_" . rawurlencode($sanitizedContext) . ".txt";

$debug = [
    "user" => $user,
    "context" => $context,
    "sanitized_context" => $sanitizedContext,
    "request" => $_GET,
    "start_time" => microtime(true)
];

if (!$user) {
    echo json_encode(["mentions" => [], "debug" => ["error" => "No user specified"] + $debug]);
    exit;
}

$debug["file"] = $file;
$debug["file_exists"] = file_exists($file);
$debug["current_dir"] = getcwd();

// Create file if it doesn't exist
if (!file_exists($file)) {
    file_put_contents($file, ""); // Create empty file
    $debug["file_created"] = true;
}

// Read the last 100 messages to check for mentions
$lines = file($file);
$debug["raw_file_contents"] = file_exists($file) ? file_get_contents($file) : null;
$debug["lines_read"] = $lines !== false;

if ($lines === false) {
    echo json_encode([
        "mentions" => [],
        "debug" => ["error" => "Could not read file", "file" => $file] + $debug
    ]);
    exit;
}

$lines = array_filter($lines); // Remove empty lines
$debug["total_lines"] = count($lines);
$debug["first_few_lines"] = array_slice($lines, 0, 5); // Show first 5 lines for debugging

if (count($lines) > 100) {
    $lines = array_slice($lines, -100);
}
$debug["processed_lines"] = count($lines);

$mentions = [];
$debugLines = [];

// Escape special regex characters in username for use in pattern
$escapedUsername = preg_quote($user, '/');

// Create patterns for different mention formats
$patterns = [
    // Standard @username format (with optional HTML entities)
    "/@(?:&[^;]+;)*" . $escapedUsername . '\b/i',
    // Username with spaces (enclosed in double quotes)
    '/@"(?:[^"]*' . $escapedUsername . '[^"]*)"/i',
    // Username with spaces (enclosed in single quotes)
    "/@'(?:[^']*" . $escapedUsername . "[^']*)'/i"
];

foreach ($lines as $index => $line) {
    $line = trim($line);  // Remove any whitespace/newlines
    
    // Debug info for raw line
    $currentLine = [
        "index" => $index,
        "raw_line" => $line,
        "decoded_line" => htmlspecialchars_decode($line)
    ];
    
    if (preg_match("/^(.*?): (.*)$/", $line, $matches)) {
        $sender = trim($matches[1]);
        $message = trim($matches[2]);
        
        // Decode any HTML entities in the message
        $message = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Check for mentions using all patterns
        $hasMention = false;
        $matchedPatterns = [];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $hasMention = true;
                $matchedPatterns[] = [
                    'pattern' => $pattern,
                    'matches' => $matches
                ];
            }
        }
        
        $currentLine += [
            "sender" => $sender,
            "message" => $message,
            "mention_patterns" => $patterns,
            "matched_patterns" => $matchedPatterns,
            "has_mention" => $hasMention,
            "is_from_different_user" => ($sender !== $user),
            "decoded_message" => $message  // Add the decoded message to debug output
        ];
        
        if ($hasMention && $sender !== $user) {
            $mentions[] = [
                "from" => $sender,
                "message" => $message,
                "context" => $context,
                "line_number" => $index + 1
            ];
        }
    } else {
        $currentLine["parse_error"] = "Line did not match expected format";
    }
    
    $debugLines[] = $currentLine;
}

$debug["execution_time"] = microtime(true) - $debug["start_time"];
$debug["message_analysis"] = $debugLines;
$debug["mentions_found"] = count($mentions);

echo json_encode([
    "mentions" => $mentions,
    "debug" => $debug
]);
?> 