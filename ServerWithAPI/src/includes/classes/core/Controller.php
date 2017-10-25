<?php
/**
 * Copyright (c) Matt Nunn - All Rights Reserved
 * Unauthorized copying of this file via any medium is strictly prohibited
 * Written by Matt Nunn <MH.Nunn@gmail.com> 2016.
 */

/**
 * Controller
 *
 * @author Matt Nunn
 */

namespace Licencing\core;

use Licencing\GlobalFunction;

/**
 * Class Controller
 *
 */
class Controller
{

    /**
     * @var string Base Path for application
     */
    private $_basePath;

    /**
     * @var array Path being used for if there are folders
     */
    private $_usepath = [];

    /**
     * @var \Twig_Environment Twig Environment.
     */
    private $_twig;

    /**
     * @var array INI configuration items.
     */
    private $_ini;

    /**
     * @var boolean debug flag.
     */
    private $_debug = FALSE;

    /**
     * @var string file to be accessed
     */
    private $_controller;

    /**
     * @var array Variables/Parameters passed to file.
     */
    private $_get;

    /**
     * Constructor Function.
     *
     * @param \Twig_Environment $twig Twig Environment (optional).
     * @param array|string      $ini  INI array (optional).
     */
    public function __construct(\Twig_Environment $twig, $ini)
    {
        $this->_ini      = $ini;
        $this->_basePath = ($this->_ini['config']['base_path']) ?: '';
        $this->_debug    = (strtolower($this->_ini['debug']['status']) == 1);

        $this->_parseURI();
        $this->_twig = $twig;
    }

    /**
     * URI Parser.
     *
     * @return void
     * @throws \Exception On Non-existing page.
     */
    private function _parseURI()
    {
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), "/");
        $path = preg_replace("/[^a-zA-Z0-9]\//", "", $path);
        if (strlen($path) > 0 && $this->_basePath != "") {
            if (strpos($path, $this->_basePath) === 0) {
                $path = substr($path, strlen($this->_basePath));
            }
        }
        $path   = ltrim($path, "/");
        $parray = array_filter(explode("/", $path));
        foreach ($parray as $folder) {
            if (!is_dir($this->_ini['config']['document_root'] . "/templates/" . implode("/", $this->_usepath) . $folder)) {
                break;
            } else {
                $path             = preg_replace("/$folder/", "", $path, 1);
                $this->_usepath[] = $folder;
            }
        }

        $path = ltrim($path, "/");

        @list($controller, $params) = array_values(array_filter(explode("/", $path, 2)));

        $this->_setController($controller);
        $this->_mergeGetParams($params);
    }

    /**
     * Set the controller used by the application and check it exists.
     *
     * @param string $controller Controller.
     *
     * @return void
     * @throws \Exception On Non-existing page.
     */
    private function _setController($controller)
    {
        if ($controller == '' || !file_exists($this->_ini['config']['document_root'] . "/templates/" . implode("/", $this->_usepath) . "/" . $controller . ".twig")) {
            $this->_controller = ($this->_ini['config']['default_module']) ?: 'index';
            if (!file_exists($this->_ini['config']['document_root'] . "/templates/" . implode("/", $this->_usepath) . "/" . $controller . ".twig") && $controller != '') {
                throw new \Exception("Page Does Not Exist: {$_SERVER['REQUEST_URI']}", 404);
            }
        } else {
            $this->_controller = $controller;
        }
    }

    /**
     * Merge any 'GET' parameters into the global $_GET parametesr.
     *
     * @param string $params Form GET Parameters.
     *
     * @return void
     */
    private function _mergeGetParams($params)
    {
        $get = $_GET;
        if (isset($params)) {
            $this->_get = array_merge($get, explode("/", $params));
            $_GET       = $this->_get;
        }
    }

    /**
     * Set Base Path.
     *
     * @param string $path Base Path.
     *
     * @return void
     */
    public function setBasePath($path)
    {
        $this->_basePath = $path;
    }

    /**
     * Render controller with template.
     *
     * @return void
     * @throws \Exception If module cant be loaded.
     */
    public function render()
    {
        $this->_setupBeforeRender($options);
        try {
            $default = ($this->_ini['config']['default_module'] ?? "index");
            $path    = (!empty($this->_usepath)) ? implode("/", $this->_usepath) . "/" : "";
            if (!file_exists($this->_ini['config']['document_root'] . "/templates/{$path}{$this->_controller}.twig")) {
                header("Location: /{$this->_controller}");
                $this->_controller = $default;
            }
            if (file_exists($this->_ini['config']['document_root'] . "/controller/{$path}{$this->_controller}.php")) {
                $class   = "LicencingController\\" . ucwords($this->_controller);
                $claz    = new $class;
                $options = $claz->getOptions();
            }
            $GLOBALS['options'] = array_merge($GLOBALS['options'], $options);
            echo $this->_twig->render("{$path}{$this->_controller}.twig", $GLOBALS['options']);
        } catch (\Exception $e) {
            GlobalFunction::logError($e);
            throw new \Exception("Unable to render Page", 500);
        }
    }

    /**
     * Prepare Variables for rendering.
     *
     * @param array|null $options Options Array.
     *
     * @return void
     */
    private function _setupBeforeRender(&$options)
    {
        foreach ($GLOBALS as $variable => $vals) {
            if (substr($variable, 0, 1) != "_") {
                $$variable = $vals;
            }
        }
        if (!(isset($GLOBALS['options']))) {
            $GLOBALS['options'] = [];
        }
        if (!isset($options)) {
            $options = [];
        }
    }
}