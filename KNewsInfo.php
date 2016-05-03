<?php

/**
 *	ロックファイルをチェックして作成する。
 *	ロックファイルを作成できたらtrue、できなかったらfalseを返す。
 */
function _lock($config){
	$timeout = 0;
	while(symlink($config["file"], $config["lock"]) === false){
		usleep(10*1000);
		$timeout += 1;
		if($timeout >= 100){
			return false;
		}
	}
	chmod($config["lock"], 0646);
	return ture;
}

/**
 *	ロックファイルを削除する
 */
function _unlock($config){
	if(file_exists($config["lock"])){
		return unlink($config["lock"]);
	}
	return true;
}

/**
 *	対象のJSONファイルを読み込んで連想配列を返す。
 *	@param filename 対象ファイルのパス
 *	@return JSONをパースした連想配列、失敗時はnull
 */
function getNewsJson($filename){
	$ret = null;

	do{
		$h = fopen($filename, "r");
		if($h === false){
			break;
		}

		$ret = json_decode(fread($h, filesize($filename)), true);
		if($ret === false){
			$ret = null;
		}
	}while(false);

	if($h !== false){
		fclose($h);
	}

	return $ret;
}

/**
 *	対象のJSONファイルを書き込む。
 *	@param filename 対象ファイルのパス
 *	@param json JSON文字列
 *	@return true:成功、flase:失敗
 */
function setNewsJson($filename, $json){
	$ret = false;

	do{
		$h = fopen($filename, "w");
		if($h === false){
			break;
		}

		$w = fwrite($h, $json);
		if($w === false){
			break;
		}
		else{
			$ret = true;
		}
	}while(false);

	if($h !== false){
		fclose($h);
	}

	return $ret;
}

/**
 *	fnameのJSONを読み込み、その中の要素数だけformatに従って置換した文字列を出力する。
 *	JSONは次の書式でなければならない。
 *		[
 *			{
 *				"%param1%": "A Parameter",
 *				"%param2%": "Other Parameter",
 *				...
 *			},
 *			{
 *				...
 *			}
 *		]
 *	formatの置換はJSONの各エントリのうちキー文字列に一致するものを、キーに対応する値で置換する。
 *	そのためキー文字列はformat中で置換対象文字列として認識可能でなければならない。もし、format中に（置換対象として期待しない）置換対象と同じ文字列が出現した場合、それも置換されてしまう。
 *
 *	@param fname JSONファイル
 *	@param format 文字列置換のテンプレート文字列
 */
function getNewsHtml($fname, $format){
	$json = getNewsJson($fname);
	if(empty($json)){
		return;
	}

	foreach($json as &$entry){
		$keys = array_keys($entry);
		$vals = array_values($entry);

		$ret = str_replace($keys, $vals, $format);
		echo $ret . "\n";
	}
}

/**
 *	runSender()へのget要求の応答メッセージを作成して返す。
 *	@param $config 設定情報
 *	@return 応答メッセージ
 */
function getSenderResponseGet($config){
	if(empty($config["file"])){
		return '{"Response": "NoJsonFile"}';
	}

	$data = getNewsJson($config["file"]);
	if(empty($data)){
		return '{"Response": "NoData"}';
	}

	$json = array(
		"Response" => "Success",
		"Data" => $data,
	);
	$ret = json_encode($json);

	if($ret === false){
		return '{"Response": "BadJson"}';
	}

	return $ret;
}

/**
 *	jsonの値に含まれるHTMLタグをエスケープする。
 *	クォーテーション（”と'）はエスケープしない。
 */
function _escapeText($json){
	foreach($json as &$entry){
		foreach($entry as $key => $val){
			$esc = htmlspecialchars($val, ENT_NOQUOTES | ENT_HTML5, 'utf-8');
			$entry[$key] = $esc;
		}
	}
	error_log(print_r($json, true));
	return $json;
}

/**
 *	runSender()へのset要求を処理し応答メッセージを作成して返す。
 *	@param $config 設定情報
 *	@param data ファイルに書き込むJSONデータ
 *	@return 応答メッセージ
 */
function getSenderResponseSet($config, $data){
	$data = array_slice($data, 0, $config["maxEntry"]);
	$data = _escapeText($data);

	$json = json_encode($data);
	if($json === false){
		return '{"Response": "CannotDecodeJson"}';
	}

	$s = setNewsJson($config["file"], $json);
	if($s === false){
		return '{"Response": "FileWriteFailed"}';
	}

	$ret = array(
		"Response" => "Success",
		"Data" => $data,
	);

	return json_encode($ret);
}

/**
 *	runSender()へのadd要求を処理し応答メッセージを作成して返す。
 *	既存データを読み込み、新規のデータ追加し、getSenderResponseSet()に処理を移譲する。
 *	@param $config 設定情報
 *	@param data ファイルに追加するJSONデータ
 *	@return 応答メッセージ
 */
function getSenderResponseAdd($config, $data){
	$old = getNewsJson($config["file"]);
	if(empty($old)){
		$old = $data;
	}
	else{
		foreach($data as &$val){
			array_unshift($old, $val);
		}
	}

	return getSenderResponseSet($config, $old);
}

/**
 *	応答を出力する
 *	@param callback JSONPのコールバック
 *	@param message 応答メッセージ
 */
function responseNewsInfo($callback, $message){
	// jsonとjsonpで応答を分ける
	if($callback){
		echo $callback . "(" . $message . ")";
	}
	else{
		echo $response_json;
	}
}

/**
 *	新着情報の取得、更新要求を処理するサーバ
 *
 */
function runSender($config=null){
	// 応答メッセージ
	$message = "";

	do{
		if(empty($config)){
			$message = '{"Response": "NoConfiguration"}';
			break;
		}

		// jsonpのcallbackのパラメータを取得
		$callback = $_GET['callback'];

		// JSONのPOSTを受け取る
		$json_string = file_get_contents('php://input');
		$json = json_decode($json_string, true);        // 連想配列にするためtrueを指定
		if($json === false){
			$message = '{"Response": "BadJsonGet"}';
			break;
		}

		switch ($json["Request"]){
			case "get":
				$message = getSenderResponseGet($config);
				break;
			case "set":
				if(_lock($config)){
					$message = getSenderResponseSet($config, $json["Data"]);
					_unlock($config);
				}
				else{
					$message = '{"Response": "FileLocked"}';
				}
				break;
			case "add":
				if(_lock($config)){
					$message = getSenderResponseAdd($config, $json["Data"]);
					_unlock($config);
				}
				else{
					$message = '{"Response": "FileLocked"}';
				}
				break;
			default:
				$message = '{"Response": "BadRequest"}';
				break;
		}

	}while(false);

	responseNewsInfo($callback, $message);
}
