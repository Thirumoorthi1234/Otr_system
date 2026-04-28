<?php
// includes/language.php

// Define available languages
$available_languages = [
    'en' => 'English',
    'hi' => 'हिन्दी',
    'ta' => 'தமிழ்',
    'kn' => 'ಕನ್ನಡ',
    'te' => 'తెలుగు',
    'ml' => 'മലയാളം'
];

$eng_language_names = [
    'en' => 'English',
    'hi' => 'Hindi',
    'ta' => 'Tamil',
    'kn' => 'Kannada',
    'te' => 'Telugu',
    'ml' => 'Malayalam'
];

// Handle language switch
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages)) {
    $_SESSION['lang'] = $_GET['lang'];
    
    // Sync with Google Translate
    if ($_GET['lang'] === 'en') {
        setcookie('googtrans', '', time() - 3600, '/');
        setcookie('googtrans', '', time() - 3600, '/', $_SERVER['HTTP_HOST']); 
    } else {
        setcookie('googtrans', '/en/' . $_GET['lang'], time() + (86400 * 30), '/');
        setcookie('googtrans', '/en/' . $_GET['lang'], time() + (86400 * 30), '/', $_SERVER['HTTP_HOST']);
    }
    
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

/**
 * Get the current language name in English
 * @return string
 */
function getCurrentLangName() {
    $lang = getCurrentLang();
    $names = [
        'en' => 'English',
        'hi' => 'Hindi',
        'ta' => 'Tamil',
        'kn' => 'Kannada',
        'te' => 'Telugu',
        'ml' => 'Malayalam'
    ];
    return $names[$lang] ?? 'English';
}
?>
