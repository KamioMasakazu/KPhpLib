<?php
/**
 *	セッションを開始する。
 *	@param sname セッション名
 *	@return true:成功、エラーメッセージ:失敗
 */
function startSession($sname=null){
	if(empty($sname)){
		return "BadSession";
	}

	session_name($sname);
	if(session_name() != $sname){
		echo "Session Error";
	}
	else {
		session_start();
	}
}

/**
 *	セッションをチェックする
 *	@param sname セッション名
 *	@return true:成功、エラーメッセージ:失敗
 */
function checkSession($sname=null){
	if(empty($sname)){
		return "BadSession";
	}

	session_name($sname);
	$ss_id = $_COOKIE[$sname];

	if($ss_id !== null && file_exists(session_save_path() . DIRECTORY_SEPARATOR . 'sess_' . $ss_id)){
		session_start();
		return true;
	}
	else{
		return "SessionError";
	}
}

/**
 *	セッションを破棄する
 */
function DestroySession(){
	setcookie(session_name(), '', 1, '/');
	$_SESSION = array();
	session_destroy();
}
