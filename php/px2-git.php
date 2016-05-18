<?php
/**
 * px2-git
 */
namespace tomk79\pickles2\git;

/**
 * px2-git
 */
class main{

	private $git;
	private $req;
	private $path_entry_script;
	private $path_homedir;
	private $path_controot;
	private $path_docroot;
	private $path_git_home;

	/**
	 * constructor
	 *
	 * @param string $px Pickles 2 オブジェクト または entry_script のパス
	 * @param array $options オプション
	 */
	public function __construct( $px, $options = array() ){
		$this->git = new \PHPGit\Git();
		if( strlen($options['bin']) ){
			$this->git->setBin( $options['bin'] );
		}
		$this->req = new \tomk79\request();

		if( is_string($px) && is_file($px) ){
			$this->path_entry_script = $px;
			$this->path_homedir = json_decode( $this->passthru(array(
				'php',
				$this->path_entry_script,
				'/?PX=api.get.path_homedir'
			)) );
			$this->path_controot = json_decode( $this->passthru(array(
				'php',
				$this->path_entry_script,
				'/?PX=api.get.path_controot'
			)) );
			$this->path_docroot = json_decode( $this->passthru(array(
				'php',
				$this->path_entry_script,
				'/?PX=api.get.path_docroot'
			)) );
		}elseif( is_object($px) ){
			$this->path_homedir = $px->get_path_homedir();
			$this->path_controot = $px->get_path_controot();
			$this->path_docroot = $px->get_path_docroot();
		}

		// finding Repository path
		$base_path = $this->path_entry_script;
		while(1){
			if( @is_dir($base_path.'/.git/') ){
				$this->path_git_home = $base_path;
				break;
			}
			if( $base_path == dirname($base_path) ){
				// not found
				break;
			}
			$base_path = dirname($base_path);
		}
		// var_dump($this->path_git_home);
		$this->git->setRepository( $this->path_git_home );

	}

	/**
	 * gitリポジトリを初期化する
	 * @param  string $path	リポジトリを作成するディレクトリ
	 * @param  array  $options オプション
	 * `array('shared' => true, 'bare' => true)`
	 * @return bool			result
	 */
	public function init($path,  $options = array()){
		if( file_exists( $path.'/.git' ) ){
			return false;
		}
		// var_dump($path);
		// var_dump($options);
		$res = $this->git->init($path, $options);
		// var_dump($res);
		return $res;
	}

	/**
	 * git log
	 * @return array result
	 */
	public function log(){
		// $logs = array();
		$logs = $this->git->log();
		// var_dump($logs);
		return $logs;
	}

	/**
	 * commit sitemap
	 * @return array result
	 */
	public function commit_sitemap($commit_message = ''){
		$path_sitemap = $this->path_homedir.'sitemaps/';
		// var_dump( $path_sitemap );
		$this->git->add($path_sitemap."/sitemap.csv", array());
		// $this->git->add($path_sitemap."/sitemap.xlsx", array());

		try {
			// throw new \Exception("Some error message");
			$res = $this->git->commit(
				trim('update Sitemap: '.$commit_message),
				array()
			);
		} catch(\Exception $e) {
			echo $e->getMessage();
		}
		// var_dump($res);

		return $res;
	}


	/**
	 * コマンドを実行し、標準出力値を返す
	 * @param array $ary_command コマンドのパラメータを要素として持つ配列
	 * @return string コマンドの標準出力値
	 */
	private function passthru( $ary_command ){
		set_time_limit(60*10);
		$cmd = array();
		foreach( $ary_command as $row ){
			$param = '"'.addslashes($row).'"';
			array_push( $cmd, $param );
		}
		$cmd = implode( ' ', $cmd );
		ob_start();
		passthru( $cmd );
		$bin = ob_get_clean();
		set_time_limit(30);
		return $bin;
	}

}