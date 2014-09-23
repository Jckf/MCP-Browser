<?php

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 Jim C K Flaten
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

function load($version) {
	global $versions;

	$cache = new Memcache();
	$cache->connect('unix:///var/run/memcached/memcached.sock', 0);

	$cached = $cache->get('mcpbrowser_' . $version);
	if ($cached != null)
		return $cached;

	$joined = array();

	$fields = array();
	$methods = array();
	$params = array();

	$mcpdir = 'mcp/' . $versions[$_GET['mc']];

	$fh = fopen($mcpdir . '/conf/joined.srg', 'r') or die('Could not find SRG file.');
	while (!feof($fh)) {
		$line = preg_replace("/\r|\n/", '', fgets($fh));
		
		if (strlen($line) == 0)
			continue;
		
		list($type, $classes) = explode(': ', $line, 2);
		list($source, $target) = explode(' ', $classes, 2);

		$type = strtoupper($type);

		if (!array_key_exists($type, $joined))
			$joined[$type] = array();
			
		if ($type == 'MD') {
			list($sp, $target) = explode(' ', $target, 2);
			$source .= ' ' . $sp;
		}
		
		$joined[$type][$source] = $target;
	}
	fclose($fh);

	$files_file = 'files/' . $version . '-server.txt';

	if (file_exists($files_file)) {
		$files = array();

		$fh = fopen($files_file, 'r');
		while (!feof($fh)) {
			$line = preg_replace("/\r|\n/", '', fgets($fh));
			
			if (strlen($line) == 0)
				continue;
			
			if (strpos($line, '.') === 0)
				continue;
			
			$files[] = $line;
		}
		
		foreach ($joined['CL'] as $o => $d) {
			if (!in_array($o . '.java', $files))
				unset($joined['CL'][$o]);
		}
	}

	$fields = array_map('str_getcsv', File($mcpdir . '/conf/fields.csv'));
	$sizeof = sizeof($fields);
	for ($i = 0; $i < $sizeof; $i++) {
		$fields[$fields[$i][0]] = $fields[$i];
		unset($fields[$i]);
	}

	$methods = array_map('str_getcsv', File($mcpdir . '/conf/methods.csv'));
	$sizeof = sizeof($methods);
	for ($i = 0; $i < $sizeof; $i++) {
		$methods[$methods[$i][0]] = $methods[$i];
		unset($methods[$i]);
	}
	
	//$params = array_map('str_getcsv', File($mcpdir . '/conf/params.csv'));

	$data = array(
		$joined,
		$fields,
		$methods,
		$params
	);

	$cache->set('mcpbrowser_' . $version, $data, false, 3600);

	return $data;
}

function is_minecraft_class($class) {
	if (strpos($class, 'class net/minecraft') === 0)
		return true;
	
	if (strpos($class, 'class ') === 0 && strpos($class, '/') === false)
		return true;

	return false;
}

function declass($class) {
	if (strpos($class, 'class ') !== false)
		$class = substr($class, 6);
	
	return $class;
}

function pretty_class($class) {
	$class = declass($class);

	$strrpos = strripos($class, '/');
	if ($strrpos !== false)
		$class = substr($class, $strrpos + 1);
	
	return $class;
}

function parse_method($raw) {
	global $methods;

	list($name, $raw_args, $raw_return) = preg_split("/ \(|\)/", $raw);
	
	$args = array();
	while (strlen($raw_args)) {
		$args[] = parse_type($raw_args);
	}

	return array(
		parse_type($raw_return),
		$name,
		$args
	);
}

function parse_type(&$raw) {
	$id = substr($raw, 0, 1);
	$raw = substr($raw, 1);

	switch ($id) {
		case 'I': return 'int';
		case 'S': return 'short';
		case 'B': return 'byte';
		case 'J': return 'long';
		case 'C': return 'char';
		case 'F': return 'float';
		case 'D': return 'double';
		case 'Z': return 'boolean';
		case '[': return parse_type($raw) . '[]';
		case 'V': return 'void';
		case 'A': return '<reference>';
		case 'L': break;
		default: return '?';
	}

	list($class, $raw) = explode(';', $raw, 2);
	return 'class ' . $class;
}
