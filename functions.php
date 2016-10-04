  <?php

  require_once('phpQuery/phpQuery.php'); # подключаем phpQuery

  # ------------------------------------------------------------------------
  # --[ MySQL ]-------------------------------------------------------------
  # ------------------------------------------------------------------------

  # устанавливаем соединение с БД
  function connect_to_db($host, $user, $password, $dbname) {
    $conn = new mysqli($host, $user, $password, $dbname);
    if ($conn->connect_error) {
      echo "Произошла ошибка подключения к БД<br />ErrNo: {$mysqli->connect_errno}<br />Error: {$mysqli->connect_error}";
      return false;
    }

    # это чтобы не было иероглифов
    $conn->query("set names 'UTF8'");
    $conn->query("set character set 'UTF8'");

    return $conn;    
  }
  

  # выгребаем все данные из БД
  function select_data($conn) {
    $sql =  "SELECT id, code, name, description, price, small_image, small_image_size, large_image, large_image_size, marked, changed ";
    $sql .= "FROM tbl_goods ";
    $sql .= "WHERE marked = '0000-00-00 00:00:00'";
    if (!$result = $conn->query($sql)) {
      echo "Произошла ошибка выполения запроса к БД<br />{$sql}<br />ErrNo: {$conn->errno}<br />Error: {$conn->error}";
      return false;
    }

    $res_array = array();
    if ($result->num_rows === 0) {
      # если пустой результат, то отправим пустой массив
      return $res_array;
    }

    # запакуем все в массив
    while ($row = $result->fetch_assoc()) {
      $res_array[ $row['code'] ] = $row;
    }
    $result->free();

    return $res_array;

  }


  # получаем элемент по коду, если он помечен на удаление (marked = '0000-00-00 00:00:00')
  function get_marked_item($conn, $code) {
    $sql =  "SELECT id, code, name, description, price, small_image, small_image_size, large_image, large_image_size, marked, changed ";
    $sql .= "FROM tbl_goods ";
    $sql .= "WHERE code = '{$code}' and NOT marked = '0000-00-00 00:00:00'";
    if (!$result = $conn->query($sql)) {
      echo "Произошла ошибка выполения запроса к БД<br />{$sql}<br />ErrNo: {$conn->errno}<br />Error: {$conn->error}";
      return false;
    }  

    # запакуем все в массив
    if ($row = $result->fetch_assoc()) {
      $result->free();
      return $row;
    }

  }
  

  # вставляем строку данных
  function insert_item($conn, $code, $name, $desc, $price, $small_image, $small_image_size, $large_image, $large_image_size) {
    $sql  = "INSERT INTO tbl_goods (code, name, description, price, small_image, small_image_size, large_image, large_image_size) ";
    $sql .= "VALUES ( ";
    $sql .= "'".$conn->real_escape_string($code)."', ";
    $sql .= "'".$conn->real_escape_string($name)."', ";
    $sql .= "'".$conn->real_escape_string($desc)."', ";
    $sql .= $conn->real_escape_string($price).", ";
    $sql .= "'".$conn->real_escape_string($small_image)."', ";
    $sql .= $conn->real_escape_string($small_image_size).", ";
    $sql .= "'".$conn->real_escape_string($large_image)."', ";
    $sql .= $conn->real_escape_string($large_image_size).")";

    if(!$result = $conn->query($sql)) {
      echo "Произошла ошибка добавления строки данных в БД<br />{$sql}<br />ErrNo: {$conn->errno}<br />Error: {$conn->error}";
      return false;
    }
    return true;

  }


  # вставляем массив данных
  function insert_array($conn, $data_array) {
    $sql_goods  = "INSERT INTO tbl_goods (code, name, description, price, small_image, small_image_size, large_image, large_image_size) ";
    $sql_goods .= "VALUES ";
    $sql_tmp = "";

    foreach ($data_array as $key => $val) {
      $code             = $conn->real_escape_string($key);
      $name             = $conn->real_escape_string($val['name']);
      $desc             = $conn->real_escape_string($val['description']);
      $price            = $conn->real_escape_string($val['price']);
      $small_image      = $conn->real_escape_string($val['small_image']);
      $small_image_size = $conn->real_escape_string($val['small_image_size']);
      $large_image      = $conn->real_escape_string($val['large_image']);
      $large_image_size = $conn->real_escape_string($val['large_image_size']);
      
      $sql_tmp .= $sql_tmp === "" ? "" : ", ";
      $sql_tmp .= "('{$key}', '{$name}', '{$desc}', '{$price}', '{$small_image}', '{$small_image_size}', '{$large_image}', '{$large_image_size}')";
    }
    $sql_goods .= $sql_tmp;
    #echo $sql_goods;

    if(!$result = $conn->query($sql_goods)) {
      echo "Произошла ошибка добавления данных в БД<br />{$sql_goods}<br />ErrNo: {$conn->errno}<br />Error: {$conn->error}";
      return false;
    }
    return true;
    
  }


  # обновляем данные в строке по полю 'code'
  function update_item($conn, $code, $upd_data_array, $marked = 0) {
    # $marked = -1 => FALSE
    # $marked =  1 => TRUE
    # $marked =  0 => DO NOTHING
    $name             = $conn->real_escape_string($upd_data_array['name']);
    $price            = $conn->real_escape_string($upd_data_array['price']);  
    $desc             = $conn->real_escape_string($upd_data_array['description']);
    $small_image      = $conn->real_escape_string($upd_data_array['small_image']);
    $small_image_size = $conn->real_escape_string($upd_data_array['small_image_size']);
    $large_image      = $conn->real_escape_string($upd_data_array['large_image']);
    $large_image_size = $conn->real_escape_string($upd_data_array['large_image_size']);

    $values  = "name = '{$name}', ";
    $values .= "price = {$price}, ";
    $values .= "description = '{$desc}', ";
    $values .= "small_image = '{$small_image}', ";
    $values .= "small_image_size = {$small_image_size}, ";
    $values .= "large_image = '{$large_image}', ";
    $values .= "large_image_size = {$large_image_size} ";
    /*
    foreach ($upd_data_array as $key => $val) {
      $values .= $values === '' ? '' : ', ';  # добавляем в конце запятую после каждого зн-я

      if (is_numeric($val)) {
        $values .= "{$key} = {$val}";
      } elseif (is_string($val)) {
        $values .= "{$key} = '{$val}'";
      } 

    }
    */

    if ($marked == 1) {
      $date = date("Y-m-d H:i:s");
      $values .= ", marked = '{$date}'";
    } elseif ($marked == -1) {
      $values .= ", marked = '0000-00-00 00:00:00'";
    }

    $sql  = "UPDATE tbl_goods ";
    $sql .= "SET {$values} ";
    $sql .= "WHERE code = '{$code}'";

    if(!$result = $conn->query($sql)) {
      echo "Произошла ошибка изменения данных в БД<br />{$sql}<br />ErrNo: {$conn->errno}<br />Error: {$conn->error}";
      return false;
    }

    return true;

  }

  # устанавливаем/снимаем пометку на отдельном элементе
  function update_mark($conn, $code, $marked = true) {
    
    if ($marked) {
      $mark = date("Y-m-d H:i:s");      
    } else {
      $mark = "0000-00-00 00:00:00";
    }

    $sql  = "UPDATE tbl_goods ";
    $sql .= "SET marked = '{$mark}' ";
    $sql .= "WHERE code = '{$code}'";

    if(!$result = $conn->query($sql)) {
      echo "Произошла ошибка изменения данных в БД<br />{$sql}<br />ErrNo: {$conn->errno}<br />Error: {$conn->error}";
      return false;
    }

    return true;
  }


  # закрываем соединение с БД
  function close_connection($conn) {
    $conn->close();
  }


  # ------------------------------------------------------------------------
  # -- [cURL] --------------------------------------------------------------
  # ------------------------------------------------------------------------


  # получаем просто копию страницы
  function get_html_data($url, $useragent, $referer = 'http://google.com') {
    $ch = curl_init();                                    # инициализируем curl и получаем дескриптор потока
    # устанавливаем необходимые опции
    curl_setopt($ch, CURLOPT_URL,             $url);      # устанавливаем адрес, который грабать будем
    curl_setopt($ch, CURLOPT_HEADER,          0);         # говорим, что нас не интересуют заголовки
    curl_setopt($ch, CURLOPT_USERAGENT,       $useragent); # прикидываемся, что мы браузер, чтобы наш ip не забанили
    curl_setopt($ch, CURLOPT_REFERER,         $referer);  # показываем, что типа мы пришли с google.com
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,  true);      # данные, полученные в результате запроса будут сохраняться в перменную, иначе все вывалится на экран
    $response = curl_exec($ch);                           # выполняем запрос
    curl_close($ch);                                      # закрываем дескриптор потока

    return $response;
  }
  

  # получаем ответ на POST-запрос, который отрабатывает вызовом AJAX
  function get_post_request($url, $market_id, $useragent, $output_encoding, $referer = 'https://vk.com', $i = 24) {
    $post_options_array = array(  # массив параметров POST-запроса для сервера vk, откуда будем напрямую грабать данные
      'al' => 1,
      'id' => '-'.$market_id,
      'load' => 1,
      'offset' => $i,
      'price_from' => '',
      'price_to' => '',
      'q' => '',
      'sort' => 0);
    $options_array = array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_REFERER => $referer,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => http_build_query($post_options_array),
      CURLOPT_USERAGENT => $useragent);
    $ch = curl_init();
    curl_setopt_array($ch, $options_array);
    $response = curl_exec($ch);
    curl_close($ch);
    
    # установим кодировку на выходе
    $response = iconv('CP1251', $output_encoding, $response);

    return $response;
  }


  # получаем ответ на POST-запрос, который прилетает при открытии позиции
  function get_post_request_item_view($url, $referer, $item_id, $market_id, $useragent, $output_encoding) {
    $post_options_array = array(  # массив параметров POST-запроса для сервера vk, откуда будем напрямую грабать данные
      'act' => 'show',
      'al' => 1,
      'from' => 'market',
      'lock' => 'market-'.$market_id,
      'query' => '{}',
      'w' => 'product-'.$market_id.'_'.$item_id.'/query');
    $options_array = array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_REFERER => $referer,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => http_build_query($post_options_array),
      CURLOPT_USERAGENT => $useragent);
    $ch = curl_init();
    curl_setopt_array($ch, $options_array);
    $response = curl_exec($ch);
    curl_close($ch);

    # установим кодировку на выходе
    $response = iconv('CP1251', $output_encoding, $response);

    return $response;
  }


  # некоторые картинки загружаются иногда не полностью, 
  #   для этого нужна затычка на проверку мин. размера
  function download_image_fully($url, $target, $min_image_size, $useragent) {
    $download_image_status = download_image($url, $target, $useragent);
    while ($download_image_status && filesize($target) < $min_image_size) {
      $download_image_status = download_image($url, $target, $useragent);      
    }

    return $download_image_status;     
  }


  # грузим себе фотку
  function download_image($url, $target, $useragent) {

    # тут удалим файл, если он есть, чтобы перезаписать
    if (file_exists($target)) {
      unlink($target);
    }

    # создадим новый файл для записи
    if (!$hfile = fopen($target, "w")) {
      return false;
    }
 
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FILE, $hfile);
 
    if(!curl_exec($ch)){
      curl_close($ch);
      fclose($hfile);
      unlink($target);  # удаляем файл в случае неудачи cURL'a
      return false;
    }
 
    fflush($hfile); # сбрасываем буфер вывода в файл
    fclose($hfile);
    curl_close($ch);
    return true;
  }

  # ------------------------------------------------------------------------
  # --[ phpQuery ]----------------------------------------------------------
  # ------------------------------------------------------------------------


  # парсим url-страницу, которую видим сразу при загрузке
  function parse_url_response($html_data, &$items_array, $img_dir, $useragent1, $useragent2, $post_url1, $post_url2, $market_id, $encoding) {
    phpQuery::newDocument($html_data);
    $market_list = pq('#market_list > .market_row');
    #$market_list = $doc->find('#market_list')->children('.market_row');
    foreach($market_list as $row) {
      $id = trim(pq($row)->attr('data-id'));
      $name = trim(pq($row)->find('.market_row_name > a')->text());
      $price = trim(pq($row)->find('.market_row_price')->text(), '$ ');
      settype($price, 'float');
      $small_image_url = pq($row)->find('.market_row_photo > a > img')->attr('src');
            
      $params = array();
      $params['name'] = $name;
      $params['price'] = $price;
      # загружаем маленькую картинку
      $small_image_target = $img_dir.'/small'.trim($id).'.jpg';
      if (file_exists($small_image_target)) {
        $params['small_image'] = $small_image_target;  
      } else {
        $params['small_image'] = download_image_fully($small_image_url, $small_image_target, 1024, $useragent2) == true ? $small_image_target : '';        
      }
      $params['small_image_url'] = $small_image_url;
      $params['small_image_size'] = file_exists($small_image_target) ? filesize($small_image_target) : 0;
      # загружаем описание и большую картинку
      $item = get_item_description_image($id, $post_url2, $post_url1, $market_id, $useragent1, $encoding);
      $params['description'] = $item['description'];
      $large_image_target = $img_dir.'/large'.trim($id).'.jpg';
      if (file_exists($large_image_target)) {
        $params['large_image'] = $large_image_target;  
      } else {
        $params['large_image'] = download_image_fully($item['image'], $large_image_target, 1024, $useragent2) == true ? $large_image_target : '';
      }
      $params['large_image_url'] = $item['image'];
      $params['large_image_size'] = file_exists($large_image_target) ? filesize($large_image_target) : 0;

      # вставляем данные в итоговый массив
      $items_array[$id] = $params;
        
    }
    phpQuery::unloadDocuments();
  }


  # парсим ответы POST-запросов, т.к. страница постоена на AJAX
  function parse_server_response($srv_data, &$items_array, $img_dir, $useragent1, $useragent2, $post_url1, $post_url2, $market_id, $encoding) {
    # чистим мусор в ответе с сервера
    $position = strpos($srv_data, '<div');
    $rem = substr($srv_data, 0, $position);
    $srv_data = str_replace($rem, '', $srv_data);
    $srv_data = str_replace('<!><!bool>1', '', $srv_data);

    # теперь строим phpDOM Object
    phpQuery::newDocument($srv_data);
    $market_list = pq('.market_row');
    foreach($market_list as $row) {
      $id = trim(pq($row)->attr('data-id'));
      
      $name = pq($row)->find('.market_row_name > a')->text();
      $price = trim(pq($row)->find('.market_row_price')->text(), '$ ');
      settype($price, 'float');
      $small_image_url = pq($row)->find('.market_row_photo > a > img')->attr('src');
            
      $params = array();
      $params['name'] = $name;
      $params['price'] = $price;
      # загружаем маленькую картинку
      $small_image_target = $img_dir.'/small'.trim($id).'.jpg';
      if (file_exists($small_image_target)) {
        $params['small_image'] = $small_image_target;
      } else {
        $params['small_image'] = download_image_fully($small_image_url, $small_image_target, 1024, $useragent2) == true ? $small_image_target : '';        
      }
      $params['small_image_url'] = $small_image_url;
      $params['small_image_size'] = file_exists($small_image_target) ? filesize($small_image_target) : 0;
      # загружаем описание и большую картинку
      $item = get_item_description_image($id, $post_url2, $post_url1, $market_id, $useragent1, $encoding);
      $params['description'] = $item['description'];
      $large_image_target = $img_dir.'/large'.trim($id).'.jpg';
      if (file_exists($large_image_target)) {
        $params['large_image'] = $large_image_target;
      } else {
        $params['large_image'] = download_image_fully($item['image'], $large_image_target, 1024, $useragent2) == true ? $large_image_target : '';
      }
      $params['large_image_url'] = $item['image'];
      $params['large_image_size'] = file_exists($large_image_target) ? filesize($large_image_target) : 0;
      
      # вставляем данные в итоговый массив
      $items_array[$id] = $params;
        
    }
    phpQuery::unloadDocuments();
  }


  # возвращаем массив с описанием и ссылкой на картинку
  function get_item_description_image($item_id, $post_url1, $post_url2, $market_id, $useragent, $output_encoding) {
    # получаем ответ по позиции, откуда будем вырезать описание
    $srv_data = get_post_request_item_view($post_url1, $post_url2, $item_id, $market_id, $useragent, $output_encoding);
    $ret = array(
      'description' => get_item_description($srv_data),
      'image' => get_item_image($srv_data));

    return $ret;
  }


  # возвращаем описание товара
  function get_item_description($srv_data) {
    # чистим мусор в ответе с сервера, парсеры в данном случае не помогут, т.к. и описание
    #   и комментарии и адрес почты заключены в одни и те же div-теги и phpDOM Object
    #   формируется кривой + присутствует javascript, с ним видимо phpQuery не особо дружит
    $s_start = '<div id="market_item_description" class="market_item_description">';
    $s_end = 'info@narint.com';
    $position_s = strpos($srv_data, $s_start);
    $position_e = strpos($srv_data, $s_end);
    $srv_data = substr($srv_data, $position_s + strlen($s_start), $position_e - strlen($s_start) - $position_s);
    $srv_data = strip_tags($srv_data, '<br> <br />');

    # стираем "По вопросам оптовых покупок пишите на рабочую почту:"
    $position_e1 = strrpos($srv_data, '.');
    $position_e1 = $position_e1 == false ? -1 : $position_e1;
    $position_e2 = strrpos($srv_data, '>');
    $position_e2 = $position_e2 == false ? -1 : $position_e2;
    
    if ($position_e1 > $position_e2) {
      $srv_data = substr($srv_data, 0, $position_e1 + 1);
    } else {
      $srv_data = substr($srv_data, 0, $position_e2 + 1);      
    }

    $srv_data = str_replace('Expand text…', '', $srv_data);
    $srv_data = str_replace('Показати повністю...', '', $srv_data);
    $srv_data = str_replace('Показати повністю…', '', $srv_data);
    $srv_data = str_replace('<br>', ' ', $srv_data);
    $srv_data = str_replace('<br />', ' ', $srv_data);
    $srv_data = str_replace('<br', ' ', $srv_data);
    $srv_data = str_replace('>', '', $srv_data);

    $position_m = strpos($srv_data, '{"type"');
    if ($position_m) {
      $srv_data = substr($srv_data, 0, $position_m);
    }
    $srv_data = trim($srv_data);

    return $srv_data;
  }


  # возвращаем ссылку на большую картинку
  function get_item_image($srv_data) {
    $s_start = '<img id="market_item_photo" class="market_item_photo" src=';
    $s_end = '/></div>';

    $position_s = strpos($srv_data, $s_start);
    $srv_data = substr($srv_data, $position_s + strlen($s_start));
    
    $position_e = strpos($srv_data, $s_end);
    $srv_data = substr($srv_data, 0, $position_e);

    $srv_data = trim(str_replace('"', '', $srv_data));

    return $srv_data;
  }


  # возвращает общее кол-во позиций товара
  function get_total($html_data) {
    phpQuery::newDocument($html_data);
    $amount = pq('#market_items_count')->text();
    phpQuery::unloadDocuments();  # чистим за собой мусор
    
    return $amount;
  }

  # ------------------------------------------------------------------------
  # --[ Техническая ф-я, которая нужна была для получения REFERER_X ]-------
  # ------------------------------------------------------------------------ 

  function save_user_agent() {
    $http_user_agent = $_SERVER['HTTP_USER_AGENT']."\n";
    echo $http_user_agent.'<br />';
    $handle = fopen('/tmp/user_agent.txt', 'a+');
    $res = fwrite($handle, $http_user_agent);
    
    if (!$res) {
      echo "Error data storage";
    } else {
      echo "Data was saved";
    }    
  }

  # ------------------------------------------------------------------------
  # --[ Техническая ф-я, нужна для вывода отладочных данных ]---------------
  # ------------------------------------------------------------------------

  function xd($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
  }

  ?>