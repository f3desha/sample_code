<?php
namespace HashCompareSystem\Engine;

class HashWorker {
	/**
	 * @return array
	 */
	public static function hashgen()
	{
		//Generates hash sums for folders of carvoy
		$root = '../hash-compare-system';

		$list_to_check = [
			$root . '/public' => [
				'exclude' => [
					$root . '/public/excluded',
				]
			],
		];
		$check_hashes = [];
		
		foreach ($list_to_check as $path => $options) {
			$path_exploded = explode($root, $path);
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
		$root = '../hash-compare-system';
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
						$path_exploded = explode($root, $directory);
						$file_hash[$path_exploded[1] . '/' . $file] = md5_file($directory . '/' . $file);
					}
				}
			}
		}

		$dir->close();
		return $file_hash;
	}
}
