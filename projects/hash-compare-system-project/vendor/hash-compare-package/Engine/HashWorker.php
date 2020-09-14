<?php
namespace HashCompare\Engine;

class HashWorker {
	
	//Folder name of your project
	const PROJECT_FOLDER_NAME = 'hash-compare-system-project';
	
	//Cache file path and name
	const CACHE_FILE_LOCATION_RELATIVELY_TO_ROOT = '/vendor/hash-compare-package/cache/hashes_etalon.txt';

	//Dont change
	const ROOT_PATH_RELATIVELY_TO_WORKER = '../../../';
	
	//Setup the config of the folders you want to track
	public static function getconfig(){
		return [
			self::ROOT_PATH_RELATIVELY_TO_WORKER.self::PROJECT_FOLDER_NAME . '/public' => [
				'exclude' => [
					self::ROOT_PATH_RELATIVELY_TO_WORKER.self::PROJECT_FOLDER_NAME . '/public/images',
				]
			],
		];
	}
	
	/**
	 * @return array
	 */
	public static function hashgen()
	{
		//Generates hash sums for folders
		$list_to_check = self::getconfig();
		$check_hashes = [];
		
		foreach ($list_to_check as $path => $options) {
			$path_exploded = explode(self::ROOT_PATH_RELATIVELY_TO_WORKER.self::PROJECT_FOLDER_NAME, $path);
			$path_for_key = $path_exploded[1];
			if (is_dir($path)) {
				$check_hashes[$path_for_key] = self::hashDirectory($path, $options);
			}
		}

		$all_file_hashes_array = [];
		array_walk_recursive(
			$check_hashes,
			function ($item, $key) use (&$all_file_hashes_array) {
				$all_file_hashes_array[$key] = $item;
			}
		);
		return $all_file_hashes_array;
	}

	/**
	 * @param $directory
	 * @return array|bool
	 */
	public static function hashFilesInSingleDirectory($directory)
	{
		if (!is_dir($directory)) {
			return false;
		}

		$file_hash = array();
		$dir = dir($directory);

		while (false !== ($file = $dir->read())) {
			if ($file != '.' and $file != '..') {
				{
					if (!is_dir($directory . '/' . $file)) {
						if (!is_link($directory . '/' . $file)) {
							$file_hash['/' . $file] = md5_file($directory . '/' . $file);
						}
					}
				}
			}
		}
		$dir->close();

		return $file_hash;
	}

	/**
	 * @param $directory
	 * @param $options
	 * @return array|bool
	 */
	public static function hashDirectory($directory, $options)
	{
		if (!is_dir($directory)) {
			return false;
		}

		$file_hash = array();
		$dir = dir($directory);

		while (false !== ($file = $dir->read())) {
			if ($file != '.' and $file != '..') {
				if (is_dir($directory . '/' . $file)) {
					$need_to_check = true;
					if (!empty($options['exclude'])) {
						foreach ($options['exclude'] as $path_restricted) {
							if ($path_restricted === $directory . '/' . $file) {
								$need_to_check = false;
							}
						}
					}

					if ($need_to_check) {
						$file_hash[$directory . '/' . $file] = self::hashDirectory($directory . '/' . $file, $options);
					}
				} else {
					if (!is_link($directory . '/' . $file)) {
						$path_exploded = explode(self::ROOT_PATH_RELATIVELY_TO_WORKER.self::PROJECT_FOLDER_NAME, $directory);
						$file_hash[$path_exploded[1] . '/' . $file] = md5_file($directory . '/' . $file);
					}
				}
			}
		}

		$dir->close();
		return $file_hash;
	}

