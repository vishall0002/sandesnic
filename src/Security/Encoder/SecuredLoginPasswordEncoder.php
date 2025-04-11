<?php

namespace App\Security\Encoder;

use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder as BaseMessageDigestPasswordEncoder;

/**
 * Extends MessageDigestPasswordEncoder to support custom merging of password and salt strings.
 *
 * @author Vipin Bose <bose.vpin@nic.in>
 */
class SecuredLoginPasswordEncoder extends BaseMessageDigestPasswordEncoder
{

    /**
     * {@inheritdoc}
     */
    public function encodePassword($raw, $salt)
    {
        // die;
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException('Invalid password.');
        }

        if (!in_array('sha256', hash_algos(), true)) {
            throw new \LogicException(sprintf('The algorithm "%s" is not supported.', 'sha256'));
        }

        // dump($raw);
        // die;
        if (substr($raw, 0, 5) === "hash:") {
            /* Handling hash coming directly from browser */
            $salted = $this->mergePasswordAndSalt(substr($raw, 5), $salt);
            $digest = bin2hex(hash('sha256', $salted, true));
        } elseif (substr($raw, 0, 11) === "hashsalted:") {
            /* Handling hash coming directly from browser */
            /* Do Nothing as the string is already hashed, salt merged and then again hashed coming directly from browser... this is the case of change userpassword */
            $digest = substr($raw, 11);
        } else {
            /* handling symfony builting packages */
            $salted = $this->mergePasswordAndSalt((bin2hex(hash('sha256', $raw, true))), $salt);
            $digest = bin2hex(hash('sha256', $salted, true));
        }
        
        for ($i = 1; $i < 7; $i++) {
            $digest = bin2hex(hash('sha256', $digest, true));
        }
        return $digest;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($digestDB, $digestBrowser, $salt)
    {
        // dump($digestBrowser.'-'.$digestDB);
        $digestUser = $digestBrowser;
        
        //-------------------The following line will handle plain text coming all the way from  browser-------// 
        // $salted = $this->mergePasswordAndSalt((bin2hex(hash('sha256', $digestBrowser, true))), $salt);
        // $digestUser = bin2hex(hash('sha256', $salted, true));
        //-------------------The following line will handle plain text coming all the way from  browser-------// 
        for ($i = 1; $i < 7; $i++) {
            $digestUser = bin2hex(hash('sha256', $digestUser, true));
        }

        // dump($digestUser.'-'.$digestDB);
        // die;
        return $this->comparePasswords($digestUser, $digestDB);
    }
}
