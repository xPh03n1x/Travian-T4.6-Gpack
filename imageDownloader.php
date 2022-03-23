<?php

new class($argv){

	private $cdn='https://gpack.travian.com/c3f4a986/';
	private $initDir;

	private $scriptsToParse=array();
	private $imagesToFetch=array();

	private $downloadedImages=0;

	private $overWriteExistingFiles=false; // Download already downloaded files

	private $request,$cssFile,$css;

	public function __construct($args){
		if(!file_exists($args[1])){
			die("The requested CSS file doesn't exist!\n");
		}

		$this->initDir=__DIR__.'/';

		$this->request=pathinfo($args[1]);

		$this->cssFile=$this->request['basename'];
		array_push($this->scriptsToParse, $this->cssFile);

		chdir($this->request['dirname']);

		$this->parseScript();
	}

	private function findImportedScripts(){
		preg_match_all('/\@import.*\"(.*)\"\;/m', $this->css, $matches, PREG_SET_ORDER, 0);
		if(!empty($matches)){
			foreach($matches as $m=>$d){
				if(isset($d[1])){
					array_push($this->scriptsToParse, $d[1]);
				}
			}
		}

		echo $this->cssFile.": Found additional ".count($matches)." scripts to parse!\n";
	}

	private function parseScript($pos = 0){
		$this->cssFile=$this->scriptsToParse[$pos];
		$this->css=file_get_contents($this->scriptsToParse[$pos]);

		$this->findImportedScripts();

		preg_match_all('/\'\.\.\/(.*?)\'/i', $this->css, $matches);
		if(!empty($matches[1])){
			echo "Found total of ".count($matches[1])." links. Fetching ...\n";
			$this->processFoundURLs($matches[1]);
		}

		// ...
		$next=$pos+1;
		if($next<count($this->scriptsToParse)){
			return $this->parseScript($next);
		}
		echo "Downloaded a total of ".$this->downloadedImages." images!\n";
	}

	private function processFoundURLs($urls){
		foreach($urls as $k=>$url){
			$temp=pathinfo($url);
			$relPath=str_replace($this->initDir,'', realpath('../')).'/';
			$fullPath=$this->initDir.$relPath.$temp['dirname'].'/';

			$fullURL=$this->cdn.$relPath.$url;

			$targetFile=$fullPath.$temp['basename'];

			if(!file_exists($targetFile) || $this->overWriteExistingFiles){

				# Ensure the target directory exists
				$this->make_dir($fullPath);

				# Download
				shell_exec('wget '.$fullURL.' -o '.$this->initDir.'/dl.log'.' -O '.$targetFile);

				$this->downloadedImages++;
			}
		}
	}

	private function make_dir($path, $permissions=0755){
		return is_dir($path) || mkdir($path, $permissions, true);
	}

};
