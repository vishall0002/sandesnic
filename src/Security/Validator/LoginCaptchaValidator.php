<?php

namespace App\Security\Validator;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Captcha validator.
 *
 * @author Gregwar <g.passault@gmail.com>
 */
class LoginCaptchaValidator
{
    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private $session;

    /**
     * Session key to store the code.
     */
    private $key;

    /**
     * Error message text for non-matching submissions.
     */
    private $invalidMessage;

    /**
     * Configuration parameter used to bypass a required code match.
     */
    private $bypassCode;

    /**
     * Number of form that the user can submit without captcha.
     *
     * @var int
     */
    private $humanity;

    private $params;

    /**
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param string                                                     $key
     * @param string                                                     $invalidMessage
     * @param string|null                                                $bypassCode
     */
    public function __construct(SessionInterface $session, ParameterBagInterface $params, $key = '_captcha_captcha', $invalidMessage = 'Invalid Code', $bypassCode = 'bypass', $humanity = 0)
    {
        $this->session = $session;
        $this->key = $key;
        $this->invalidMessage = $invalidMessage;
        $this->bypassCode = $bypassCode;
        $this->humanity = $humanity;
        $this->params = $params;
    }

    public function validate($code)
    {
        $expectedCode = $this->getExpectedCode();

        $captchaDisabled = $this->params->get('is_captcha_disabled');
        if ('true' === $captchaDisabled) {
            return true;
        }

        if ($this->humanity > 0) {
            $humanity = $this->getHumanity();
            if ($humanity > 0) {
                $this->updateHumanity($humanity - 1);

                return;
            }
        }

        if (!($code && is_string($code) && ($this->compare($code, $expectedCode) || $this->compare($code, $this->bypassCode)))) {
            return false;
        } else {
            if ($this->humanity > 0) {
                $this->updateHumanity($this->humanity);
            }
        }

        $this->session->remove($this->key);

        if ($this->session->has($this->key.'_fingerprint')) {
            $this->session->remove($this->key.'_fingerprint');
        }

        return true;
    }

    /**
     * Retrieve the expected CAPTCHA code.
     *
     * @return mixed|null
     */
    protected function getExpectedCode()
    {
        $options = $this->session->get($this->key, array());
        if (is_array($options) && isset($options['phrase'])) {
            return $options['phrase'];
        }

        return null;
    }

    /**
     * Retreive the humanity.
     *
     * @return mixed|null
     */
    protected function getHumanity()
    {
        return $this->session->get($this->key.'_humanity', 0);
    }

    /**
     * Updates the humanity.
     */
    protected function updateHumanity($newValue)
    {
        if ($newValue > 0) {
            $this->session->set($this->key.'_humanity', $newValue);
        } else {
            $this->session->remove($this->key.'_humanity');
        }

        return null;
    }

    /**
     * Process the codes.
     *
     * @param $code
     *
     * @return string
     */
    protected function niceize($code)
    {
        return strtr(strtolower($code), 'oil', '01l');
    }

    /**
     * Run a match comparison on the provided code and the expected code.
     *
     * @param $code
     * @param $expectedCode
     *
     * @return bool
     */
    protected function compare($code, $expectedCode)
    {
        return $expectedCode && is_string($expectedCode) && $this->niceize($code) == $this->niceize($expectedCode);
    }
}
