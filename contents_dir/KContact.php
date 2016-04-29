<?php
require_once("../../KPhpLib/KContactMail.php");

/******************************************************************************
 * メール送信設定
 *****************************************************************************/
$KContactConfig = array(
	"to" => "kamio",			// 宛先アドレス
	// サーバ設定
	"server" => array(
		"host" => "127.0.0.1",	// サーバIP、ホスト名
		"port" => 25,			// ポート番号
		"auth" => false,		// 認証（未実装）
	),
);

/******************************************************************************
 * メールを構築するコールバック関数が必要なら定義する
 *****************************************************************************/

/**
*	メールのサブジェクトを作成する
*	@param data 受け取ったデータ
*	@return サブジェクト文字列
*/
function makeMailSubject(&$data){
	$corp = trim($data["Corp"]);
	$cust = trim($data["Customer"]);

	if (empty($corp)){
		return "【問い合わせ】 " . $cust;
	}
	else{
		return "【問い合わせ】 " . "[" . $corp . "] " . $cust;
	}
}

 /**
  *	メールの本文を作成する。
  *	@param data 受け取ったデータ連想配列（JSON）
  *	@return メール本文文字列
  */
function makeMailBody(&$data){
	$body = "";

	$body .= "[顧客情報]\n";
	$body .= "氏名  : " . $data["Customer"] . "\n";
	$body .= "会社  : " . $data["Corp"] . "\n";
	$body .= "E-Mail: " . $data["Email"] . "\n";
	$body .= "[問い合わせ内容]\n";
	$body .= $data["Text"];
	$body .= "\n";

	return $body;
}

/******************************************************************************
 * main
 ******************************************************************************/
runSender($KContactConfig, "makeMailSubject", "makeMailBody");
