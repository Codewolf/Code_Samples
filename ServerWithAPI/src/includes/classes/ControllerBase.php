<?php

namespace Licencing;

/**
 * Class ControllerBase
 *
 * @package Licencing
 */
abstract class ControllerBase
{

    /**
     * @var array Options to pass to twig template.
     */
    protected $options = [];

    /**
     * Fetch the Options to be rendered.
     *
     * @return array Options Array.
     */
    public function getOptions()
    {
        return $this->options;
    }
}