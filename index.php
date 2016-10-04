<?php
  # ------------------------------------------------------------------------
  # ------------------------------------------------------------------------
  # --[ (1) cURL - нужен, для того, чтобы безопасно грабать данные страниц ]
  # --[     простой file_get_contents(URL) не годится, т.к. мы не ]---------
  # --[     имитируем действия браузера и наш ip могут забанить после ]-----
  # --[     нескольких таких запросов ]-------------------------------------
  # --[ (2) phpQuery - php-ый аналог jQuery, ]------------------------------
  # --[     нужен для парсинга контента и ответов c сервера ]---------------
  # --[ (3) mysqli - используем для подключения и работы с БД MySQL, ]------
  # --[     использование функций mysql не рекомендуется, т.к. они ]--------
  # --[     считаются устаревшими ]-----------------------------------------
  # ------------------------------------------------------------------------
  # ------------------------------------------------------------------------
  # --[ Действия для UNIX-системы]------------------------------------------
  # ------------------------------------------------------------------------
  # --[ 1. Для работы с curl'ом необходимо его установить командой: ]-------
  # --[ $ sudo apt-get install php5-curl ]----------------------------------
  # ------------------------------------------------------------------------
  # --[ 2. Для работы с phpQuery необходимо его скачать с git'а: ]----------
  # --[ $ git clone https://github.com/phpquery/phpquery.git phpQuery ]-----
  # --[ либо скачать в zip-архиве и распаковать в папку phpQuery ]----------
  # ------------------------------------------------------------------------
  
  header("Content-type:text/html; charset='utf-8'");

  ini_set("display_errors", "On");
  error_reporting(E_ALL);
	require('functions.php');    # подключаем модуль с нашими функциями

  # определяем константы
  define('OUTPUT_ENCODING', 'UTF-8');
  define('MARKET_ID', '107893492');
  define('IMG_DIR', '/tmp');
  define('URL', 'https://vk.com/market-107893492');
  define('POST_URL1', 'https://vk.com/al_market.php');  # ссылка для POST-запроса для имитации AJAX-обновления страницы списка товаров
  define('POST_URL2', 'https://vk.com/wkview.php'); # ссылка для POST-запроса для имитации открытия позиции списка товаров
  define('USERAGENT_1', 'Mozilla/5.0 (Windows NT 5.1; rv:43.0) Gecko/20100101 Firefox/43.0'); # winXP Firefox
  define('USERAGENT_2', 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; InfoPath.3; .NET CLR 2.0.50727; .NET CLR 3.0.04506.648; .NET CLR 3.5.21022; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)'); # winXP IE
  define('USERAGENT_3', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.63 Safari/537.36'); # LINUX
  define('USERAGENT_4', 'Mozilla/5.0 (Linux; Android 4.4.2; JINGA_IGO_M1 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36'); # Android
  
  define('DB_HOST',   '127.0.0.1');
  define('DB_PORT',    3306);
  define('DB_USER',   'coder');
  define('DB_PWD',    'pas127');
  define('DB_NAME',   'vk_grab');

  define('DELAY', 4*60*60);

  # функция проверки ошибок
  function is_error($val) {
    if (is_bool($val) && !$val) {
      return true;
    }
    return false;
  }


  # основное тело программы
  function main () {
    $items_array = array();
    $iter = 0;
    $html_data = get_html_data(URL, USERAGENT_4);
    $total_of_positions = get_total($html_data);
    parse_url_response($html_data, $items_array, IMG_DIR, USERAGENT_1, USERAGENT_2, POST_URL1, POST_URL2, MARKET_ID, OUTPUT_ENCODING); # первые 24 позиции получаем с основной страницы
    
    # остальные позиции получаем порциями по 24 с сервера
    while ($iter < $total_of_positions) {
      $srv_response = get_post_request(POST_URL1, MARKET_ID, USERAGENT_1, OUTPUT_ENCODING, URL, $iter + 24);
      parse_server_response($srv_response, $items_array, IMG_DIR, USERAGENT_1, USERAGENT_2, POST_URL1, POST_URL2, MARKET_ID, OUTPUT_ENCODING);
      $iter += 24;
    }
    /*
    */
    $conn = connect_to_db(DB_HOST, DB_USER, DB_PWD, DB_NAME);
    if (is_error($conn)) {
      return;
    }
    
    $db_array = select_data($conn);
    if (is_error($db_array)) {
      return;
    }

    # заведем массив, в котором будем хранить обработанные элементы
    $proc_array = array();    

    # если БД пустая, то наполним ее
    if(!count($db_array)) {
      insert_array($conn, $items_array);    

    } else {
      # начнем сравнение полученных данных с сайта и из БД
      foreach ($items_array as $key => $val) {
        # ищем по ключу наличие позиции в БД
        if (array_key_exists($key, $db_array)) {
            
          $res_search = $db_array[$key];
            
          if (trim($res_search['name'])         != trim($val['name'])
            || trim($res_search['description']) != trim($val['description'])
            || $res_search['price']             != $val['price']) {
            update_item($conn, $key, $val);
          }


        } else {
          
          # если не нашли, то проверим непосредственно в базе данных с пометкой 'marked'
          $res = get_marked_item($conn, $key);
          if (is_error($res)) {
            return;
          }

          if (!count($res)) { # нету такого элемента, значит добавим
            $res_ins = insert_item($conn, $key, $val['name'], $val['description'], $val['price'], 
                                    $val['small_image'], $val['small_image_size'], $val['large_image'], $val['large_image_size']);
            if(!$res_ins) {
              echo "Не удалось загрузить строку в БД<br />";
            }
          } else { # есть такой элемент, значит снимем пометку
            $res_upd = update_mark($conn, $key, false);
            if(!$res_upd) {
              echo "Не удалось обновить строку в БД<br />";
            }
          }
        }
          
        $proc_array[] = $key;
      }
        
      # теперь проверим наоборот, вдруг в БД есть, что уже не актуально
      foreach ($db_array as $key => $val) {
        if (!in_array($key, $proc_array)) { 
          # не нашли нужный элемент, значит он не актуальный, помечаем его
          $res_upd = update_mark($conn, $key);
          if(!$res_upd) {
            echo "Не удалось обновить строку в БД<br />";
          }
        }
      }
    }
    
    close_connection($conn);
    
    echo 'Загружено позиций: '.count($items_array).'<br />';
    echo 'Детализация:<br />';
    xd($items_array);
    
    #echo 'Web-page worked...';

  }


  /*

  while (true) {
    main();
    time.sleep(DELAY);
  }
  */

  main();
  
  
?>