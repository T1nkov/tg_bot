<?php
function isTextMatchingButtons($text) {
    foreach ($GLOBALS['config']['buttons'] as $buttonValues) {
        if (in_array($text, $buttonValues)) return true;
    }
    return false;
}
?>
