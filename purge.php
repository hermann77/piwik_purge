<?php

$db_name = $argv[1];
$username = $argv[2];
$password = $argv[3];
$purge_till_year = $argv[4]; // format: 'YYYY', e.g. '2013'
$purge_till_year = ($purge_till_year > 2013) ? 2013 : $purge_till_year; // the max year = 2013 (to avoid remove current data)
$purge_till_date = $purge_till_year . '-12-31';


print 'Start purging Piwik/Matomo database' . PHP_EOL;
print date("h:i:s", time()) . PHP_EOL;

try {
    $dsn = 'mysql:host=localhost;dbname=' . $db_name;
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);


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
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();
    print 'OPTIMIZE executed' . PHP_EOL;



    /**
     * REMOVE ARCHIVE TABLES
     */
    
    for($year = 2000; $year <= $purge_till_year; ++$year) {

       for($month = 1; $month <= 12; ++$month) {

            $month = str_pad($month, 2, '0', STR_PAD_LEFT);

            sleep(1);
            $sql = 'DROP TABLE IF EXISTS piwik_archive_blob_' . $year . '_' . $month;
            print 'SQL (blob): ' . $sql . PHP_EOL;
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
            print 'TABLE piwik_archive_blob_' . $year . '_' . $month . ' dropped' . PHP_EOL;

            sleep(1);
            $sql = 'DROP TABLE IF EXISTS piwik_archive_numeric_' . $year . '_' . $month;
            print 'SQL (archive): ' . $sql . PHP_EOL;
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
            print 'TABLE piwik_archive_numeric_' . $year . '_' . $month . ' dropped' . PHP_EOL;
       }
    }

    



}
catch(PDOException $e) {
    print 'Exception ' . $e->getMessage() . PHP_EOL;
}


print 'Finished to purging Piwik/Matomo database' . PHP_EOL;
print date("h:i:s", time()) . PHP_EOL;