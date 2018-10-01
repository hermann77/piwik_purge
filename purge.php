<?php

$db_name = $argv[1];
$username = $argv[2];
$password = $argv[3];
$purge_till_year = $argv[4]; // format: 'YYYY', e.g. '2013'
$purge_till_year = ($purge_till_year > 2013) ? 2013 : $purge_till_year; // the max year = 2013 (to avoid remove current data)
$purge_till_date = $purge_till_year . '-12-31';



try {
    $dsn = 'mysql:host=localhost;dbname=' . $db_name;
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    /**
     * DELETE piwik_log_visit AND piwik_log_link_visit_action
     */
    $sql = 'DELETE piwik_log_visit, piwik_log_link_visit_action
            FROM piwik_log_visit INNER JOIN piwik_log_link_visit_action
            WHERE piwik_log_visit.idvisit = piwik_log_link_visit_action.idvisit
            AND visit_first_action_time <= :purge_till_date';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':purge_till_date' => $purge_till_date]);

    print 'DELETED ' . $stmt->rowCount() . ' items.' . PHP_EOL;


    /**
     * OPTIMIZE
     */
    $sql = 'OPTIMIZE TABLE piwik_log_visit, piwik_log_link_visit_action';
    $pdo->exec($sql);
    print 'OPTIMIZE executed' . PHP_EOL;



    /**
     * REMOVE ARCHIVE TABLES
     */
    for($year = 2000; $year <= $purge_till_year; ++$year) {
       for($month = 1; $month <= 12; ++$month) {
            $sql = 'DROP TABLE piwik_archive_blob_' . $year . '_' . $month;
            $pdo->exec($sql);

            $sql = 'DROP TABLE piwik_archive_numeric_' . $year . '_' . $month;
            $pdo->exec($sql);
       }
    }

}
catch(PDOException $e) {
    print 'Exception ' . $e->getMessage();
}