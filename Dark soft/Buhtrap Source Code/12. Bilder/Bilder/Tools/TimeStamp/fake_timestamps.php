<?php
/*
	fake_timestamps.php
	Sets random timestamp at IMAGE_FILE_HEADER.TimeDateStamp
*/

// entrypoint 
//error_reporting(E_ALL);
error_reporting(E_ALL & ~E_WARNING);

// check for input param 
if (!@isset($argv[1])) { die("ERR: need to specify <filename> to process"); }

$fname = $argv[1];
if (!file_exists($fname)) { die("ERR: file {$fname} does not exists"); }

$pe_data = file_get_contents($fname);

$res = array();

// IMAGE_DOS_HEADER
$res['IMAGE_DOS_HEADER'] = unpack('ve_magic/ve_cblp/ve_cp/ve_crlc/ve_cparhdr/ve_minalloc/ve_maxalloc/ve_ss/ve_sp/ve_csum/ve_ip/ve_cs/ve_lfarlc/ve_ovno/v4e_res/ve_oemid/ve_oeminfo/v10e_res2/ve_lfanew', $pe_data);
if ($res['IMAGE_DOS_HEADER']['e_magic'] != 0x05a4d) {  die("Invalid DOS header signature"); }

// arch-independent part of IMAGE_NT_HEADERS : Signature + IMAGE_FILE_HEADER
$res['IMAGE_FILE_HEADER'] = unpack('VSignature/vMachine/vNumberOfSections/VTimeDateStamp/VPointerToSymbolTable/VNumberOfSymbols/vSizeOfOptionalHeader/vCharacteristics', substr($pe_data, $res['IMAGE_DOS_HEADER']['e_lfanew']));
if ($res['IMAGE_FILE_HEADER']['Signature'] != 0x4550) {  die("Invalid PE header signature"); }

// modify timestamp with random val
$new_ts = mt_rand( strtotime("01 January 2014"), strtotime("01 June 2015") );	// gen rnd PE timestamp
$pe_data = substr_replace( $pe_data, pack("V", $new_ts), $res['IMAGE_DOS_HEADER']['e_lfanew'] + (2 * 4), 4);

// save result
file_put_contents($fname, $pe_data);

echo "OK: {$fname} ts ".date('d-M-Y H:i:s', $res['IMAGE_FILE_HEADER']['TimeDateStamp'])." -> ".date('d-M-Y H:i:s', $new_ts)."\n";



?>