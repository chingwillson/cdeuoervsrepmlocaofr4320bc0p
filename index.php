<?php
  // php -S 127.0.0.1:8888
  date_default_timezone_set('Asia/Kuala_Lumpur');
  define('CODE_SUCCESS',    1);
  define('CODE_FAILED',     2);
  define('DEBUG_MODE',      TRUE);
  define('FLOAT_MIN_VALUE', 0);
  define('FLOAT_MAX_VALUE', 99999);
  define('TEXT_MAX_LENGTH', 5000);
  define('IS_ARRAY',        'is_array');
  define('IS_STRING',       'is_string');
  define('IS_DOUBLE',       'is_double');
  define('IS_INT',          'is_int');
  define('IS_NUMERIC',      'is_numeric');

  define('DB_HOST',         'localhost');
  define('DB_NAME',         'test');
  define('DB_USER',         '');
  define('DB_PASS',         '');

  $jsonpayload_keys = array('uid'=>IS_NUMERIC, 'trips'=>IS_ARRAY);
  $trip_keys        = array('tid'=>IS_INT, 'name'=>IS_STRING, 'expenses'=>IS_ARRAY, 'others'=>IS_ARRAY);
  $expense_keys     = array('type'=>IS_STRING, 'amount'=>'is_double', 'others'=>IS_ARRAY);
  
  try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } 
  catch (PDOException $e) {
    dump(array('code'=>CODE_FAILED, 'message'=>'Failed connecting to database.'));
  }
  
  $jsonpayload = file_get_contents('php://input');
  if ($jsonpayload===NULL) {
    dump(array('code'=>CODE_FAILED, 'message'=>'Failed reading submitted data.'));
  }
  
  $payload = json_decode($jsonpayload, true);
  if ($payload===NULL) {
    dump(array('code'=>CODE_FAILED, 'message'=>'Missing JSON payload.'));
  }

  $payload = isset($payload['jsonpayload']) ? $payload['jsonpayload'] : NULL;
  if ($payload!==NULL && (!has_valid_schema($jsonpayload_keys, $payload) || !has_valid_value($jsonpayload_keys, $payload))) {
    dump(array('code'=>CODE_FAILED, 'message'=>'Invalid JSON payload.', 'debug'=>$payload));
  }
  
  $uid    = $payload['uid'];
  $trips  = $payload['trips'];
  $stored_trips     = array();
  $stored_trip_ids  = array();
  foreach($trips as $trip) {
    if (!has_valid_schema($trip_keys, $trip) || !has_valid_value($trip_keys, $trip)) {
      dump(array('code'=>CODE_FAILED, 'message'=>'Invalid JSON trips payload.', 'userid'=>$uid, 'debug'=>$trip));
    }
  
    $expenses = $trip['expenses'];
    foreach($expenses as $expense) {
      if (!has_valid_schema($expense_keys, $expense) || !has_valid_value($expense_keys, $expense)) {
        dump(array('code'=>CODE_FAILED, 'message'=>'Invalid JSON expenses payload.', 'userid'=>$uid, 'debug'=>$expense));
      }
    }
  
    $trip_id      = $trip['tid'];
    $trip_name    = $trip['name'];
    $trip_others  = json_encode($trip['others']);

    $delete_sqls = array(
      "DELETE FROM `expenses` WHERE `trip_id` IN (SELECT `id` FROM `trips` WHERE `tid` = :tid)", 
      "DELETE FROM `trips` WHERE `tid` = :tid"
    );
    foreach($delete_sqls as $sql) {
      $del_stmt = $pdo->prepare($sql);
      $del_stmt->bindParam(':tid',  $trip_id);
      $del_stmt->execute();  
    }

    $trip_query  = "INSERT INTO `trips` (`tid`, `name`, `uid`, `others`) ";
    $trip_query .= "SELECT :tid, :name, :user_id, :others FROM `users` WHERE `uid` LIKE :user_id";
  
    $trip_statement = $pdo->prepare($trip_query);
    $trip_statement->bindParam(':tid',      $trip_id);
    $trip_statement->bindParam(':name',     $trip_name);
    $trip_statement->bindParam(':user_id',  $uid);
    $trip_statement->bindParam(':others',   $trip_others);
    $trip_statement->execute();
    if ($trip_statement->rowCount()!==1) {
      dump(array('code'=>CODE_FAILED, 'message'=>'Failed to insert trip.', 'userid'=>$uid, 'debug'=>$trip));
    }
    $trip_id = $pdo->lastInsertId();
    $stored_trips[]     = $trip_name;
    $stored_trip_ids[]  = $trip['tid'];
  
    $expense_query = "INSERT INTO expenses (`type`, `amount`, `trip_id`, `others`) VALUES (:type, :amount, :trip_id, :others)";
    $expense_statement = $pdo->prepare($expense_query);  
    foreach($expenses as $expense) {
      $expense_type   = $expense['type'];
      $expense_amount = $expense['amount'];
      $expense_others = json_encode($expense['others']);
  
      $expense_statement->bindParam(':type',    $expense_type);
      $expense_statement->bindParam(':amount',  $expense_amount);
      $expense_statement->bindParam(':trip_id', $trip_id);
      $expense_statement->bindParam(':others',  $expense_others);
      $expense_statement->execute();
    }
  }
  
  dump(array('code'=>CODE_SUCCESS, 'message'=>'OK', 'userid'=>$uid, 'names'=>$stored_trips, 'ids'=>$stored_trip_ids, 'number'=>count($stored_trips)));

  function has_valid_schema($keys_array, $array) {
    if (empty($keys_array) || empty($array) || !is_array($keys_array) || !is_array($array)) {
      return FALSE;
    }

    $keys_count = count(array_intersect_key($keys_array, $array));
    if (($keys_count !== count($keys_array)) || ($keys_count !== count($array))) {
      return FALSE;
    }

    foreach($keys_array as $key=>$fn) {
      if (!isset($array[$key]) || "{$fn($array[$key])}"!=="1") {
        return FALSE;
      }
    }

    return TRUE;
  }
  function has_valid_value($keys_array, $array) {
    foreach($keys_array as $key=>$fn) {
      $data = $array[$key];
      if ($fn===IS_DOUBLE) { 
        $data = floatval($data);
        if ($data < FLOAT_MIN_VALUE || $data > FLOAT_MAX_VALUE) {
          return FALSE;
        }
        continue; 
      }
      if ($fn===IS_ARRAY) { $data = json_encode($array[$key]); }
      if (strlen($data) > TEXT_MAX_LENGTH) { return FALSE; }
    }
    return TRUE;
  }
  function dump($array) {
    if (!DEBUG_MODE) { unset($array['debug']); }
    echo json_encode($array);
    exit;
  }
