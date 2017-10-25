<?php
/**
 * TwigMailer
 *
 * @author Matt Nunn
 */

namespace Licencing\core;

use Licencing\core\Exceptions\MailException;

/**
 * TwigMailer Class to enable sending twig-rendered emails via SwiftMailer Package.
 */
class TwigMailer
{

    /**
     * Twig Environment.
     *
     * @var \Twig_Environment
     */
    private $_twig;

    /**
     * Create TwigMailer Instance.
     *
     * @param \Twig_Environment $twig_Environment Twig environment.
     */
    public function __construct(\Twig_Environment $twig_Environment)
    {
        $this->_twig = $twig_Environment;
    }

    /**
     * Get the Twig template and render the Email ready to be sent.
     *
     * @param string $identifier Template name to be rendered.
     * @param array  $params     Parameters/variables to pass to template.
     *
     * @return \Swift_Message Swift_Message to return.
     * @throws MailException If there is an error.
     */
    public function getMessage($identifier, array $params = [])
    {
        try {
            $template = $this->_twig->loadTemplate("emails/{$identifier}.twig");

            $subject  = $template->renderBlock('subject', $params);
            $bodyHtml = $template->renderBlock('body_html', $params);
            $bodyText = $template->renderBlock('body_text', $params);

            return (new \Swift_Message())
                ->setSubject($subject)
                ->setBody($bodyHtml, 'text/html')
                ->addPart($bodyText, 'text/plain');
        } catch (\Exception $e) {
            throw new MailException("Error Creating Email", 500, $e);
        }

    }

}

?>