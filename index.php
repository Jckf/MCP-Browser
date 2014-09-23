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

	error_reporting(E_ALL);
	ini_set('display_errors', true);

	$_ts = microtime(true);

	require('func.php');

	$versions = array();
	
	$joined = array();
	
	$fields = array();
	$methods = array();
	$params = array();

	foreach (glob('mcp/*') as $mcp) {
		$version = parse_ini_file($mcp . '/conf/version.cfg');
		$versions[$version['ServerVersion']] = $version['MCPVersion'];
	}

	if (array_key_exists('mc', $_GET)) {
		if (!array_key_exists($_GET['mc'], $versions)) {
			header('Location: .');
			exit;
		}

		list($joined, $fields, $methods, $params) = load($_GET['mc']);
	}

	$obfcl = null;
	$deobfcl = null;
	
	if (array_key_exists('obfcl', $_GET) && !empty($_GET['obfcl'])) {
		$obfcl = $_GET['obfcl'];
		$deobfcl = $joined['CL'][$obfcl];
	}
		
	if (array_key_exists('deobfcl', $_GET)) {
		$deobfcl = $_GET['deobfcl'];
		$obfcl = array_search($deobfcl, $joined['CL']);
	}
	
	if ($obfcl != null && !array_key_exists($obfcl, $joined['CL'])) {
		header('Location: .');
		exit;
	}

	if ($deobfcl != null && !in_array($deobfcl, $joined['CL'])) {
		header('Location: .');
		exit;
	}

	header('Content-Type: text/html; charset=UTF-8', true);
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">

		<title>MCP Browser</title>

		<link href="css/main.css" type="text/css" rel="stylesheet">
		<link href="css/code.css" type="text/css" rel="stylesheet">
		<link href="css/search.css" type="text/css" rel="stylesheet">

		<link href="js/chosen/chosen.min.css" type="text/css" rel="stylesheet">

		<script src="js/main.js" type="text/javascript"></script>

		<script src="//code.jquery.com/jquery-1.11.0.min.js" type="text/javascript"></script>
		<script src="js/chosen/chosen.jquery.min.js" type="text/javascript"></script>
		<script type="text/javascript">
			$(function () {
				$('#obfcl').chosen({
					width: '100%',
					placeholder_text_single: 'Select a class'
				});
				$('#deobfcl').chosen({
					width: '100%',
					placeholder_text_single: 'Select a class',
					search_contains: true
				});
			});
		</script>
	</head>
	<body>
		<a href="."><h1>MCP Browser</h1></a>

		<?php if (!array_key_exists('mc', $_GET) || empty($_GET['mc'])) { ?>
			<h2>Select a version</h2>

			<div class="centerwrap">
				<table class="tiny centered listing table">
					<thead>
						<tr>
							<td>Minecraft version</td>
							<td>MCP version</td>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($versions as $mc => $mcp) { ?>
							<tr>
								<td><a href="?mc=<?= $mc ?>"><?= $mc ?></a></td>
								<td><?= $mcp ?></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		<?php } else { ?>
			<a href="?mc=<?= $_GET['mc'] ?>"><h2>Minecraft <?= $_GET['mc'] ?></h2></a>

			<form method="get" action="." onsubmit="disableEmpty(this)">
				<input type="hidden" name="mc" value="<?= $_GET['mc'] ?>">
				
				<table>
					<tr>
						<td class="classes" colspan="2">
							<table class="table">
								<thead>
									<tr>
										<td colspan="2">Classes</td>
									</tr>
									<tr>
										<td>Obfuscated</td>
										<td>Deobfuscated</td>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>
											<select id="obfcl" name="obfcl" onchange="clearSelect('deobfcl'); submitForm(this.form)">
												<option value=""></option>
												<?php foreach ($joined['CL'] as $obfuscated => $deobfuscated) { ?>
													<option <?= $obfcl != null && $obfuscated == $obfcl ? 'selected="selected"' : '' ?>><?= $obfuscated ?></option>
												<?php } ?>
											</select>
										</td>
										<td>
											<select id="deobfcl" name="deobfcl" onchange="clearSelect('obfcl'); submitForm(this.form)">
												<option value=""></option>
												<?php $_temp = $joined['CL']; sort($_temp); foreach ($_temp as $deobfuscated) { ?>
													<option <?= $deobfcl != null && $deobfuscated == $deobfcl ? 'selected="selected"' : '' ?>><?= $deobfuscated ?></option>
												<?php } ?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
					
					<?php if ($obfcl != null && $deobfcl != null) { ?>
						<tr>
							<td class="fields">
								<table class="listing table">
									<thead>
										<tr>
											<td colspan="3">Fields</td>
										</tr>
										<tr>
											<td>Obfuscated</td>
											<td>Deobfuscated</td>
											<td>Description</td>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($joined['FD'] as $var => $val) { ?>
											<?php if (strpos($var, $obfcl . '/') === 0) { $depretty = str_replace($deobfcl . '/', '', $val); ?>
												<tr>
													<td><?= str_replace($obfcl . '/', '', $var) ?></td>
													<td><?= array_key_exists($depretty, $fields) ? $fields[$depretty][1] : $depretty ?></td>
													<td class="tiny"><?= array_key_exists($depretty, $fields) ? $fields[$depretty][3] : '' ?></td>
												</tr>
											<?php } ?>
										<?php } ?>
									</tbody>
								</table>
							</td>

							<td class="methods">
								<table class="listing table">
									<thead>
										<tr>
											<td colspan="3">Methods</td>
										</tr>
										<tr>
											<td>Obfuscated</td>
											<td>Deobfuscated</td>
											<td>Description</td>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($joined['MD'] as $var => $val) { ?>
											<?php if (strpos($var, $obfcl . '/') === 0) { $method = null; ?>
												<tr>
													<td>
														<?php $method = parse_method(str_replace($obfcl . '/', '', $var)); ?>
														
														<span class="java_ret">
															<?php $link = is_minecraft_class($method[0]); ?>
															
															<?php if ($link) { ?>
																<a href="?mc=<?= $_GET['mc'] ?>&amp;obfcl=<?= declass($method[0]) ?>">
															<?php } ?>
															
															<?= pretty_class($method[0]) ?>
															
															<?php if ($link) { ?>
																</a>
															<?php } ?>
														</span>
														
														<span class="java_method"><?= $method[1] ?></span>
	
														<span class="java_args">(
															<?php $i = 0; foreach ($method[2] as $arg) { ?>
																<?php $link = is_minecraft_class($arg); ?>
															
																<?php if ($link) { ?>
																	<a href="?mc=<?= $_GET['mc'] ?>&amp;obfcl=<?= declass($arg) ?>">
																<?php } ?>
	
																<span class="java_arg"><?= pretty_class($arg) ?></span>
																
																<?php if ($link) { ?>
																	</a>
																<?php } ?>
																
																<?= ++$i != sizeof($method[2]) ? ',' : '' ?>
															<?php } ?>
														)</span>
													</td>
													<!-- OBFUSCATED METHOD ABOVE, DEOBFUSCATED METHOD BELOW -->
													<td>
														<?php $method = parse_method(str_replace($deobfcl . '/', '', $val)); ?>
														
														<span class="java_ret">
															<?php $link = is_minecraft_class($method[0]); ?>
															
															<?php if ($link) { ?>
																<a href="?mc=<?= $_GET['mc'] ?>&amp;deobfcl=<?= declass($method[0]) ?>">
															<?php } ?>
															
															<?= pretty_class($method[0]) ?>
															
															<?php if ($link) { ?>
																</a>
															<?php } ?>
														</span>
														
														<?php $name = array_key_exists($method[1], $methods) ? $methods[$method[1]][1] : $method[1] ?>
														<span class="java_method<?= strpos($name, 'access$') === 0 ? '_gen' : '' ?>"><?= $name ?></span>
	
														<span class="java_args">(
															<?php $i = 0; foreach ($method[2] as $arg) { ?>
																<?php $link = is_minecraft_class($arg); ?>
															
																<?php if ($link) { ?>
																	<a href="?mc=<?= $_GET['mc'] ?>&amp;deobfcl=<?= declass($arg) ?>">
																<?php } ?>
	
																<span class="java_arg"><?= pretty_class($arg) ?></span>
																
																<?php if ($link) { ?>
																	</a>
																<?php } ?>
																
																<?= ++$i != sizeof($method[2]) ? ',' : '' ?>
															<?php } ?>
														)</span>
													</td>
													<td class="tiny"><?= array_key_exists($method[1], $methods) ? $methods[$method[1]][3] : '' ?></td>
												</tr>
											<?php } ?>
										<?php } ?>
									</tbody>
								</table>
							</td>
						</tr>
	
						<tr>
							<td class="source" colspan="2">
								<table class="table">
									<thead>
										<tr>
											<td colspan="2">Source</td>
										</tr>
										<tr>
											<td>Obfuscated</td>
											<td>Deobfuscated</td>
										</tr>
									</thead>
									<tbody>
										<?php if (false) { ?>
										<tr class="code">
											<td>
												<div>
													<script src="http://gist-it.appspot.com/github/Jckf/mc-dev/blob/master/<?= $_GET['mc'] ?>%2520obfuscated/<?= substr($obfcl, 0, 1) ?>/<?= $obfcl ?>.java?footer=0"></script>
												</div>
											</td>
											<td>
												<div>
													<script src="http://gist-it.appspot.com/github/Jckf/mc-dev/blob/master/<?= $_GET['mc'] ?>%2520deobfuscated/<?= str_replace('net/minecraft/', '', $deobfcl) ?>.java?footer=0"></script>
												</div>
											</td>
										</tr>
										<?php } else { ?>
										<tr>
											<td colspan="2">Source code view has been disabled.</td>
										</tr>
										<?php } ?>
									</tbody>
								</table>
							</td>
						</tr>
					<?php } ?>
				</table>
			</form>
		<?php } ?>

		<div id="footer">
			Page by <a href="http://www.jckf.no/">Jim "Jckf" C K Flaten</a>. Data from <a href="http://mcp.ocean-labs.de/">Minecraft Coder Pack</a>.<br>
			Generated in <?= round(microtime(true) - $_ts, 3) ?> seconds.
		</div>
	</body>
</html>