	/**
	 * Makes and compares hashsum of all files with previous generated files hash
	 */
	public function compare()
	{
		$hashes_etalon_file = self::ROOT_PATH_RELATIVELY_TO_WORKER . self::PROJECT_FOLDER_NAME . self::CACHE_FILE_LOCATION_RELATIVELY_TO_ROOT;
		$hash_from_file = self::hashread();
		if (!empty($hash_from_file)) {
			$hash_array = self::hashgen();
			$result = self::hashcompare($hash_from_file, $hash_array);
			if (empty($result)) {
				$message = "Hash integrity is stable compare to etalon created at: " . date("F d Y H:i:s.", filemtime($hashes_etalon_file));
				echo "$message\n";
				return 0;
			}
			$mail_output_files = "";
			foreach ($result as $group_name => $group) {
				$out = strtoupper('[' . $group_name . ']') . "\n";
				$out .= implode("\n", $group);
				$out .= "\n\n";
				$mail_output_files .= $out;
				$message = $out;
				echo "$message";
			}
			
			return 0;
		} else {
			$message = 'No hash found. Please init hash tracking with init command.';
			echo "$message\n";
			return 1;
		}
	}

	/**
	 * @param array $hash_array
	 */
	public static function hashsave(array $hash_array)
	{
		$hashes_etalon_file = self::ROOT_PATH_RELATIVELY_TO_WORKER . self::PROJECT_FOLDER_NAME . self::CACHE_FILE_LOCATION_RELATIVELY_TO_ROOT;
		if (file_exists($hashes_etalon_file)) {
			unlink($hashes_etalon_file);
		}
		foreach ($hash_array as $k => $hash) {
			$line = $k . '|' . $hash . "\n";
			file_put_contents($hashes_etalon_file, $line, FILE_APPEND | LOCK_EX);
		}
	}

	/**
	 * @return array
	 */
	public static function hashread()
	{
		$hashes_etalon_file = self::ROOT_PATH_RELATIVELY_TO_WORKER . self::PROJECT_FOLDER_NAME . self::CACHE_FILE_LOCATION_RELATIVELY_TO_ROOT;
		$hashes_etalon = [];
		if (file_exists($hashes_etalon_file)) {
			$fn = fopen($hashes_etalon_file, "r");
			while (!feof($fn)) {
				$result = fgets($fn);
				if ($result) {
					$e = explode('|', $result);
					$hashes_etalon[$e[0]] = substr($e[1], 0, -1);
				}
			}
			fclose($fn);
		}
		return $hashes_etalon;
	}

	/**
	 * @param array $etalon_hashes
	 * @param array $new_hash
	 * @return array
	 */
	public static function hashcompare(array $etalon_hashes, array $new_hash)
	{
		$hash_differences = [];
		foreach ($etalon_hashes as $etalon_key => $etalon_hash) {
			if (array_key_exists($etalon_key, $new_hash) && array_key_exists($etalon_key, $etalon_hashes)) {
				if ($new_hash[$etalon_key] !== $etalon_hashes[$etalon_key]) {
					$hash_differences['modified'][] = $etalon_key;
				}
			}
		}

		foreach ($new_hash as $key => $hash) {
			if (!array_key_exists($key, $etalon_hashes)) {
				$hash_differences['new'][] = $key;
			}
		}

		foreach ($etalon_hashes as $key => $hash) {
			if (!array_key_exists($key, $new_hash)) {
				$hash_differences['deleted'][] = $key;
			}
		}
		return $hash_differences;
	}

	/**
	 * Makes and saves a hashsum of all files to compare in future
	 */
	public function init()
	{
		$hash_array = self::hashgen();
		self::hashsave($hash_array);
		$message = 'Hash generated successfully.';
		echo "$message\n";
		return 0;
	}

	/**
	 * Deletes hashsum of all files with previous generated files hash and stops tracking while new hash will not be created
	 */
	public function stop()
	{
		$hashes_etalon_file = self::ROOT_PATH_RELATIVELY_TO_WORKER . self::PROJECT_FOLDER_NAME . self::CACHE_FILE_LOCATION_RELATIVELY_TO_ROOT;
		if (file_exists($hashes_etalon_file)) {
			unlink($hashes_etalon_file);
			$message = 'Hash tracking stoped.';
			echo "$message\n";
			return 0;
		}
		$message = 'Nothing to stop.';
		echo "$message\n";
		return 1;
	}
	
}
