<?php
//=============================================================================
//	ページ構造構築用ライブラリ
//=============================================================================

/**
 *	メニューのリンクを構築する。
 *	@param $mtitle 現在のページのタイトル（一致するページが選択状態になる）
 *	@param $depth ドキュメントルートからのパス。未指定時は「/（絶対パス指定）」になる。
 *	@param $conf 設定ファイル(php)を指定する。未指定時は「conf/menu_conf.php」になる。
 */
function writeMenu($mtitle="", $depth = "/", $conf="conf/page_conf.php"){
	include($conf);

	$html = $MenuConf["Html"];

	// depthを置換
	$html = str_replace("%depth%", $depth, $html);

	// Selectionに一致するもののclassをSelectedに、それ以外をUnselectedにする。
	if(is_array($MenuConf["Selection"])){
		foreach ($MenuConf["Selection"] as $m) {
			$to_rep = "%" . $m . "%";
			$sel = 'class="Unselected"';

			if($m == $mtitle){
				$sel = 'class="Selected"';
			}

			$html = str_replace($to_rep, $sel, $html);
		}
	}

	echo $html;
}

/**
 *	$confに定義された$HeaderHtmlの文字列を出力する
 */
function writeHeader($conf="conf/page_conf.php"){
	include($conf);
	echo $HeaderConf["Html"];
}

/**
 *	$confに定義された$FooterHtmlの文字列を出力する
 */
function writeFooter($conf="conf/page_conf.php", $depth = "/"){
	include($conf);
	$html = $FooterConf["Html"];

	// depthを置換
	$html = str_replace("%depth%", $depth, $html);

	echo $html;
}
