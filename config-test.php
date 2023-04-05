<?php
require 'lib/aws.phar';
use Aws\SecretsManager\SecretsManagerClient;
use Aws\Exception\AwsException;
use Aws\Iam\IamClient;

$client = new SecretsManagerClient([
    'version' => '2017-10-17',
    'region' => 'us-east-1',
]);

$secretName = 'arn:aws:secretsmanager:us-east-1:806862010366:secret:rdsInstanceSecret-daphcs5QVdcy-6lUXdz';

try {
    $result = $client->getSecretValue([
        'SecretId' => $secretName,
    ]);

} catch (AwsException $e) {
    $error = $e->getAwsErrorCode();
}
// Decrypts secret using the associated KMS CMK.
// Depending on whether the secret is a string or binary, one of these fields will be populated.
if (isset($result['SecretString'])) {
    $secret = $result['SecretString'];
} 
$CFG = new stdClass;
$CFG->getremoteaddrconf = 0;
$CFG->dbtype = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost = getenv('EnvDatabaseClusterEndpointAddress');
$CFG->dbname = getenv('EnvDatabaseName');
$CFG->dbuser = json_decode($secret)->{'username'};
$CFG->dbpass = json_decode($secret)->{'password'};
$CFG->prefix = 'mdl_';
$CFG->lang = 'en';
$CFG->dboptions = array(
  'dbpersist' => false,
  'dbsocket' => false,
  'dbport' => '',
  'dbhandlesoptions' => false,
  'dbcollation' => 'utf8mb4_unicode_ci',
  'connecttimeout' => 300, 
  'readonly' => [         
    'instance' => 'db-cluster-readonly-endpoint',
    'connecttimeout' => 300, 
    'latency' => 2,    
    'exclude_tables' => [  
      'config',
    ],
  ]
);

// Hostname definition //
$hostname = 'prod.saylor.org';
$hostwithprotocol = strtolower($hostname);

if(substr($hostwithprotocol, 0, 4) === 'http'){} else {
  $hostwithprotocol = 'http://'.strtolower($hostwithprotocol);
}

$CFG->wwwroot = strtolower($hostwithprotocol);
$CFG->sslproxy = (substr($hostwithprotocol,0,5)=='https' ? true : false);
// Moodledata location //
$CFG->dataroot = '/var/www/moodle/data';
$CFG->tempdir = '/var/www/moodle/temp';
$CFG->cachedir = '/var/www/moodle/cache';
$CFG->localcachedir = '/var/www/moodle/local';
$CFG->directorypermissions = 02777;
$CFG->admin = 'admin';
// Configure Session Cache
$SessionsCacheType = 'Memcached';
$SessionEndpoint = '';
if ($SessionEndpoint != '') {
  
  $CFG->dbsessions = false;
    
  if($SessionsCacheType == 'Redis') {

    $CFG->session_handler_class = '\core\session\redis';
    $CFG->session_redis_host = $SessionEndpoint;
    $CFG->session_redis_port = 6379;                     // Optional.
    $CFG->session_redis_database = 0;                    // Optional, default is db 0.
    //$CFG->session_redis_auth = '';                       // Optional, default is don't set one.
    //$CFG->session_redis_prefix = '';                     // Optional, default is don't set one.
    $CFG->session_redis_acquire_lock_timeout = 120;      // Default is 2 minutes.
    $CFG->session_redis_acquire_lock_warn = 0;           // If set logs early warning if a lock has not been acquried.
    $CFG->session_redis_lock_expire = 7200;              // Optional, defaults to session timeout.
    $CFG->session_redis_lock_retry = 100;                // Optional wait between lock attempts in ms, default is 100.

    $CFG->session_redis_serializer_use_igbinary = false; // Optional, default is PHP builtin serializer.
    $CFG->session_redis_compressor = 'none';       
  } else {

    $CFG->session_handler_class = '\core\session\memcached';
    $CFG->session_memcached_save_path = $SessionEndpoint;
    $CFG->session_memcached_prefix = 'memc.sess.key.';
    $CFG->session_memcached_acquire_lock_timeout = 120;
    $CFG->session_memcached_lock_expire = 7100;
    $CFG->session_memcached_lock_retry_sleep = 150;
  }
}
//@error_reporting(E_ALL | E_STRICT);   // NOT FOR PRODUCTION SERVERS!
//@ini_set('display_errors', '1');         // NOT FOR PRODUCTION SERVERS!
//$CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
//$CFG->debugdisplay = 1; 
require_once(__DIR__ . '/lib/setup.php');
// END OF CONFIG //
?>
