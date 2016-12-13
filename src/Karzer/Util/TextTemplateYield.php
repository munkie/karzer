<?php

namespace Karzer\Util;

class TextTemplateYield extends \Exception
{
    /**
     * @var \Text_Template
     */
    protected $template;

    /**
     * @param \Text_Template $template
     */
    public function __construct(\Text_Template $template)
    {
        $this->template = $template;
        parent::__construct();
    }

    /**
     * @return \Text_Template
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
