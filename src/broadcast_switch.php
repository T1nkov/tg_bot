<?php

function getCrontab() {
    exec('crontab -l 2>&1', $output, $return_var);
    if ($return_var !== 0) {
        echo "Cannot read crontab: " . implode("\n", $output);
        exit(1);
    }
    return $output;
}

function updateCrontab($cronJobs) {
    $newCron = implode("\n", $cronJobs);
    if (!empty($newCron) && substr($newCron, -1) !== "\n") {
        $newCron .= "\n";
    }
    $process = proc_open('crontab', [
        ['pipe', 'r'],
        ['pipe', 'w'],
        ['pipe', 'w'],
    ], $pipes);

    if (is_resource($process)) {
        fwrite($pipes[0], $newCron);
        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        proc_close($process);
    }
    echo "Crontab changed.\n";
}

function startBC() {
    $cronJobs = getCrontab();
    $newJobs = array_map(function($job) {
        return ltrim($job, '#');
    }, $cronJobs);
    updateCrontab($newJobs);
}

function stopBC() {
    $cronJobs = getCrontab();
    $newJobs = array_map(function($job) {
        return (strpos($job, '#') === false) ? '#' . $job : $job;
    }, $cronJobs);
    updateCrontab($newJobs);
}

?>
