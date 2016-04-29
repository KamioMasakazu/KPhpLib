<?php
require_once("Mail.php");
require_once("KSessionManager.php");

/**
 *	メールアドレスのホストの存在をチェックする。
 *	@param email 対象のメールアドレス
 */
function checkValidEmailHost($email){
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return false;
	}
	$host = substr($email, strrpos($email, '@') + 1);
	$check_recs = dns_get_record($host, DNS_MX);
	return isset($check_recs[0]["target"]);
}

/**
 *	入力データをチェックする
 *	@param data 受け取ったJSONを連想配列に変換したもの
 */
function checkData(&$data){
	if($data === null){
		return "NullData";
	}

	if(empty($data["Customer"])){
		return "NoName";
	}
	if(empty($data["Email"])){
		return "NoEmail";
	}
	if(!filter_var($data["Email"], FILTER_VALIDATE_EMAIL)){
		return "BadEmail";
	}
	if(!checkValidEmailHost($data["Email"])){
		return "UnknownHost";
	}
	if(empty($data["Text"])){
		return "NoText";
	}
	return true;
}

/**
 *	メールの本文を作成する。
 *	@param data 受け取ったデータ
 *	@return メール本文文字列
 */
function makeMailBodyDefault(&$data){
	return "Default Message";
}

/**
 *	メールのサブジェクトを作成する
 *	@param data 受け取ったデータ
 *	@return サブジェクト文字列
 */
function makeMailSubjectDefault(&$data){
	return "Default Subject";
}

/**
 *	メールを送信する。
 *	@param config メールサーバなどの設定
 *		array(
 *			"to" => "foo@bar.com",
 *			"server" => array(
 *				"host" => "127.0.0.1",
 *				"port" => 25,
 *				"auth" => false,
 *			),
 *		)
 *	@param data 内容データ
 *	@param subjectMaker メールサブジェクト作成用コールバック関数
 *	@param bodyMaker メール本文作成用コールバック関数
 */
function sendMail(&$config, &$data, $subjectMaker, $bodyMaker){
	$params = array(
		"host" => $config["server"]["host"],
		"port" => $config["server"]["port"],
		"auth" => $config["server"]["auth"],
	);

	$mailObject = Mail::factory("smtp", $params);

	$recipients = $config["to"];
	$headers = array(
		"To" => $config["to"],
		"From" => $data["Email"],
		"Subject" => mb_encode_mimeheader($subjectMaker($data))
	);

	$body = $bodyMaker($data);
	$encoding = mb_detect_encoding($body, "UTF-8,EUC-JP,SJIS,JIS");
	if($encoding != "JIS"){
	  $body = mb_convert_encoding($body, "JIS", $encoding);
	}

	$body = mb_convert_kana($body, "KVa");

	return $mailObject -> send($recipients, $headers, $body);
}

/**
 *	応答を出力する
 *	@param callback JSONPのコールバック
 *	@param message 応答メッセージ
 */
function responseContact($callback, $message){
	// 応答データを作成
	$response_json = "{\"Response\":\"" . $message . "\"}";

	// jsonとjsonpで応答を分ける
	if($callback){
		echo $callback . "(" . $response_json . ")";
	}
	else{
		echo $response_json;
	}
}

/**
 *	セッションをチェックする
 *	@return true:成功、"SessionError":失敗
 */
//function checkSession(){
//	session_name("KContactSession");
//	$ss_id = $_COOKIE["KContactSession"];
//
//	if($ss_id !== null && file_exists(session_save_path() . DIRECTORY_SEPARATOR . 'sess_' . $ss_id)){
//		session_start();
//		return true;
//	}
//	else{
//		return "SessionError";
//	}
//}

/**
 *	セッションを破棄する
 */
//function DestroySession(){
//	setcookie(session_name(), '', 1, '/');
//	$_SESSION = array();
//	session_destroy();
//}

function runSender($config=null, $subjectMaker="makeMailSubjectDefault", $bodyMaker="makeMailBodyDefault"){
	// 応答メッセージ
	$message = "";

	// jsonpのcallbackのパラメータを取得
	$callback = $_GET['callback'];

	do{
		// セッションが存在するかをチェック
		$ret = checkSession("KContactSession");
		if($ret !== true){
			$message = $ret;
			break;
		}

		// JSONのPOSTを受け取る
		$json_string = file_get_contents('php://input');
		$json = json_decode($json_string, true);        // 連想配列にするためtrueを指定

		// 入力値チェック
		$ret = checkData($json);
		if($ret !== true){
			$message = $ret;
			break;
		}

		// メール送信
		$ret = sendMail($config, $json, $subjectMaker, $bodyMaker);
		if($ret !== true){
			$message = "ServerError";
		}
		else{
			$message = "Success";
		}

		DestroySession();
	}while(false);

	responseContact($callback, $message);
}
