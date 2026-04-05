<?php
// includes/language.php

// Define available languages
$available_languages = [
    'en' => 'English',
    'hi' => 'हिन्दी (Hindi)',
    'ta' => 'தமிழ் (Tamil)',
    'kn' => 'ಕನ್ನಡ (Kannada)',
    'te' => 'తెలుగు (Telugu)',
    'ml' => 'മലയാളം (Malayalam)'
];

// Handle language switch
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages)) {
    $_SESSION['lang'] = $_GET['lang'];
    
    // Redirect back to remove the lang param from URL
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    
    // Keep other GET params if they exist
    $query = $_GET;
    unset($query['lang']);
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }
    
    header("Location: $url");
    exit();
}

// Set default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

$current_lang = $_SESSION['lang'];

// Load language file
$lang_file = __DIR__ . "/languages/{$current_lang}.php";
if (file_exists($lang_file)) {
    $translations = require $lang_file;
} else {
    $translations = require __DIR__ . "/languages/en.php";
}

/**
 * Translate a key
 * @param string $key
 * @param string $default Optional default value if key not found
 * @return string
 */
function __($key, $default = null) {
    global $translations;
    if (isset($translations[$key])) {
        return $translations[$key];
    }
    return $default ?: $key;
}

/**
 * Get the current language code
 * @return string
 */
function getCurrentLang() {
    return $_SESSION['lang'] ?? 'en';
}

/**
 * Get language font family
 */
function getLangFont() {
    $lang = getCurrentLang();
    switch ($lang) {
        case 'hi': return "'Noto Sans Devanagari', sans-serif";
        case 'ta': return "'Noto Sans Tamil', sans-serif";
        case 'kn': return "'Noto Sans Kannada', sans-serif";
        case 'te': return "'Noto Sans Telugu', sans-serif";
        case 'ml': return "'Noto Sans Malayalam', sans-serif";
        default: return "'Outfit', 'Inter', sans-serif";
    }
}
?>
