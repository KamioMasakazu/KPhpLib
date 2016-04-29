<?php
/**
 *	メニュー定義（makeMenu()用）
 *	Selection メニューを選択状態にする場合に指定する文字列。一致した文字列の前後に%をつけた文字列が"Html"の定義中に存在する場合、その文字列を「class="Selected"」で置き換える。Selectionに存在し、"Html"の定義中に存在しなかった文字列は「class="Unselected"」で置き換える。
 *	Html メニューを定義する文字列。「%文字列%」は置換対象であることを意味する。「%depth%」はwriteMenu()のdepth引数の値で置換される（デフォルトは"/"）。"Selection"の配列に指定された文字列を%で囲んだものは選択状態を表すCSSのclassに置換される。
 */
$MenuConf = array(
	"Selection" => array("TopPage", "Service", "Portfolio", "Contact"),
	"Html" => '
	<div id="MenuArea">
		<ul id="MainMenu">
			<li %TopPage%><a href="%depth%">トップページ</a></li>
			<li %Service%><a href="%depth%Service/">サービス</a></li>
			<li %Portfolio%><a href="%depth%Portfolio/">ポートフォリオ</a></li>
			<li %Contact%><a href="%depth%Contact/">お問合せ</a></li>
		</ul>
	</div>
	',
);

/**
 *	ヘッダ定義（writeHeader()用）
 *	Html ヘッダを定義する文字列。
 */
$HeaderConf = array(
	"Html" => '
	<div id="PageHeader">
		<div id="CompanyName">神尾ソフトウェア研究所</div>
		<div id="BannerArea">K=!0</div>
	</div>
	',
);

/**
 *	フッタ定義
 *	Html フッタを定義する文字列。「%文字列%」は置換対象であることを意味する。「%depth%」はwriteFooter()のdepth引数の値で置換される（デフォルトは"/"）。
 */
$FooterConf = array(
	"Html" => '
	<div id="PageFooter">
		<ul id="FooterMenu">
			<li id="FLeft" class="spacer">&nbsp;</li>
			<li id="FMiddle" class="spacer">&nbsp;</li>
			<li id="FRight"><a href="#">▲</a></li>
		</ul>
	</div>
	',
);
