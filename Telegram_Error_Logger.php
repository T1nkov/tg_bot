<?php
class TelegramErrorLogger {
    private static $self;

    public static function log($result, $content, $use_rt = true) {
        if ($result['ok'] === false) {
            self::$self = new self();
            $error = self::formatResponse($result);
            $array = self::formatContent($content, $use_rt);
            $backtrace = '============[Trace]===========\n' . (new \Exception())->getTraceAsString();
            self::$self->_log_to_file($error . $array . $backtrace);
        }
    }

    private function _log_to_file($error_text) {
        $dir_name = dirname(__FILE__) . '/logs';
        if (!is_dir($dir_name)) mkdir($dir_name);
        $fileName = "$dir_name/" . __CLASS__ . '-' . date('Y-m-d') . '.txt';
        $date = "[ " . date('Y-m-d H:i:s e') . " ]\n";
        file_put_contents($fileName, "============[Date]============\n$date$error_text\n\n", FILE_APPEND);
    }

    private static function formatResponse($result) {
        $error = "==========[Response]==========\n";
        foreach ($result as $key => $value) {
            $error .= "$key:\t\t" . ($value ? $value : "False") . "\n";
        }
        return $error;
    }

    private static function formatContent($content, $use_rt) {
        $array = "=========[Sent Data]==========\n";
        foreach ($content as $key => $value) {
            $array .= $use_rt ? self::$self->rt($value) . PHP_EOL : "$key:\t\t$value\n";
        }
        return $array;
    }

    private function rt($array, $title = null, $head = true) {
        $text = $head ? "[ref]\n" : '';
        foreach ($array as $key => $value) {
            $key = $title ? "$title.$key" : $key;
            $text .= is_array($value) ? self::rt($value, $key, false) : "[ref].$key= " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . PHP_EOL;
        }
        return $text;
    }
}
