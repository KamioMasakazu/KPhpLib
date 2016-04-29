<?php
//=============================================================================
//	ファイル一覧を作成するライブラリ関数群
//=============================================================================

//=============================================================================
//	ファイル一覧を出力する
//	getFileListHTML() 呼び出し関数
//	getFileTitle() プライベート関数
//-----------------------------------------------------------------------------
/**
 *	指定されたファイル（HTML）からタイトルとなる文字列を取り出す。
 *	@param dir ターゲットのディレクトリ
 *	@param file ターゲットファイル
 *	@param pattern 対象ファイルから文字列を取り出すための正規表現
 *	@return 取得した文字列（なかったら空文字列）
 */
function getFileTitle($dir, $file, $pattern){
	if(empty($dir) || empty($file) || empty($pattern)){
		return "";
	}

	$path = $dir . "/" . $file;
	$ret = "";

	$fp = fopen($path, "r");
	while(!feof($fp)){
		$line = fgets($fp);
		if(preg_match($pattern, $line, $match)){
			$ret = $match[1];
			break;
		}
	}
	fclose($fp);
	return $ret;
}

/**
 *	指定されたディレクトリのfname_patternに一致するファイルを取得して次のようなHTMLを返す。
 *		<tag><a href="path/to/file">タイトル</a></tag>
 *	@param path 対象ディレクトリ
 *	@param fname_pattern 対象とするファイル名の正規表現
 *	@param title_pattern タイトル文字列を対象とするファイルから取得するための正規表現
 *	@param tag アンカータグをさらに囲むタグの<と>の間の文字列
 *	@return HTML文字列
 */
function getFileListHTML($path, $fname_pattern, $title_pattern, $tag=null){
	if(empty($path) || empty($fname_pattern) || empty($title_pattern)){
		return "";
	}

	$st_tag = "";
	$ed_tag = "";
	if(!empty($tag)){
		$st_tag = '<' . $tag . '>';
		$ed_tag = '</' . $tag . '>';
	}

	$files = array_filter(scandir($path), function ($file) use ($fname_pattern) {
		return preg_match($fname_pattern, $file);
	});

	$list = "";
	foreach($files as &$file) {
		$list .= $st_tag . '<a href="' . $path . '/' . $file . '">' . getFileTitle($path, $file, $title_pattern) . "</a>" . $ed_tag . "\n";
	}
	echo $list;
}

//=============================================================================
//	ディレクトリを検索し、一覧を作成する。
//	listChildContents() 呼び出し関数
//	parseChildContent() プライベート関数
//-----------------------------------------------------------------------------
/**
 *	$dir, $fileに指定されたファイルを読み込んで$formatに基づいた文字列を返す。
 *	$format中の'$dir'は$dirの値に、'$file'は$fileの値に置換される。
 *	$patternはキーを$format中の置換文字列、値は$dir/$pathのファイルから置換文字列を検索するための正規表現とする連想配列。
 *	（例）次の場合、対象のファイルから「ListTitle:」に続く文字を取得して$format中の'%title%'を、「ListDesc:」に続く文字を取得して$format中の'%text%'を置換する。
 *	$pattern = array("%title%"=>'/ListTitle: (.+)/', "%text%"=>'/ListDesc: (.+)/');
 *
 *	@param dir ファイル検索対象のディレクトリ
 *	@param file 対象ファイル名
 *	@param format 置換後の文字列の書式
 *	@param $pattern 置換文字列を検索するための連想配列。
 */
function parseChildContent($dir, $file, $format, $pattern){
	$path = $dir . "/" . $file;

	# $format内の"$dir"と"$file"を置換する
	$format = str_replace('$dir', $dir, $format);
	$format = str_replace('$file', $file, $format);

	do{
		$fp = fopen($path, "r");
		if($fp == false){
			return "File is not exist(" . $path . ")";
		}

		$content = fread($fp, filesize($path));

		foreach($pattern as $key => $val){
			if(preg_match($val, $content, $match)){
				$format = str_replace($key, $match[1], $format);
			}
		}


	}while(false);

	fclose($fp);

	return $format;
}

/**
 *	path以下のdname_patternを持つディレクトリを順次検索し、そのディレクトリ内のtargetファイルからpatternに定義された置換文字列パターンを検索してformatの書式に従って整形した文字列を返す。
 *
 *	@param path 検索対象のパス。
 *	@param dname_pattern $path以下の検索対象とするディレクトリ名のパターン（正規表現）。「.」と「..」は無視する。
 *	@param target 置換文字列を読み込む対象のファイル名。「path/dname_patternに一致するディレクトリ/target」を読み込むので各ディレクトリには同名のファイルがなければならない。
 *	@param format 置換後の文字列の書式定義。formatに'$dir'、'$file'が含まれる場合'$dir'は「path/dname_patternに一致するディレクトリ」に、'$file'はtargetの値に置換される。
 *	@param pattern 置換文字列をキー、targetから検索する文字列パターン（正規表現）をキーとした連想配列。
 *	（例）次の場合、対象のファイルから「ListTitle:」に続く文字を取得して$format中の'%title%'を、「ListDesc:」に続く文字を取得して$format中の'%text%'を置換する。
 *	$pattern = array("%title%"=>'/ListTitle: (.+)/', "%text%"=>'/ListDesc: (.+)/');
 */
function listChildContents($path, $dname_pattern, $target, $format, $pattern){
	if(empty($path) || empty($dname_pattern)|| empty($target) || empty($format) || empty($pattern)){
		echo "BadParameter";
		return ;
	}

	$dirs = array_filter(scandir($path), function ($name) use ($path, $dname_pattern) {
		if(($name == ".") || ($name == "..")){
			return false;
		}

		$f = $path . "/" . $name;
		if(filetype($f) != "dir"){
			return false;
		}

		return preg_match($dname_pattern, $name);
	});

	foreach($dirs as &$dir){
		echo parseChildContent($path . "/" . $dir, $target, $format, $pattern) . "\n";
	}
}
