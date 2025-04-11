<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use App\Services\ProfileWorkspace;

class AppServices {

    private $emr;
    private $security;
    private $profileWorkspace;

    public function __construct(EntityManagerInterface $em, Security $security, ProfileWorkspace $profileWorkspace) {
        $this->emr = $em;
        $this->security = $security;
        $this->profileWorkspace = $profileWorkspace;
    }

    public function getUnixTimeStamp() {
        return strtotime((new \DateTimeImmutable('now'))->format('d-m-Y H:i:s'));
    }

    public function getAvailableRoles() {
        $em = $this->emr;
        $loggedUser = $this->security->getUser();
        return $em->getRepository('App:Portal\Profile')->findBy(['user' => $loggedUser, 'isEnabled' => true]);
    }

    public function getEmployeeByJabberID($jabberID) {
        $em = $this->emr;
        return $em->getRepository('App:Portal\Employee')->findOneBy(['jabberId' => $jabberID]);
    }

    public function getGroupByJabberID($jabberID) {
        $em = $this->emr;
        $parts = explode('@', $jabberID);
        $groupName = $parts[0];
        $groupHost = $parts[1];
        return $em->getRepository('App:Portal\Group')->findOneBy(['groupName' => $groupName, 'xmppHost' => $groupHost]);
    }

    public function getUserByID($userID) {
        $em = $this->emr;
        return $em->getRepository('App:Portal\User')->findOneBy(['id' => $userID]);
    }

    public function getOS() { 

        $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
        $os_platform =   "Unidentified OS platform";
        $os_array =   array(
            '/windows nt 10/i'      =>  'Windows 10',
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/windows nt 6.0/i'     =>  'Windows Vista',
            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     =>  'Windows XP',
            '/windows xp/i'         =>  'Windows XP',
            '/windows nt 5.0/i'     =>  'Windows 2000',
            '/windows me/i'         =>  'Windows ME',
            '/win98/i'              =>  'Windows 98',
            '/win95/i'              =>  'Windows 95',
            '/win16/i'              =>  'Windows 3.11',
            '/macintosh|mac os x/i' =>  'Apple',
            '/mac_powerpc/i'        =>  'Apple',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'Apple',
            '/ipod/i'               =>  'Apple',
            '/ipad/i'               =>  'Apple',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
            '/webos/i'              =>  'Mobile'
        );
    
        foreach ( $os_array as $regex => $value ) { 
            if ( preg_match($regex, $user_agent ) ) {
                $os_platform = $value;
            }
        }   
        return $os_platform;
    }
    
    public function getBrowser() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
        $browser        = "Unidentified browser";
        $browser_array  = array(
            '/msie/i'       =>  'Internet Explorer',
            '/firefox/i'    =>  'Firefox',
            '/safari/i'     =>  'Safari',
            '/chrome/i'     =>  'Chrome',
            '/edge/i'       =>  'Edge',
            '/opera/i'      =>  'Opera',
            '/netscape/i'   =>  'Netscape',
            '/maxthon/i'    =>  'Maxthon',
            '/konqueror/i'  =>  'Konqueror',
            '/mobile/i'     =>  'Handheld Browser'
        );
    
        foreach ( $browser_array as $regex => $value ) { 
            if ( preg_match( $regex, $user_agent ) ) {
                $browser = $value;
            }
        }
        return $browser;
    }

}
